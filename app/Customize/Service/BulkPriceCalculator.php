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

final class BulkPriceCalculator
{
    /** @return array{quantity: int, unitPrice: string, rate: int, subtotal: string, discount: string, net: string} */
    public function calculate(string $unitPrice, int $quantity): array
    {
        if ($quantity < 1 || $quantity > 10000 || !is_numeric($unitPrice) || bccomp($unitPrice, '0', 2) < 0) {
            throw new \InvalidArgumentException('数量は1〜10,000個で指定してください。');
        }
        $rate = match (true) {
            $quantity >= 100 => 15,
            $quantity >= 50 => 10,
            $quantity >= 10 => 5,
            default => 0,
        };
        $subtotal = bcmul($unitPrice, (string) $quantity, 0);
        // 円未満切り捨て。割引を税抜の商品合計に一度だけ適用する。
        $discount = bcdiv(bcmul($subtotal, (string) $rate, 0), '100', 0);

        return [
            'quantity' => $quantity, 'unitPrice' => $unitPrice, 'rate' => $rate,
            'subtotal' => $subtotal, 'discount' => $discount,
            'net' => bcsub($subtotal, $discount, 0),
        ];
    }
}
