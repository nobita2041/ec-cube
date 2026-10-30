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

namespace Eccube\Tests\Service;

use Customize\Service\BulkPriceCalculator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class BulkPriceCalculatorTest extends TestCase
{
    #[DataProvider('quantities')]
    public function testTierBoundaries(int $quantity, int $rate, string $discount): void
    {
        $result = (new BulkPriceCalculator())->calculate('1000', $quantity);
        self::assertSame($rate, $result['rate']);
        self::assertSame($discount, $result['discount']);
        self::assertSame(bcsub(bcmul('1000', (string) $quantity), $discount, 0), $result['net']);
    }

    public static function quantities(): array
    {
        return [[1, 0, '0'], [9, 0, '0'], [10, 5, '500'], [49, 5, '2450'], [50, 10, '5000'], [99, 10, '9900'], [100, 15, '15000'], [10000, 15, '1500000']];
    }

    public function testDiscountRoundsDownToYen(): void
    {
        self::assertSame('50', (new BulkPriceCalculator())->calculate('101', 10)['discount']);
    }

    #[DataProvider('invalidQuantities')]
    public function testInvalidQuantity(int $quantity): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new BulkPriceCalculator())->calculate('1000', $quantity);
    }

    public static function invalidQuantities(): array
    {
        return [[0], [-1], [10001]];
    }
}
