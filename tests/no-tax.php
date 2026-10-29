<?php
/*
 * This file is part of the Tax GST plugin for Shopclass.
 * Copyright (c) 2026 Navjot Tomer (Mindstellar) and contributors
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

/**
 * Without the Tax plugin the filters are never run and the admin is told it is needed.
 */

putenv('SHOPCLASS_TAX=/nonexistent');
require __DIR__ . '/lib/bootstrap.php';

$GLOBALS['hooks'] = array();
function osc_register_plugin($path, $fn)
{
}
function osc_plugin_path($file)
{
    return 'tax-gst/index.php';
}
function osc_add_hook($hook, $fn, $priority = 5)
{
    $GLOBALS['hooks'][$hook][] = $fn;
}
function osc_esc_html($s)
{
    return htmlspecialchars((string) $s, ENT_QUOTES);
}

$GLOBALS['settings'] = array();
function osc_settings_value($section, $name)
{
    return $GLOBALS['settings'][$section][$name] ?? '';
}

require __DIR__ . '/../index.php';

harness_section('without the Tax plugin');
$notice = static function (): string {
    ob_start();
    ($GLOBALS['hooks']['admin_page_header'][0])();

    return (string) ob_get_clean();
};
check('the admin is told Tax is needed', str_contains($notice(), 'needs the Tax plugin'));
pin('the filters change nothing when nobody runs them', 'x', osc_apply_filter('tax_parts', 'x'));
pin('and are only the two hooks', array('tax_parts', 'tax_invoice'), array_keys($GLOBALS['filters']));


exit(harness_result());
