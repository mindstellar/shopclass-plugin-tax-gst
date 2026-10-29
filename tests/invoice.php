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
pin('the buyer\'s state code', array('label' => 'State code', 'value' => '27 Maharashtra'), $in['extra'][1]);
pin('and the kind of supply', array('label' => 'Supply', 'value' => 'Between states (IGST)'), $in['extra'][2]);
pin('same state reads as CGST and SGST', 'Within the state (CGST and SGST)', Invoice::apply($base, ctx('Delhi', 'Delhi'))['extra'][2]['value']);
pin('the title and number are untouched', array('Tax invoice', '07AAACM1234A1Z5'), array($in['title'], $in['tax_id']));
pin('a label the admin chose is kept', 'Our GSTIN', Invoice::apply(array('tax_id_label' => 'Our GSTIN') + $base, ctx('Delhi', 'Delhi'))['tax_id_label']);
pin('a buyer state we do not know adds no code row, only the supply', 2, count(Invoice::apply($base, ctx('Narnia', 'Narnia'))['extra']));
pin('GST not applying: invoice unchanged', $base, Invoice::apply($base, ctx('Delhi', 'Bavaria', 'IN', 'DE')));

exit(harness_result());
