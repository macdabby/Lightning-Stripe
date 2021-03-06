<?php

namespace lightningsdk\checkout_stripe\Connectors;

use lightningsdk\core\Tools\Configuration;
use lightningsdk\core\Tools\Template;
use lightningsdk\core\View\JS;
use lightningsdk\checkout\Handlers\Payment;
use lightningsdk\checkout\Model\Order;
use lightningsdk\checkout_stripe\StripeClient;

class Checkout extends Payment {
    public static function init() {
        JS::startup('lightning.modules.stripe.init();', ['lightningsdk/checkout-stripe' => 'Stripe.js']);
        JS::set('modules.stripe.public', Configuration::get('stripe.public'));
        if (Configuration::get('modules.stripe.use_plaid')) {
            JS::set('modules.plaid.public_key', Configuration::get('modules.plaid.public_key'));
        }
        JS::set('modules.checkout.handler', 'lightning.modules.stripe.pay');
    }

    public static function printPlan($id) {
        $stripe = new StripeClient();
        $subscription = $stripe->getPlan($id);

        return '$' . number_format($subscription['amount']/100, 2) . ' per ' . $subscription['interval'];
    }

    public function isConfigured() {
        $config = Configuration::get('modules.stripe');
        return !empty($config);
    }

    public function getDescription() {
        return 'Pay with Visa, MasterCard, Discover or American Express';
    }

    public function getTitle() {
        return 'Credit card';
    }

    public function getLogo() {
        return '/images/checkout/logos/stripe.png';
    }

    public function getPage(Order $cart) {
        if ($cart->hasSubscription()) {
            return ['payment-source', 'Stripe'];
        } else {
            JS::set('modules.stripe.public', Configuration::get('stripe.public'));
            JS::startup('lightning.modules.stripe.initElementsCard()', ['lightningsdk/checkout-stripe' => 'Stripe.js']);
            $order = Order::loadBySession();
            JS::set('modules.checkout.cart', [
                'id' => $order->id,
                'amount' => intval($order->getTotal() * 100),
                'name' => $cart->requiresShippingAddress() ? $cart->getShippingAddress()->name : '',
            ]);

            $user = $order->getUser();
            Template::getInstance()->set('email', !empty($user) ? $user->email : '');

            return ['checkout-payment', 'lightningsdk/checkout-stripe'];
        }
    }
}
