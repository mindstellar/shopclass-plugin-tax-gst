<?php
/*
Plugin Name: Tax GST
Plugin URI: https://github.com/mindstellar/shopclass-plugin-tax-gst
Description: India GST for the Tax plugin: CGST and SGST halves when the buyer is in the seller's state, IGST otherwise, the GSTIN and HSN on the invoice.
Version: 0.1.0
Author: Navjot Tomer (Mindstellar)
Author URI: https://mindstellar.com
Short Name: tax-gst
Requires Shopclass: 7.1.0
Tested up to: 7.1
Requires PHP: 8.2
Support URI: https://github.com/mindstellar/shopclass-plugin-tax-gst/issues
Modes: shop
*/

/*
 * This file is part of the Tax GST plugin for Shopclass.
 * Copyright (c) 2026 Navjot Tomer (Mindstellar) and contributors
 *
 * Distributed under the GNU General Public License v3.0 or later. See LICENSE.
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

use mindstellar\taxgst\Plugin;

if (!defined('ABS_PATH')) {
    exit('Direct access is not allowed.');
}

spl_autoload_register(static function (string $class): void {
    $prefix = 'mindstellar\\taxgst\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }
    $file = __DIR__ . '/src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

osc_register_plugin(osc_plugin_path(__FILE__), array(Plugin::class, 'install'));

// The filters are inert until the Tax plugin runs them; the notice appears when it is missing.
osc_add_filter('tax_parts', array(Plugin::class, 'parts'), 10);
osc_add_filter('tax_invoice', array(Plugin::class, 'invoice'), 10);
osc_add_hook('admin_page_header', array(Plugin::class, 'notice'), 10);
