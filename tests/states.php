<?php
/*
 * This file is part of the Tax GST plugin for Shopclass.
 * Copyright (c) 2026 Navjot Tomer (Mindstellar) and contributors
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

require __DIR__ . '/lib/bootstrap.php';

use mindstellar\taxgst\States;

harness_section('state names to GST codes');
pin('Delhi', '07', States::code('Delhi'));
pin('case and spaces do not matter', '27', States::code('  maharashtra '));
pin('"&" reads as "and"', '01', States::code('Jammu & Kashmir'));
pin('old and short spellings', array('21', '34', '07', '07', '35'), array(
    States::code('Orissa'), States::code('Pondicherry'), States::code('NCT of Delhi'), States::code('New Delhi'), States::code('Andaman & Nicobar'),
));
pin('the merged territory by either old name', array('26', '26'), array(States::code('Daman and Diu'), States::code('Dadra and Nagar Haveli')));
pin('not a state of India', null, States::code('Bavaria'));
pin('empty', null, States::code(''));
pin('every listed state finds itself', array(), array_values(array_filter(array_keys(States::NAMES), static fn (string $c): bool => States::code(States::NAMES[$c]) !== $c)));
pin('codes are unique two-digit strings', 36, count(array_unique(array_keys(States::NAMES))));

exit(harness_result());
