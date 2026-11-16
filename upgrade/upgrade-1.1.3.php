<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Register the paymentOptions hook so the subscription option shows in the PS 1.7+/8/9 checkout.
 */
function upgrade_module_1_1_3($module)
{
    return $module->registerHook('paymentOptions');
}
