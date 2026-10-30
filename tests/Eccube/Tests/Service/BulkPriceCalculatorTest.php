<?php

declare(strict_types=1);

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
    #[DataProvider(methodName: 'quantities')]
    public function testTierBoundaries(int $quantity, int $rate, string $discount): void
    {
        $result = (new BulkPriceCalculator())->calculate('1000', $quantity);
        $this->assertSame($rate, $result['rate']);
        $this->assertSame($discount, $result['discount']);
        $this->assertSame(bcsub(bcmul('1000', (string) $quantity), $discount, 0), $result['net']);
    }

    public static function quantities(): \Iterator
    {
        yield [1, 0, '0'];
        yield [9, 0, '0'];
        yield [10, 5, '500'];
        yield [49, 5, '2450'];
        yield [50, 10, '5000'];
        yield [99, 10, '9900'];
        yield [100, 15, '15000'];
        yield [10000, 15, '1500000'];
    }

    public function testDiscountRoundsDownToYen(): void
    {
        $this->assertSame('50', (new BulkPriceCalculator())->calculate('101', 10)['discount']);
    }

    #[DataProvider(methodName: 'invalidQuantities')]
    public function testInvalidQuantity(int $quantity): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new BulkPriceCalculator())->calculate('1000', $quantity);
    }

    public static function invalidQuantities(): \Iterator
    {
        yield [0];
        yield [-1];
        yield [10001];
    }
}
