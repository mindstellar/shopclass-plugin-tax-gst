<?php
/*
 * This file is part of the Tax GST plugin for Shopclass.
 * Copyright (c) 2026 Navjot Tomer (Mindstellar) and contributors
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

/**
 * index.php hooks the Tax plugin's two filters and the admin notice.
 */

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

harness_section('the hooks');
require __DIR__ . '/../index.php';
pin('both Tax filters', array('tax_parts', 'tax_invoice'), array_keys($GLOBALS['filters']));
pin('and the admin notice', array('admin_page_header'), array_keys($GLOBALS['hooks']));

$notice = static function (): string {
    ob_start();
    ($GLOBALS['hooks']['admin_page_header'][0])();

    return (string) ob_get_clean();
};

harness_section('with the Tax plugin');
if (!class_exists('mindstellar\\tax\\Plugin')) {
    eval('namespace mindstellar\\tax; final class Plugin {}');
}
pin('no notice for a seller outside India', '', $notice());
$GLOBALS['settings']['tax'] = array('seller_country' => 'IN', 'seller_state' => 'Delhi');
pin('no notice for a known state', '', $notice());
$GLOBALS['settings']['tax']['seller_state'] = 'Narnia';
check('a notice for a state GST cannot place', str_contains($notice(), 'one of India'));
pin('the parts filter splits', array('cgst', 'sgst'), array_column(osc_apply_filter('tax_parts', array(), ctx('Delhi', 'Delhi')), 'id'));
pin('bad arguments change nothing', 'x', osc_apply_filter('tax_parts', 'x', 'not an array'));
pin('the invoice filter leaves a non-array alone', 'x', osc_apply_filter('tax_invoice', 'x', null, ctx('Delhi', 'Delhi')));

exit(harness_result());
