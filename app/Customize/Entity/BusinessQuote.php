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

namespace Customize\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Entity\AbstractEntity;

#[ORM\Entity]
#[ORM\Table(name: 'dtb_business_quote')]
class BusinessQuote extends AbstractEntity
{
    #[ORM\Id]
    #[ORM\Column(length: 32)]
    private string $id;

    /** @var array<string, mixed> */
    #[ORM\Column(type: 'json')]
    private array $snapshot;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $issuedAt;

    /** @param array<string, mixed> $snapshot */
    public function __construct(string $id, array $snapshot, \DateTimeImmutable $issuedAt)
    {
        $this->id = $id;
        $this->snapshot = $snapshot;
        $this->issuedAt = $issuedAt;
    }

    public function getId(): string
    {
        return $this->id;
    }

    /** @return array<string, mixed> */
    public function getSnapshot(): array
    {
        return $this->snapshot;
    }

    public function getIssuedAt(): \DateTimeImmutable
    {
        return $this->issuedAt;
    }
}
