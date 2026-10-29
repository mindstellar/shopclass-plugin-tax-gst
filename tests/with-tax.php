<?php
/*
 * This file is part of the Tax GST plugin for Shopclass.
 * Copyright (c) 2026 Navjot Tomer (Mindstellar) and contributors
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

/**
 * The split run through the Tax plugin's own Calculator: halves add up to the whole to the
 * paisa. Skips without the Tax plugin's source beside this repository.
 */

require __DIR__ . '/lib/bootstrap.php';

use mindstellar\tax\Calculator;
use mindstellar\tax\Currency;
use mindstellar\tax\Parts;
use mindstellar\taxgst\Split;

if (!tax_available()) {
    echo "SKIP  the Tax plugin is not beside this repository\nRESULT: 0 passed, 0 failed\n";
    exit(getenv('REQUIRE_CORE') === '1' ? 1 : 0);
}

$m     = static fn (float $units): int => (int) round($units * 1000000);
$taxes = static function (array $amounts, bool $inclusive, array $context): array {
    $default = Parts::single('GST');
    $parts   = Parts::normalise(Split::parts($default, $context), $default);
    $taxable = array();
    foreach ($amounts as $i => $amount) {
        $taxable[] = array('kind' => 'item', 'line' => $i, 'item_id' => $i + 1, 'amount' => $amount, 'bp' => 1800);
    }

    return (new Calculator($inclusive, Currency::step('INR')))->lines($taxable, $parts);
};

harness_section('halves through the Tax calculator');
$amounts = array($m(499), $m(1), $m(118.5), $m(0.99), $m(1234.56));
foreach (array(true, false) as $inclusive) {
    $name  = $inclusive ? 'inclusive' : 'exclusive';
    $intra = $taxes($amounts, $inclusive, ctx('Delhi', 'Delhi'));
    $inter = $taxes($amounts, $inclusive, ctx('Delhi', 'Maharashtra'));
    pin("$name: same state gives CGST 9% and SGST 9%", array('tax.cgst.9', 'tax.sgst.9'), array_column($intra, 'id'));
    pin("$name: other state gives IGST 18%", array('tax.igst.18'), array_column($inter, 'id'));
    pin("$name: CGST + SGST equals IGST to the micro", $inter[0]['amount'], $intra[0]['amount'] + $intra[1]['amount']);
    pin("$name: the taxable value is the same under both", $inter[0]['meta']['base'], $intra[0]['meta']['base']);
    pin("$name: lines are marked as the setting says", $inclusive, $intra[0]['meta']['included']);
}

harness_section('50 odd-priced lines never drift');
$many  = array_map(static fn (int $i): int => $m(10 + $i * 7.31), range(1, 50));
$intra = $taxes($many, true, ctx('Delhi', 'Delhi'));
$inter = $taxes($many, true, ctx('Delhi', 'Maharashtra'));
pin('halves sum to the whole', $inter[0]['amount'], $intra[0]['amount'] + $intra[1]['amount']);
pin('the halves differ by at most one paisa', true, abs($intra[0]['amount'] - $intra[1]['amount']) <= 10000 * 50);
pin('each half is a whole number of paise', 0, $intra[0]['amount'] % 10000);

exit(harness_result());
