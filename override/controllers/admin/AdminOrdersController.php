<?php

/**
 * 2007-2015 PrestaShop.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2007-2015 PrestaShop SA
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */
class AdminOrdersController extends AdminOrdersControllerCore
{
    public function __construct()
    {
        parent::__construct();

        if (Configuration::get('BILLWERKSUBSCRIPTION_ENABLED')) {
            $this->fields_list['frisbii_inv'] = [
                'title' => $this->l('Frisbii Order Type'),
                'align' => 'text-center',
                'callback' => 'frisbiiSubscriptionOrderType',
                'orderby' => false,
                'search' => false,
                'remove_onclick' => true,
            ];
            $this->_select .= ',a.id_order AS billwerk_sub_handle, a.id_order as frisbii_inv,';

            $this->fields_list['billwerk_sub_handle'] = [
                'title' => $this->l('Frisbii Subscription Handle'),
                'align' => 'text-center',
                'callback' => 'frisbiiSubscriptionHandle',
                'orderby' => false,
                'search' => false,
                'remove_onclick' => true,
            ];
            $this->_select .= 'a.id_order AS billwerk_sub_handle,';
        }
        AdminController::__construct();
    }

    public function frisbiiSubscriptionHandle($id_order)
    {
        $query = new DbQuery();
        $query->from('billwerksubscription_order');
        $query->where('`id_shop` = '.(int) $this->context->shop->id);
        $query->where('`id_order` = '.(int) $id_order);
        $result = Db::getInstance()->getRow($query);
        if ($result) {
            $link = "https://app.frisbii.com/#/rp/subscriptions/subscription/{$result['sub_handle']}";
            return "<a href = {$link} target='_blank'>".$result['sub_handle'].'</a>';
        }
    }

    public function frisbiiSubscriptionOrderType($id_order)
    {
        $query = new DbQuery();
        $query->from('billwerksubscription_order');
        $query->where('`id_shop` = '.(int) $this->context->shop->id);
        $query->where('`id_order` = '.(int) $id_order);
        $result = Db::getInstance()->getRow($query);
        if ($result) {
            return $result['order_type'];
        }
    }
}
