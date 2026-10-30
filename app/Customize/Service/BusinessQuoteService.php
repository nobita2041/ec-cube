<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * http://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Customize\Service;

use Customize\Entity\BusinessQuote;
use Doctrine\ORM\EntityManagerInterface;
use Eccube\Entity\Master\ProductStatus;
use Eccube\Entity\ProductClass;
use Eccube\Repository\BaseInfoRepository;
use Eccube\Repository\ProductClassRepository;
use Eccube\Service\TaxRuleService;

final class BusinessQuoteService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ProductClassRepository $products,
        private readonly BulkPriceCalculator $calculator,
        private readonly TaxRuleService $tax,
        private readonly BaseInfoRepository $baseInfo,
    ) {
    }

    /** @return list<ProductClass> */
    public function choices(): array
    {
        return $this->products->createQueryBuilder('pc')
            ->join('pc.Product', 'p')
            ->where('pc.visible = :visible AND p.Status = :status AND pc.price02 IS NOT NULL')
            ->setParameter('visible', true)->setParameter('status', ProductStatus::DISPLAY_SHOW)
            ->orderBy('p.id', 'DESC')->addOrderBy('pc.id', 'ASC')
            ->setMaxResults(100)->getQuery()->getResult();
    }

    /** @return array<string, mixed> */
    public function estimate(int $classId, int $quantity): array
    {
        $product = $this->products->find($classId);
        if (!$product || !$product->isVisible() || $product->getProduct()->getStatus()->getId() !== ProductStatus::DISPLAY_SHOW || $product->getPrice02() === null) {
            throw new \InvalidArgumentException('この商品は見積対象外です。');
        }
        $amounts = $this->calculator->calculate($product->getPrice02(), $quantity);
        $tax = $this->tax->getTax($amounts['net'], $product->getProduct(), $product);

        return $amounts + [
            'productClassId' => $classId,
            'productName' => $product->formattedProductName(),
            'tax' => $tax,
            'total' => bcadd($amounts['net'], $tax, 0),
        ];
    }

    /** @param array{product: ProductClass, quantity: int, company: string, contact: string, email: string} $data */
    public function create(array $data): BusinessQuote
    {
        $snapshot = $this->estimate($data['product']->getId(), $data['quantity']);
        $now = new \DateTimeImmutable('now', new \DateTimeZone('Asia/Tokyo'));
        $id = bin2hex(random_bytes(16));
        $snapshot += [
            'number' => 'BQ-'.$now->format('Ymd').'-'.strtoupper(substr($id, 0, 10)),
            'company' => $data['company'], 'contact' => $data['contact'], 'email' => $data['email'],
            'issued' => $now->format('Y/m/d'), 'expires' => $now->modify('+30 days')->format('Y/m/d'),
            'issuer' => $this->baseInfo->get()->getShopName(),
        ];
        $quote = new BusinessQuote($id, $snapshot, $now);
        $this->em->persist($quote);
        $this->em->flush();

        return $quote;
    }

    public function find(string $id): ?BusinessQuote
    {
        return $this->em->find(BusinessQuote::class, $id);
    }

    /** @return list<BusinessQuote> */
    public function recent(int $page): array
    {
        return $this->em->getRepository(BusinessQuote::class)->findBy([], ['issuedAt' => 'DESC', 'id' => 'DESC'], 20, ($page - 1) * 20);
    }
}
