<?php

/**
 * The amount of the Frisbii charge session for a mixed cart (non-subscription products plus shipping).
 * validation.php sends it when the session is created, and confirmation.php compares it with the paid
 * invoice when the customer comes back, so both sides must use this one calculation.
 */
class BillwerkChargeAmount
{
    /**
     * @param Cart $cart
     *
     * @return int amount in minor units (for example cents)
     */
    public static function forCart($cart)
    {
        // The cart total depends on the context currency, and it must be the cart's currency.
        Context::getContext()->currency = new Currency((int) $cart->id_currency);

        $non_subscription_products = [];
        foreach ($cart->getProducts() as $product) {
            if (!BillwerkSubscriptionProduct::getByShopProductId($product['id_product'], $cart->id_shop)) {
                $non_subscription_products[] = $product;
            }
        }

        $order_total_without_subscription_product = $cart->getOrderTotal(true, Cart::BOTH, $non_subscription_products, true);
        $total = $order_total_without_subscription_product + $cart->getTotalShippingCost();

        return (int) round($total * 100);
    }
}
