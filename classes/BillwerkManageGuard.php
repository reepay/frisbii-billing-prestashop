<?php

/**
 * FP-35: rules for the manage-subscription page. Pure functions, no PrestaShop needed, so they can be tested on the CLI.
 */
class BillwerkManageGuard
{
    const ACTIONS = ['pause', 'activate', 'cancel', 'uncancel', 'change-payment'];

    /** Who started the current hold or cancel: set only when the customer did it in this shop. */
    const ORIGIN_HOLD = 'on_hold';
    const ORIGIN_CANCEL = 'cancelled';

    public static function isKnownAction($action)
    {
        return is_string($action) && in_array($action, self::ACTIONS, true);
    }

    public static function isValidToken($sent, $expected)
    {
        return is_string($sent) && is_string($expected) && '' !== $expected && hash_equals($expected, $sent);
    }

    /**
     * @param string $action one of ACTIONS
     * @param mixed $subscription Frisbii subscription array
     * @param string|null $origin stored ORIGIN_* value for this handle
     * @param bool $holdEnabled BILLWERKSUBSCRIPTION_ENABLE_ON_HOLD
     * @param bool $cancelEnabled BILLWERKSUBSCRIPTION_ENABLE_CANCEL
     *
     * @return string|null null when allowed, otherwise the reason (for the log, not for the page)
     */
    public static function refusal($action, $subscription, $origin, $holdEnabled, $cancelEnabled)
    {
        if (!self::isKnownAction($action)) {
            return 'unknown action';
        }
        if (!is_array($subscription) || !isset($subscription['state'], $subscription['is_cancelled'])
            || !is_bool($subscription['is_cancelled'])) {
            return 'the subscription state is unknown';
        }

        $state = $subscription['state'];
        $cancelled = $subscription['is_cancelled'];
        $inTrial = isset($subscription['in_trial']) && true === $subscription['in_trial'];

        switch ($action) {
            case 'pause':
                if (!$holdEnabled) {
                    return 'pause is switched off in the shop settings';
                }
                if ($inTrial) {
                    return 'a trial subscription cannot be paused';
                }

                return 'active' === $state && !$cancelled ? null : 'only an active subscription can be paused';
            case 'cancel':
                if (!$cancelEnabled) {
                    return 'cancel is switched off in the shop settings';
                }

                return 'active' === $state && !$cancelled ? null : 'only an active subscription can be cancelled';
            case 'activate':
                if ('on_hold' !== $state) {
                    return 'only a subscription on hold can be activated';
                }

                return self::ORIGIN_HOLD === $origin ? null : 'the hold was not started by the customer';
            case 'uncancel':
                if (!$cancelled) {
                    return 'only a cancelled subscription can be restarted';
                }

                return self::ORIGIN_CANCEL === $origin ? null : 'the cancel was not started by the customer';
            default: // change-payment
                return 'active' === $state && !$cancelled ? null : 'only an active subscription can change payment';
        }
    }
}
