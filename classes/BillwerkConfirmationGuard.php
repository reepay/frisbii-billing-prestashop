<?php

class BillwerkConfirmationGuard
{
    const PAID_INVOICE_STATES = ['authorized', 'settled'];

    public static function isPaidInvoice($invoice, $cartId)
    {
        return is_array($invoice)
            && isset($invoice['handle'], $invoice['type'], $invoice['state'])
            && 'ch' === $invoice['type']
            && (string) $cartId === (string) $invoice['handle']
            && in_array($invoice['state'], self::PAID_INVOICE_STATES, true);
    }

    public static function ownsCart($sessionCustomerId, $cartCustomerId)
    {
        return (int) $sessionCustomerId > 0 && (int) $sessionCustomerId === (int) $cartCustomerId;
    }

    public static function isActiveMethodOf($response, $customerHandle)
    {
        return is_array($response)
            && isset($response['state'], $response['customer'])
            && 'active' === $response['state']
            && (string) $customerHandle === (string) $response['customer'];
    }

    /**
     * True when the cart still holds exactly what the subscription session was created for:
     * one product line, quantity 1, a subscription product, and the plan Frisbii created.
     *
     * @param array $products result of Cart::getProducts()
     * @param array|false|null $subscriptionProduct the module's mapping row for the product (needs id_product, plan_handle)
     * @param array|null $subscription Frisbii subscription (needs plan)
     */
    public static function isCartUnchangedForSubscription($products, $subscriptionProduct, $subscription)
    {
        if (!is_array($products) || 1 !== count($products)
            || !is_array($subscriptionProduct) || !isset($subscriptionProduct['id_product'], $subscriptionProduct['plan_handle'])
            || !is_array($subscription) || !isset($subscription['plan'])) {
            return false;
        }

        $line = current($products);

        return is_array($line)
            && isset($line['id_product'], $line['cart_quantity'])
            && 1 === (int) $line['cart_quantity']
            && (int) $line['id_product'] === (int) $subscriptionProduct['id_product']
            && (string) $subscriptionProduct['plan_handle'] === (string) $subscription['plan'];
    }

    /**
     * True when the paid invoice has the amount and currency the cart requires now.
     *
     * @param mixed $invoiceAmount Frisbii invoice amount, minor units
     * @param int $expectedMinorUnits amount calculated from the cart (BillwerkChargeAmount::forCart())
     */
    public static function amountMatches($invoiceAmount, $expectedMinorUnits, $invoiceCurrency, $cartCurrency)
    {
        return (is_int($invoiceAmount) || (is_string($invoiceAmount) && ctype_digit($invoiceAmount)))
            && (int) $expectedMinorUnits > 0
            && (int) $invoiceAmount === (int) $expectedMinorUnits
            && is_string($invoiceCurrency) && is_string($cartCurrency)
            && '' !== $cartCurrency
            && 0 === strcasecmp($invoiceCurrency, $cartCurrency);
    }

    public static function isSubscriptionOf($subscription, $subHandle, $customerHandle)
    {
        return is_array($subscription)
            && isset($subscription['state'], $subscription['handle'], $subscription['customer'])
            && 'active' === $subscription['state']
            && (string) $subHandle === (string) $subscription['handle']
            && (string) $customerHandle === (string) $subscription['customer'];
    }
}
