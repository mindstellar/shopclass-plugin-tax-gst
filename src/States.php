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
 * India's states and union territories by GST state code, matched by name without case,
 * punctuation or the usual old and short spellings.
 */
final class States
{
    /** @var array<string,string> two-digit GST code => name */
    public const NAMES = array(
        '01' => 'Jammu and Kashmir', '02' => 'Himachal Pradesh', '03' => 'Punjab', '04' => 'Chandigarh',
        '05' => 'Uttarakhand', '06' => 'Haryana', '07' => 'Delhi', '08' => 'Rajasthan',
        '09' => 'Uttar Pradesh', '10' => 'Bihar', '11' => 'Sikkim', '12' => 'Arunachal Pradesh',
        '13' => 'Nagaland', '14' => 'Manipur', '15' => 'Mizoram', '16' => 'Tripura',
        '17' => 'Meghalaya', '18' => 'Assam', '19' => 'West Bengal', '20' => 'Jharkhand',
        '21' => 'Odisha', '22' => 'Chhattisgarh', '23' => 'Madhya Pradesh', '24' => 'Gujarat',
        '26' => 'Dadra and Nagar Haveli and Daman and Diu', '27' => 'Maharashtra', '29' => 'Karnataka',
        '30' => 'Goa', '31' => 'Lakshadweep', '32' => 'Kerala', '33' => 'Tamil Nadu', '34' => 'Puducherry',
        '35' => 'Andaman and Nicobar Islands', '36' => 'Telangana', '37' => 'Andhra Pradesh', '38' => 'Ladakh',
    );

    /** @var array<string,string> other spellings => code */
    private const ALIASES = array(
        'nct of delhi' => '07', 'new delhi' => '07', 'national capital territory of delhi' => '07',
        'orissa' => '21', 'pondicherry' => '34', 'uttaranchal' => '05', 'chattisgarh' => '22',
        'dadra and nagar haveli' => '26', 'daman and diu' => '26', 'andaman and nicobar' => '35',
    );

    /**
     * The GST state code for a state name, or null when it is not one of India's.
     */
    public static function code(string $name): ?string
    {
        $key = self::fold($name);
        if ($key === '') {
            return null;
        }
        if (isset(self::ALIASES[$key])) {
            return self::ALIASES[$key];
        }
        foreach (self::NAMES as $code => $label) {
            $code = (string) $code;
            if (self::fold($label) === $key) {
                return $code;
            }
        }

        return null;
    }

    private static function fold(string $name): string
    {
        $name = mb_strtolower(str_replace('&', ' and ', $name), 'UTF-8');
        $name = (string) preg_replace('/[^a-z0-9]+/', ' ', $name);

        return trim($name);
    }
}
