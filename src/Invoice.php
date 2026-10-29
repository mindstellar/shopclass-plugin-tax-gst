<?php

/*
 * This file is part of the Tax GST plugin for Shopclass.
 * Copyright (c) 2026 Navjot Tomer (Mindstellar) and contributors
 *
 * Distributed under the GNU General Public License v3.0 or later. See LICENSE.
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

declare(strict_types=1);

namespace mindstellar\taxgst;

/**
 * What GST adds to the Tax plugin's invoice: the number is called GSTIN, tax codes are HSN
 * codes, and the place of supply gets its state code and the kind of supply.
 */
final class Invoice
{
    /**
     * @param array<string,mixed> $invoice the Tax plugin's invoice
     * @param array<string,mixed> $context the Tax plugin's filter context
     *
     * @return array<string,mixed>
     */
    public static function apply(array $invoice, array $context): array
    {
        $intra = Split::intraState($context);
        if ($intra === null) {
            return $invoice;
        }
        $label = trim((string) ($invoice['tax_id_label'] ?? ''));
        if ($label === '' || $label === __('Tax number', 'tax')) {
            $invoice['tax_id_label'] = 'GSTIN';
        }
        $invoice['code_label'] = 'HSN';

        $extra = is_array($invoice['extra'] ?? null) ? $invoice['extra'] : array();
        $code  = States::code((string) ($context['state'] ?? ''));
        if ($code !== null) {
            $extra[] = array('label' => __('State code', 'tax-gst'), 'value' => $code . ' ' . States::NAMES[$code]);
        }
        $extra[] = array(
            'label' => __('Supply', 'tax-gst'),
            'value' => $intra ? __('Within the state (CGST and SGST)', 'tax-gst') : __('Between states (IGST)', 'tax-gst'),
        );
        $invoice['extra'] = $extra;

        return $invoice;
    }
}
