<?php
/*
 * This file is part of the Tax GST plugin for Shopclass.
 * Copyright (c) 2026 Navjot Tomer (Mindstellar) and contributors
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

/**
 * Both plugins against core's real cart pricer, checkout and receipt on a scratch database:
 * a Delhi buyer is charged CGST + SGST, a Mumbai buyer IGST, the lines store the same tax
 * either way, and the receipt shows the GSTIN, HSN and the state code.
 *
 * Needs core's scratch MySQL, a core with shop mode and the Tax plugin beside this repository
 * (or SHOPCLASS_TAX); it skips without any of them. Usage:
 *   SHOPCLASS_CORE=/path/to/core DRIFT_DB_PORT=33061 php tests/integration/billing.php
 */

$core   = rtrim(getenv('SHOPCLASS_CORE') ?: dirname(__DIR__, 3) . '/shopclass', '/');
$tax    = rtrim(getenv('SHOPCLASS_TAX') ?: dirname(__DIR__, 3) . '/shopclass-plugin-tax', '/');
$pricer = $core . '/oc-includes/osclass/classes/billing/CartPricer.php';
if (!is_file($pricer) || !str_contains((string) file_get_contents($pricer), 'PROBLEM_POINTERS')) {
    echo "SKIP  core at $core has no shop-mode cart contract\nRESULT: 0 passed, 0 failed\n";
    exit(getenv('REQUIRE_CORE') === '1' ? 1 : 0);
}
if (!is_file($tax . '/index.php') || !str_contains((string) file_get_contents($tax . '/src/Plugin.php'), 'tax_parts')) {
    echo "SKIP  the Tax plugin is not at $tax\nRESULT: 0 passed, 0 failed\n";
    exit(getenv('REQUIRE_CORE') === '1' ? 1 : 0);
}

require_once $core . '/tests/lib/harness.php';
require_once $core . '/tests/lib/api-admin-kit.php';

$admin = api_admin_boot('osc_tax_gst_plugin');
$p     = DB_TABLE_PREFIX;
require_once ABS_PATH . 'oc-includes/osclass/helpers/hSettings.php';
require_once ABS_PATH . 'oc-includes/osclass/helpers/hDatabase.php';
require_once ABS_PATH . 'oc-includes/osclass/helpers/hAdminUi.php';

use mindstellar\billing\Billing;
use mindstellar\billing\CallbackResult;
use mindstellar\billing\CartPricer;
use mindstellar\billing\CheckoutIntent;
use mindstellar\billing\GoodsCheckout;
use mindstellar\billing\Order;
use mindstellar\billing\OrderLineStore;
use mindstellar\billing\OrderStore;
use mindstellar\billing\PaymentGateway;
use mindstellar\billing\PaymentGatewayRegistry;
use mindstellar\billing\Receipts;
use mindstellar\listing\VariantStore;

$locale = seed_locale($admin);
seed_currency($admin, 'INR', 'Indian Rupee');
seed_country($admin, 'IN', 'India');
$books  = seed_category($admin, 'Books', null, $locale);
$seller = seed_user($admin, 'seller', 'seller@example.test');
$buyer  = seed_user($admin, 'buyer', 'buyer@example.test');
$m      = static fn (float $units): int => (int) round($units * 1000000);
foreach (array('enabled_users' => '1', 'language' => 'en_US', 'currency' => 'INR', 'billing_enabled' => '1', 'billing_currency' => 'INR', 'billing_shipping_flat' => '0') as $k => $v) {
    Preference::getInstance()->set($k, $v);
}
scratchdb_forget_cache();
osc_reset_preferences();

final class GstFakeGateway implements PaymentGateway
{
    public function getId(): string
    {
        return 'fake';
    }

    public function getName(): string
    {
        return 'Fake';
    }

    public function getSupportedCurrencies(): array
    {
        return array('INR');
    }

    public function isConfigured(): bool
    {
        return true;
    }

    public function createCheckout(Order $order): CheckoutIntent
    {
        return CheckoutIntent::redirect('https://pay.example.test/' . $order->getId());
    }

    public function handleCallback(array $request): CallbackResult
    {
        return CallbackResult::ignored('none');
    }
}
PaymentGatewayRegistry::getInstance()->register(new GstFakeGateway());

$book = seed_item($admin, $books, $seller, 'Book', 1180.0);
$admin->query("UPDATE {$p}t_item SET fk_c_currency_code = 'INR' WHERE pk_i_id = $book");
$bookV = VariantStore::saveDefault($book, array('i_price' => $m(1180), 'i_stock' => null));
$cart  = array(array('variant' => $bookV, 'qty' => 1));

harness_section('both plugins load');
require $tax . '/index.php';
require dirname(__DIR__, 2) . '/index.php';
osc_set_preference('rates', '{"default_bp":1800,"by_country":{},"by_category":{}}', 'tax');
foreach (array('seller_country' => 'IN', 'seller_state' => 'Delhi', 'seller_tax_id' => '07AAACM1234A1Z5', 'tax_label' => 'GST') as $k => $v) {
    osc_set_preference($k, $v, 'tax');
}
osc_reset_preferences();

$pricer = new CartPricer();
$ids    = static fn ($q): array => array_column($q->extraLines('tax'), 'amount', 'id');

harness_section('the quote');
// 1180 holds 180 of GST at 18%.
pin('a Delhi buyer: CGST 9% and SGST 9%', array('tax.cgst.9' => $m(90), 'tax.sgst.9' => $m(90)), $ids($pricer->quote($cart, array('address' => CartPricer::quoteAddress('IN', 'Delhi', '110001')))));
pin('"NCT of Delhi" is the same state', array('tax.cgst.9' => $m(90), 'tax.sgst.9' => $m(90)), $ids($pricer->quote($cart, array('address' => CartPricer::quoteAddress('IN', 'NCT of Delhi', '110001')))));
pin('a Mumbai buyer: IGST 18%', array('tax.igst.18' => $m(180)), $ids($pricer->quote($cart, array('address' => CartPricer::quoteAddress('IN', 'Maharashtra', '400001')))));
pin('no address yet: counted in the seller\'s state', array('tax.cgst.9' => $m(90), 'tax.sgst.9' => $m(90)), $ids($pricer->quote($cart)));
$q = $pricer->quote($cart, array('address' => CartPricer::quoteAddress('IN', 'Maharashtra', '400001')));
pin('the total stays the price: tax is included', array($m(1180), $m(180)), array($q->total(), $q->totals()['tax_included']));
pin('a buyer in Germany keeps the single GST line', array('tax.gst.18'), array_keys($ids($pricer->quote($cart, array('address' => CartPricer::quoteAddress('DE', 'Bavaria', '80331'))))));

harness_section('orders and invoices');
$address = array('name' => 'Asha', 'phone' => '9800000000', 'line1' => '1 Marine Drive', 'line2' => '', 'city' => 'Mumbai', 'state' => 'Maharashtra', 'postcode' => '400001', 'country' => 'IN');
foreach (array('Delhi' => array('kind' => 'Within the state (CGST and SGST)', 'code' => '07 Delhi'), 'Maharashtra' => array('kind' => 'Between states (IGST)', 'code' => '27 Maharashtra')) as $state => $want) {
    $order = (new GoodsCheckout())->place($buyer, $cart, array('state' => $state) + $address, 'standard', 'fake')['order'];
    $lines = OrderLineStore::ofOrder($order->getId());
    $taxes = array_values(array_filter($lines, static fn ($l) => $l['kind'] === 'tax'));
    pin("$state: tax lines store amount 0 and the tax as unit", $state === 'Delhi' ? array(array(0, $m(90)), array(0, $m(90))) : array(array(0, $m(180))), array_map(static fn ($l) => array($l['amount'], $l['unit']), $taxes));
    pin("$state: parts in the meta", $state === 'Delhi' ? array('cgst', 'sgst') : array('igst'), array_map(static fn ($l) => $l['meta']['part'], $taxes));
    pin("$state: lines still add up to the total", OrderStore::find($order->getId())->getAmount(), (int) array_sum(array_column($lines, 'amount')));

    Billing::markPaid(OrderStore::find($order->getId()), 'ext-gst-' . $state);
    ob_start();
    Receipts::render(OrderStore::find($order->getId()), 'https://example.test/', 'Back');
    $html = (string) ob_get_clean();
    check("$state: the receipt is a tax invoice with the GSTIN", str_contains($html, 'Tax invoice #') && str_contains($html, 'GSTIN: 07AAACM1234A1Z5'), $html);
    check("$state: the state code and the kind of supply", str_contains($html, 'State code: ' . $want['code']) && str_contains($html, 'Supply: ' . $want['kind']), $html);
    check("$state: summary lines per part", $state === 'Delhi' ? (str_contains($html, 'Taxable value at CGST 9%') && str_contains($html, 'Taxable value at SGST 9%')) : str_contains($html, 'Taxable value at IGST 18%'), $html);
}

exit(harness_result());
