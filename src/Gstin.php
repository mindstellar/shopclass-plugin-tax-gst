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
 * Format check for a GSTIN: 15 characters, a known state code and the mod-36 check digit.
 * No call to the GST portal is made, so a well-formed number may still be unregistered.
 */
final class Gstin
{
    private const ALPHABET = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';

    public static function normalise(string $raw): string
    {
        return strtoupper((string) preg_replace('/[\s.\-]+/', '', $raw));
    }

    public static function isValid(string $gstin): bool
    {
        if (!preg_match('/^(\d{2})[A-Z]{5}\d{4}[A-Z][1-9A-Z]Z[0-9A-Z]$/', $gstin, $m)) {
            return false;
        }
        if (!isset(States::NAMES[$m[1]])) {
            return false;
        }

        return self::checkChar(substr($gstin, 0, 14)) === $gstin[14];
    }

    /**
     * The state code (two digits) of a valid-looking GSTIN, or null.
     */
    public static function stateCode(string $gstin): ?string
    {
        $code = substr($gstin, 0, 2);

        return self::isValid($gstin) && isset(States::NAMES[$code]) ? $code : null;
    }

    /**
     * The check character for the first 14 characters.
     */
    public static function checkChar(string $first14): string
    {
        $sum = 0;
        foreach (str_split($first14) as $i => $ch) {
            $value = (int) strpos(self::ALPHABET, $ch) * ($i % 2 === 0 ? 1 : 2);
            $sum  += intdiv($value, 36) + $value % 36;
        }

        return self::ALPHABET[(36 - $sum % 36) % 36];
    }
}
