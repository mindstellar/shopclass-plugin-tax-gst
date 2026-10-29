<?php
/*
 * This file is part of the Tax GST plugin for Shopclass.
 * Copyright (c) 2026 Navjot Tomer (Mindstellar) and contributors
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

/**
 * Loads the plugin's classes with no core, no database and no network, and stands in for
 * __() and a filter registry. The Tax plugin's classes load too when its repository sits
 * beside this one (or SHOPCLASS_TAX points at it); tax_available() says whether they did.
 */

require_once __DIR__ . '/harness.php';

if (!defined('ABS_PATH')) {
    define('ABS_PATH', dirname(__DIR__, 2) . '/');
}

spl_autoload_register(static function (string $class): void {
    $prefix = 'mindstellar\\taxgst\\';
    if (strncmp($class, $prefix, strlen($prefix)) === 0) {
        require __DIR__ . '/../../src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    }
});

/** Where the Tax plugin lives, or null. */
function tax_dir(): ?string
{
    $dir = getenv('SHOPCLASS_TAX') ?: dirname(__DIR__, 3) . '/shopclass-plugin-tax';

    return is_file($dir . '/src/Calculator.php') ? $dir : null;
}

function tax_available(): bool
{
    return tax_dir() !== null;
}

if (tax_dir() !== null) {
    spl_autoload_register(static function (string $class): void {
        $prefix = 'mindstellar\\tax\\';
        if (strncmp($class, $prefix, strlen($prefix)) === 0) {
            $file = tax_dir() . '/src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
            if (is_file($file)) {
                require $file;
            }
        }
    });
}

ini_set('error_log', '/dev/null');
ini_set('display_errors', '1');
error_reporting(E_ALL);

if (!function_exists('__')) {
    function __($text, $domain = '')
    {
        return $text;
    }
}

$GLOBALS['filters'] = array();
if (!function_exists('osc_apply_filter')) {
    function osc_add_filter($hook, $fn, $priority = 5)
    {
        $GLOBALS['filters'][$hook][] = $fn;
    }

    function osc_apply_filter($hook, $content, ...$args)
    {
        foreach ($GLOBALS['filters'][$hook] ?? array() as $fn) {
            $content = $fn($content, ...$args);
        }

        return $content;
    }
}

/** A Tax plugin filter context. */
function ctx(string $sellerState, string $state, string $sellerCountry = 'IN', string $country = 'IN'): array
{
    return array(
        'seller_country' => $sellerCountry, 'seller_state' => $sellerState,
        'country' => $country, 'state' => $state, 'postcode' => '',
        'same_country' => $country === $sellerCountry,
        'same_state' => $state !== '' && strtolower(trim($state)) === strtolower(trim($sellerState)),
        'inclusive' => true, 'currency' => 'INR', 'user_id' => 1,
    );
}
