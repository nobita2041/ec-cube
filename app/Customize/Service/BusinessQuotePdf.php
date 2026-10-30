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
use Eccube\Service\Pdf\PdfWriter;

final class BusinessQuotePdf
{
    public function generate(BusinessQuote $quote): string
    {
        $q = $quote->getSnapshot();
        $pdf = new PdfWriter();
        $pdf->setMargins(20, 20);
        $pdf->addPage(false);
        $pdf->setFont('kozminproregular', '', 22);
        $pdf->setTextColor(30, 50, 75);
        $pdf->cell(170, 16, '御見積書（概算）', 0, 1);
        $pdf->setFont('kozminproregular', '', 10);
        $pdf->cell(170, 8, '見積番号：'.$q['number'], 0, 1);
        $pdf->cell(170, 8, '発行日：'.$q['issued'].'    有効期限：'.$q['expires'], 0, 1);
        $pdf->cell(170, 8, '発行元：'.$q['issuer'], 0, 1);
        $pdf->setY(70);
        $pdf->setFont('kozminproregular', '', 13);
        $pdf->multiCell(170, 8, $q['company'].' 御中');
        $pdf->setFont('kozminproregular', '', 10);
        $pdf->multiCell(170, 7, 'ご担当：'.$q['contact'].' 様');
        $pdf->multiCell(170, 7, '連絡先：'.$q['email']);
        $pdf->cell(170, 10, '下記のとおりお見積り申し上げます。', 0, 1);
        $pdf->setFillColor(237, 243, 248);
        $pdf->setFont('kozminproregular', '', 17);
        $pdf->cell(170, 17, 'お見積金額（税込）  ￥'.number_format((float) $q['total']), 0, 1, 'C', true);
        $pdf->setFont('kozminproregular', '', 10);
        $pdf->cell(170, 12, '商品明細', 0, 1);
        $pdf->multiCell(170, 8, $q['productName']);
        foreach ([
            '通常単価（税抜）' => '￥'.number_format((float) $q['unitPrice']),
            '数量' => number_format($q['quantity']).' 個',
            '通常商品合計（税抜）' => '￥'.number_format((float) $q['subtotal']),
            'まとめ買い割引（'.$q['rate'].'%）' => '− ￥'.number_format((float) $q['discount']),
            '割引後商品合計（税抜）' => '￥'.number_format((float) $q['net']),
            '消費税' => '￥'.number_format((float) $q['tax']),
            '合計（税込・送料別途）' => '￥'.number_format((float) $q['total']),
        ] as $label => $value) {
            $pdf->cell(110, 8, $label, 'B', 0);
            $pdf->cell(60, 8, $value, 'B', 1, 'R');
        }
        $pdf->cell(170, 8, '', 0, 1);
        $pdf->setFont('kozminproregular', '', 9);
        $pdf->multiCell(170, 6, "本書はデモ用の概算見積です。正式な注文・在庫確保ではありません。\n送料は別途お見積りします。納期・在庫・正式価格は担当者が確認します。\n割引は同一商品・同一規格の税抜合計に適用し、割引額の円未満は切り捨てます。\n通常購入のカートには適用されません。メールは送信されません。");

        return $pdf->output();
    }
}
