<?php
/*
 * This file is part of the Tax GST plugin for Shopclass.
 * Copyright (c) 2026 Navjot Tomer (Mindstellar) and contributors
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

require __DIR__ . '/lib/bootstrap.php';

use mindstellar\taxgst\Gstin;
use mindstellar\taxgst\Plugin;

harness_section('GSTIN check');
pin('a real GSTIN is valid', true, Gstin::isValid('27AAPFU0939F1ZV'));
pin('another with a Delhi code', true, Gstin::isValid('07AAACM1234A1ZA'));
pin('a check character of 0 (sum divides by 36) is valid', true, Gstin::isValid('27AAPFU1049F1Z0'));
pin('a wrong check character fails', false, Gstin::isValid('27AAPFU0939F1ZW'));
pin('a wrong length fails', false, Gstin::isValid('27AAPFU0939F1Z'));
pin('an unknown state code fails', false, Gstin::isValid('00AAPFU0939F1ZB'));
pin('though its check character is right', 'B', Gstin::checkChar('00AAPFU0939F1Z'));
pin('lowercase is not valid until normalised', false, Gstin::isValid('27aapfu0939f1zv'));
pin('the 14th character must be Z', false, Gstin::isValid('27AAPFU0939F1AV'));
pin('letters where digits belong fail', false, Gstin::isValid('27AAPFUA939F1ZV'));
pin('normalise strips spaces, dots and dashes and uppercases', '27AAPFU0939F1ZV', Gstin::normalise(' 27aapfu-0939.f1zv '));
pin('state code of a valid number', '27', Gstin::stateCode('27AAPFU0939F1ZV'));
pin('no state code for an invalid number', null, Gstin::stateCode('27AAPFU0939F1ZW'));

harness_section('the tax_buyer_id filter');
$ok = array('valid' => true, 'value' => '', 'message' => '');
$in = ctx('Delhi', 'Maharashtra');
$r  = Plugin::buyerId($ok, ' 27aapfu0939f1zv ', $in);
pin('a good GSTIN is normalised and accepted', array(true, '27AAPFU0939F1ZV'), array($r['valid'], $r['value']));
pin('the label is GSTIN', 'GSTIN', $r['label']);
$r = Plugin::buyerId($ok, '27AAPFU0939F1ZW', $in);
pin('a bad GSTIN is refused with a message', array(false, true), array($r['valid'], $r['message'] !== ''));
pin('an empty value passes (the field may be optional)', true, Plugin::buyerId($ok, '  ', $in)['valid']);
pin('outside India to India: check untouched', $ok, Plugin::buyerId($ok, 'whatever', ctx('Delhi', 'Bavaria', 'IN', 'DE')));
pin('seller abroad: check untouched', $ok, Plugin::buyerId($ok, 'whatever', ctx('Bavaria', 'Delhi', 'DE', 'IN')));
pin('a non-array check is returned as is', 'x', Plugin::buyerId('x', '1', $in));

exit(harness_result());
