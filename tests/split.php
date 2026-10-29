<?php
/*
 * This file is part of the Tax GST plugin for Shopclass.
 * Copyright (c) 2026 Navjot Tomer (Mindstellar) and contributors
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

require __DIR__ . '/lib/bootstrap.php';

use mindstellar\taxgst\Split;

$default = array(array('id' => 'gst', 'label' => 'GST', 'share' => 10000));
$ids     = static fn ($parts): array => array_column($parts, 'id');
$shares  = static fn ($parts): int => (int) array_sum(array_column($parts, 'share'));

harness_section('which supply');
pin('same state', true, Split::intraState(ctx('Delhi', 'Delhi')));
pin('another state', false, Split::intraState(ctx('Delhi', 'Maharashtra')));
pin('spelled differently, still the same state', true, Split::intraState(ctx('Delhi', 'NCT of Delhi')));
pin('state names we do not know fall back to the Tax plugin\'s answer', array(true, false), array(
    Split::intraState(ctx('Narnia', 'Narnia')), Split::intraState(ctx('Narnia', 'Oz')),
));
pin('a known buyer state against an unknown seller state is not the same', false, Split::intraState(ctx('Narnia', 'Delhi')));
pin('seller state not set: GST does not apply', null, Split::intraState(ctx('', 'Delhi')));
pin('buyer abroad: GST does not apply', null, Split::intraState(ctx('Delhi', 'Bavaria', 'IN', 'DE')));
pin('seller abroad: GST does not apply', null, Split::intraState(ctx('Bavaria', 'Delhi', 'DE', 'IN')));

harness_section('the parts');
$intra = Split::parts($default, ctx('Delhi', 'Delhi'));
pin('same state: CGST and SGST', array('cgst', 'sgst'), $ids($intra));
pin('in halves', array(5000, 5000), array_column($intra, 'share'));
pin('that sum to the whole', 10000, $shares($intra));
$inter = Split::parts($default, ctx('Delhi', 'Maharashtra'));
pin('other state: IGST alone', array('igst'), $ids($inter));
pin('at the full share', 10000, $shares($inter));
pin('GST not applying leaves the default parts alone', $default, Split::parts($default, ctx('Delhi', 'Bavaria', 'IN', 'DE')));
pin('and whatever another plugin set', 'x', Split::parts('x', ctx('', '')));

exit(harness_result());
