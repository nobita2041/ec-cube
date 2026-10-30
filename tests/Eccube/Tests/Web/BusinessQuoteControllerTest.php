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

namespace Eccube\Tests\Web;

use Customize\Entity\BusinessQuote;
use Customize\Service\BusinessQuoteService;
use Customize\Service\QuoteSettings;

final class BusinessQuoteControllerTest extends AbstractWebTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->client->disableReboot();
        static::getContainer()->set(QuoteSettings::class, new QuoteSettings($this->name() !== 'testDisabledFeaturePreservesShopAndRejectsDirectRoutes'));
    }

    public function testQuoteSnapshotPdfAndAdminAccess(): void
    {
        $product = $this->createProduct();
        $pc = $product->getProductClasses()->first();
        $pc->setPrice02('1000');
        $this->entityManager->flush();
        $page = $this->client->request('GET', $this->generateUrl('business_quote'));
        self::assertResponseIsSuccessful();
        $form = $page->selectButton('見積を依頼してPDFを発行 →')->form([
            'business_quote[product]' => (string) $pc->getId(),
            'business_quote[quantity]' => '100',
            'business_quote[company]' => 'テスト株式会社 <script>alert(1)</script>',
            'business_quote[contact]' => '山田 太郎',
            'business_quote[email]' => 'demo@example.invalid',
        ]);
        $this->client->submit($form);
        self::assertResponseRedirects();
        $url = $this->client->getResponse()->headers->get('Location');
        $id = basename($url);
        $quote = static::getContainer()->get(BusinessQuoteService::class)->find($id);
        self::assertInstanceOf(BusinessQuote::class, $quote);
        self::assertSame('15000', $quote->getSnapshot()['discount']);
        self::assertSame('85000', $quote->getSnapshot()['net']);
        $this->client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('alert(1)', $this->client->getResponse()->getContent());
        self::assertStringNotContainsString('<script>alert(1)</script>', $this->client->getResponse()->getContent());

        // Later catalogue changes must not change an already issued quote.
        $em = static::getContainer()->get(\Doctrine\ORM\EntityManagerInterface::class);
        $em->find(\Eccube\Entity\ProductClass::class, $pc->getId())->setPrice02('9000');
        $em->flush();
        $this->client->request('GET', $this->generateUrl('business_quote_pdf', ['id' => $id]));
        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'application/pdf');
        self::assertStringStartsWith('%PDF-', $this->client->getResponse()->getContent());
        self::assertStringContainsString('no-store', $this->client->getResponse()->headers->get('Cache-Control'));
        self::assertSame('85000', $quote->getSnapshot()['net']);

        $this->client->getCookieJar()->clear();
        $this->client->request('GET', $url);
        self::assertResponseStatusCodeSame(404);
        $this->client->request('GET', $this->generateUrl('business_quote_pdf', ['id' => $id]));
        self::assertResponseStatusCodeSame(404);
        $this->client->request('GET', $this->generateUrl('admin_business_quotes'));
        self::assertResponseRedirects();
        $this->loginTo($this->createMember());
        $this->client->request('GET', $this->generateUrl('admin_business_quotes'));
        self::assertResponseIsSuccessful();
        self::assertStringContainsString($quote->getSnapshot()['number'], $this->client->getResponse()->getContent());
        $this->client->request('GET', $this->generateUrl('admin_business_quote_show', ['id' => $id]));
        self::assertResponseIsSuccessful();
        $this->client->request('GET', $this->generateUrl('admin_business_quote_pdf', ['id' => $id]));
        self::assertResponseIsSuccessful();
        self::assertStringStartsWith('%PDF-', $this->client->getResponse()->getContent());
    }

    public function testCsrfRejectsUnprotectedRequest(): void
    {
        $product = $this->createProduct();
        $before = $this->entityManager->getRepository(BusinessQuote::class)->count([]);
        $this->client->request('POST', $this->generateUrl('business_quote'), [
            'business_quote' => [
                'product' => (string) $product->getProductClasses()->first()->getId(),
                'quantity' => '100', 'company' => 'テスト株式会社', 'contact' => '山田',
                'email' => 'demo@example.invalid',
            ],
        ]);
        self::assertResponseIsSuccessful();
        self::assertSame($before, $this->entityManager->getRepository(BusinessQuote::class)->count([]));
    }

    public function testDisabledFeaturePreservesShopAndRejectsDirectRoutes(): void
    {
        $this->client->request('GET', $this->generateUrl('business_quote'));
        self::assertResponseStatusCodeSame(404);
        $this->client->request('GET', $this->generateUrl('business_quote_estimate', ['product' => 11, 'quantity' => 100]));
        self::assertResponseStatusCodeSame(404);
        $this->client->request('GET', $this->generateUrl('homepage'));
        self::assertResponseIsSuccessful();
        self::assertStringNotContainsString('法人見積・まとめ買い', $this->client->getResponse()->getContent());
    }

    public function testEstimateRejectsInvalidQuantitiesAndHiddenProduct(): void
    {
        $product = $this->createProduct();
        $pc = $product->getProductClasses()->first();
        foreach (['0', '-1', '1.5', '10001', 'not-a-number'] as $quantity) {
            $this->client->request('GET', $this->generateUrl('business_quote_estimate', ['product' => $pc->getId(), 'quantity' => $quantity]));
            self::assertResponseStatusCodeSame(400);
        }
        $em = static::getContainer()->get(\Doctrine\ORM\EntityManagerInterface::class);
        $em->find(\Eccube\Entity\ProductClass::class, $pc->getId())->setVisible(false);
        $em->flush();
        $this->client->request('GET', $this->generateUrl('business_quote_estimate', ['product' => $pc->getId(), 'quantity' => 100]));
        self::assertResponseStatusCodeSame(400);
    }
}
