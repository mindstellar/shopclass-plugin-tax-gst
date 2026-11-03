<?php
/*
 * This file is part of the Tax GST plugin for Shopclass.
 * Copyright (c) 2026 Navjot Tomer (Mindstellar) and contributors
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

require __DIR__ . '/lib/bootstrap.php';

use mindstellar\taxgst\Invoice;

$base = array('title' => 'Tax invoice', 'tax_id_label' => 'Tax number', 'tax_id' => '07AAACM1234A1Z5', 'code_label' => 'Tax code', 'extra' => array(array('label' => 'Keep', 'value' => 'me')), 'summary' => array());

harness_section('the invoice');
$in = Invoice::apply($base, ctx('Delhi', 'Maharashtra'));
pin('the number is a GSTIN', 'GSTIN', $in['tax_id_label']);
pin('tax codes are HSN', 'HSN', $in['code_label']);
pin('rows already there stay first', array('label' => 'Keep', 'value' => 'me'), $in['extra'][0]);
pin('the place of supply replaces the Tax plugin\'s own, with no row of its own', '27-Maharashtra', $in['place_of_supply']);
pin('the kind of supply', array('label' => 'Supply', 'value' => 'Between states (IGST)'), $in['extra'][1]);
pin('reverse charge is stated, No by default', array('label' => 'Reverse charge', 'value' => 'No'), $in['extra'][2]);
pin('same state reads as CGST and SGST', 'Within the state (CGST and SGST)', Invoice::apply($base, ctx('Delhi', 'Delhi'))['extra'][1]['value']);
pin('Delhi place of supply', '07-Delhi', Invoice::apply($base, ctx('Delhi', 'Delhi'))['place_of_supply']);
$GLOBALS['filters']['tax_gst_reverse_charge'][] = static fn ($rcm, $order) => $order === 'rcm-order';
pin('the filter turns reverse charge to Yes', 'Yes', Invoice::apply($base, ctx('Delhi', 'Delhi'), 'rcm-order')['extra'][2]['value']);
pin('and other orders stay No', 'No', Invoice::apply($base, ctx('Delhi', 'Delhi'), 'other')['extra'][2]['value']);
unset($GLOBALS['filters']['tax_gst_reverse_charge']);
$fields = new class () {
    public function meta(string $k, $d = null)
    {
        return $k === '_fields' ? array('tax_id' => array('label' => 'GSTIN', 'value' => '29AAPFU0939F1ZR', 'invoice' => true)) : $d;
    }
};
$withId = Invoice::apply($base, ctx('Delhi', 'Maharashtra'), $fields);
pin('the buyer GSTIN state code row', array('label' => 'Buyer GSTIN state code', 'value' => '29 Karnataka'), $withId['extra'][3]);
pin('no such row without a buyer GSTIN', 3, count($in['extra']));
pin('the title and number are untouched', array('Tax invoice', '07AAACM1234A1Z5'), array($in['title'], $in['tax_id']));
pin('a label the admin chose is kept', 'Our GSTIN', Invoice::apply(array('tax_id_label' => 'Our GSTIN') + $base, ctx('Delhi', 'Delhi'))['tax_id_label']);
$narnia = Invoice::apply($base, ctx('Narnia', 'Narnia'));
pin('a buyer state we do not know keeps the Tax plugin\'s place of supply', array(3, null), array(count($narnia['extra']), $narnia['place_of_supply'] ?? null));
pin('GST not applying: invoice unchanged', $base, Invoice::apply($base, ctx('Delhi', 'Bavaria', 'IN', 'DE')));

exit(harness_result());
