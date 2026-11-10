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
pin('the Tax filters', array('tax_parts', 'tax_invoice', 'tax_buyer_id'), array_keys($GLOBALS['filters']));
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

harness_section('with core notices');
pin('no API, no registration', false, \mindstellar\taxgst\Plugin::registerNotices());
$GLOBALS['notices'] = array();
if (!function_exists('osc_add_admin_notice')) {
    function osc_add_admin_notice($type, $text, $options = array())
    {
        $GLOBALS['notices'][$options['id']] = array($type, $text, $options);
    }
}
pin('registers when core has the API', true, \mindstellar\taxgst\Plugin::registerNotices());
pin('the two checks', array('tax_gst:needs_tax', 'tax_gst:seller_state'), array_keys($GLOBALS['notices']));
$opts = $GLOBALS['notices']['tax_gst:seller_state'][2];
pin('placed under Billing settings', 'billing/billing_settings', $opts['menu'] . '/' . $opts['submenu']);
pin('named by source', 'Tax GST', $opts['source']);
check('seller state fires for an unknown state', $opts['when']() && str_contains(($GLOBALS['notices']['tax_gst:seller_state'][1])(), 'one of India'));
check('needs_tax stays quiet while Tax runs', !$GLOBALS['notices']['tax_gst:needs_tax'][2]['when']());
$GLOBALS['settings']['tax']['seller_state'] = 'Delhi';
check('seller state quiet for a known state', !$opts['when']());
$GLOBALS['settings']['tax']['seller_state'] = 'Narnia';
pin('the page banner steps aside', '', $notice());

exit(harness_result());
