<?php

/*
* 2007-2017 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Open Software License (OSL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/osl-3.0.php
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
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2017 PrestaShop SA
*  @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

/**
 * @property Product $object
 */
class AdminProductsController extends AdminProductsControllerCore
{
    public function __construct()
    {
        parent::__construct();

        if (Configuration::get('BILLWERKSUBSCRIPTION_ENABLED')) {
            $this->_join .= 'LEFT JOIN `'._DB_PREFIX_.'billwerksubscription_product` sub_p ON (sub_p.`id_product` = a.`id_product`)';
            $this->_select .= ' ,sub_p.`plan_name` as `sub_plan`';
            $this->fields_list['sub_plan'] = [
                'title' => $this->l('Subscription plan'),
                'align' => 'text-center',
                'type' => 'bool',
                'class' => 'fixed-width-sm',
                'orderby' => false,
            ];
        }
    }
}
