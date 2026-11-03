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
 * codes, the place of supply reads 27-Maharashtra, and rows for the kind of supply and reverse charge.
 */
final class Invoice
{
    /**
     * @param array<string,mixed> $invoice the Tax plugin's invoice
     * @param array<string,mixed> $context the Tax plugin's filter context
     * @param mixed               $order   the order, when the filter passes one
     *
     * @return array<string,mixed>
     */
    public static function apply(array $invoice, array $context, $order = null): array
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
            $invoice['place_of_supply'] = $code . '-' . States::NAMES[$code];
        }
        $extra[] = array(
            'label' => __('Supply', 'tax-gst'),
            'value' => $intra ? __('Within the state (CGST and SGST)', 'tax-gst') : __('Between states (IGST)', 'tax-gst'),
        );
        $rcm     = (bool) osc_apply_filter('tax_gst_reverse_charge', false, $order);
        $extra[] = array('label' => __('Reverse charge', 'tax-gst'), 'value' => $rcm ? __('Yes', 'tax-gst') : __('No', 'tax-gst'));
        $buyer   = self::buyerStateCode($order);
        if ($buyer !== null) {
            $extra[] = array('label' => __('Buyer GSTIN state code', 'tax-gst'), 'value' => $buyer . ' ' . States::NAMES[$buyer]);
        }
        $invoice['extra'] = $extra;

        return $invoice;
    }

    /**
     * The state code inside the GSTIN the buyer gave at checkout, if the order has one.
     *
     * @param mixed $order
     */
    private static function buyerStateCode($order): ?string
    {
        if (!is_object($order) || !method_exists($order, 'meta')) {
            return null;
        }
        $fields = $order->meta('_fields', array());
        $value  = is_array($fields) && is_array($fields['tax_id'] ?? null) ? Gstin::normalise((string) ($fields['tax_id']['value'] ?? '')) : '';

        return $value === '' ? null : Gstin::stateCode($value);
    }
}
