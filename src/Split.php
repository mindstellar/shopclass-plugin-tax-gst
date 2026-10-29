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
 * Which GST applies to a sale: CGST and SGST halves inside the seller's state, IGST to
 * another state. Pure; the hooks pass it the Tax plugin's context.
 */
final class Split
{
    /**
     * True for a sale inside the seller's state, false for another state, null when GST
     * does not apply (not India to India, or the seller's state is not set).
     *
     * @param array<string,mixed> $context the Tax plugin's filter context
     */
    public static function intraState(array $context): ?bool
    {
        if (strtoupper((string) ($context['seller_country'] ?? '')) !== 'IN' || strtoupper((string) ($context['country'] ?? '')) !== 'IN') {
            return null;
        }
        $seller = trim((string) ($context['seller_state'] ?? ''));
        if ($seller === '') {
            return null;
        }
        $sellerCode = States::code($seller);
        $buyerCode  = States::code((string) ($context['state'] ?? ''));
        if ($sellerCode !== null && $buyerCode !== null) {
            return $sellerCode === $buyerCode;
        }

        return !empty($context['same_state']);
    }

    /**
     * The parts for the Tax plugin's `tax_parts` filter: the default ones when GST does not apply.
     *
     * @param mixed               $parts
     * @param array<string,mixed> $context
     *
     * @return mixed
     */
    public static function parts($parts, array $context)
    {
        $intra = self::intraState($context);
        if ($intra === null) {
            return $parts;
        }

        return $intra
            ? array(
                array('id' => 'cgst', 'label' => 'CGST', 'share' => 5000),
                array('id' => 'sgst', 'label' => 'SGST', 'share' => 5000),
            )
            : array(array('id' => 'igst', 'label' => 'IGST', 'share' => 10000));
    }
}
