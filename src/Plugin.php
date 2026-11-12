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
     * The warning text for the Tax plugin missing, or null.
     */
    public static function needsTaxText(): ?string
    {
        return class_exists('mindstellar\\tax\\Plugin')
            ? null
            : __('Tax GST needs the Tax plugin and does nothing without it. Install and enable Tax.', 'tax-gst');
    }

    /**
     * The warning text for a seller state GST cannot place, or null.
     */
    public static function sellerStateText(): ?string
    {
        if (!class_exists('mindstellar\\tax\\Plugin') || !function_exists('osc_settings_value')
            || strtoupper((string) osc_settings_value('tax', 'seller_country')) !== 'IN'
            || States::code((string) osc_settings_value('tax', 'seller_state')) !== null) {
            return null;
        }

        return __('Tax GST: set the seller state under Billing, Tax to one of India\'s states, so GST can be split into CGST and SGST.', 'tax-gst');
    }

    /**
     * Registers the two standing checks with core's notices; older cores use the page banner instead.
     */
    public static function registerNotices(): bool
    {
        if (!function_exists('osc_add_admin_notice')) {
            return false;
        }
        $link = function_exists('osc_settings_page_url') ? osc_settings_page_url('tax') : '';
        $base = array(
            'menu'       => 'billing',
            'submenu'    => 'billing_settings',
            'source'     => 'Tax GST',
            'link'       => $link,
            'link_label' => __('Tax settings', 'tax-gst'),
        );
        osc_add_admin_notice('warning', static fn (): string => (string) self::needsTaxText(), array(
            'id'   => 'tax_gst:needs_tax',
            'when' => static fn (): bool => self::needsTaxText() !== null,
        ) + $base);
        osc_add_admin_notice('warning', static fn (): string => (string) self::sellerStateText(), array(
            'id'   => 'tax_gst:seller_state',
            'when' => static fn (): bool => self::sellerStateText() !== null,
        ) + $base);

        return true;
    }

    /**
     * The page banner for older cores. Core's notices replace it when they exist.
     */
    public static function notice(): void
    {
        if (function_exists('osc_add_admin_notice')) {
            return;
        }
        $text = self::needsTaxText() ?? self::sellerStateText();
        if ($text === null) {
            return;
        }
        echo '<div class="flashmessage flashmessage-warning" role="status">' . osc_esc_html($text) . '</div>';
    }
}
