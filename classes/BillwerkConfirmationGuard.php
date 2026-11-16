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

    public static function isSubscriptionOf($subscription, $subHandle, $customerHandle)
    {
        return is_array($subscription)
            && isset($subscription['state'], $subscription['handle'], $subscription['customer'])
            && 'active' === $subscription['state']
            && (string) $subHandle === (string) $subscription['handle']
            && (string) $customerHandle === (string) $subscription['customer'];
    }
}
