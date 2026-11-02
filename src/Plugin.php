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
 * The hooks: thin wrappers that hand the Tax plugin's filters to Split and Invoice.
 */
final class Plugin
{
    public static function install(): void
    {
    }

    /**
     * `tax_parts`.
     *
     * @param mixed $parts
     * @param mixed $context
     *
     * @return mixed
     */
    public static function parts($parts, $context = array())
    {
        return is_array($context) ? Split::parts($parts, $context) : $parts;
    }

    /**
     * `tax_invoice`.
     *
     * @param mixed $invoice
     * @param mixed $order
     * @param mixed $context
     *
     * @return mixed
     */
    public static function invoice($invoice, $order = null, $context = array())
    {
        return is_array($invoice) && is_array($context) ? Invoice::apply($invoice, $context, $order) : $invoice;
    }

    /**
     * `tax_buyer_id`: when GST applies, the number is a GSTIN, normalised and format-checked.
     *
     * @param mixed $check   {valid, value, message}
     * @param mixed $raw
     * @param mixed $context
     *
     * @return mixed
     */
    public static function buyerId($check, $raw = '', $context = array())
    {
        if (!is_array($check) || !is_array($context) || Split::intraState($context) === null) {
            return $check;
        }
        $value          = Gstin::normalise((string) $raw);
        $check['label'] = 'GSTIN';
        if ($value === '') {
            return $check;
        }
        if (!Gstin::isValid($value)) {
            return array('valid' => false, 'value' => $value, 'message' => __('Enter a valid 15-character GSTIN, such as 27AAPFU0939F1ZV.', 'tax-gst')) + $check;
        }

        return array('valid' => true, 'value' => $value, 'message' => '') + $check;
    }

    /**
     * An admin notice when the Tax plugin is not running, or its seller details cannot give GST.
     */
    public static function notice(): void
    {
        if (!class_exists('mindstellar\\tax\\Plugin')) {
            $text = __('Tax GST needs the Tax plugin and does nothing without it. Install and enable Tax.', 'tax-gst');
        } elseif (!function_exists('osc_settings_value') || strtoupper((string) osc_settings_value('tax', 'seller_country')) !== 'IN') {
            return;
        } elseif (States::code((string) osc_settings_value('tax', 'seller_state')) === null) {
            $text = __('Tax GST: set the seller state under Billing, Tax to one of India\'s states, so GST can be split into CGST and SGST.', 'tax-gst');
        } else {
            return;
        }
        echo '<div class="flashmessage flashmessage-warning" role="status">' . osc_esc_html($text) . '</div>';
    }
}
