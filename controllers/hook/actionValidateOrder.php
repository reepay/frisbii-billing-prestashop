<?php

class BillwerkSubscriptionActionValidateOrderController
{
    public function __construct($module, $file, $path)
    {
        $this->file = $file;
        $this->module = $module;
        $this->context = Context::getContext();
        $this->_path = $path;
    }

    public function run($params)
    {
        $order = $params['order'];
        if ('billwerksubscription' == $order->module) {
            $sub_id = Tools::getValue('subscription');
            $inv_handle = Tools::getValue('invoice');

            if ('webhook' == Tools::getValue('controller')) {
                $content = Tools::file_get_contents('php://input');
                $request_body = json_decode($content, true);

                // placing order on subscription_renewal event
                // POST request to the webhook controller
                if (isset($request_body['event_type']) && 'subscription_renewal' == $request_body['event_type']) {
                    $sub_id = $request_body['subscription'];
                    $inv_handle = $request_body['invoice'];
                }
                $order_type = 'renewal';
            }

            $t = Tools::getValue('t');

            if ('ch' == $t) {
                $order_type = 'mixed';
            }

            if ('s' == $t) {
                // set order payment to 0
                $payments = $order->getOrderPayments();
                $payment['id'] = current($payments);
                $orderPayment = new OrderPayment($payment['id']->id);
                $orderPayment->amount = 0;
                $orderPayment->save();

                // set shipping costs to 0
                $id_order_carrier = $order->getIdOrderCarrier();
                $order_carrier = new OrderCarrier($id_order_carrier);
                $order_carrier->shipping_cost_tax_incl = 0;
                $order_carrier->shipping_cost_tax_excl = 0;
                $order_carrier->save();

                // set complete order total to 0
                $order->total_paid = 0;
                $order->total_paid_tax_incl = 0;
                $order->total_paid_tax_excl = 0;
                $order->total_paid_real = 0;
                $order->total_paid_products = 0;
                $order->total_products_wt = 0;
                $order->total_shipping_tax_incl = 0;
                $order->total_shipping_tax_excl = 0;
                $order->save();

                $order_type = 'subscription';
            }

            $cart = $params['cart'];

            // if subscription not trial we need to re stock subscription item
            $order_details_list = $order->getOrderDetailList();
            foreach ($order_details_list as $order_detail) {
                $subscription_product = BillwerkSubscriptionProduct::getByShopProductId($order_detail['product_id'], $cart->id_shop);
                if ($subscription_product) {
                    $order_detail_obj = new OrderDetailCore($order_detail['id_order_detail']);
                    $order_detail_obj->unit_price_tax_incl = 0;
                    $order_detail_obj->unit_price_tax_excl = 0;
                    $order_detail_obj->save();
                    break;
                }
            }

            StockAvailable::updateQuantity(
                $order_detail['product_id'],
                $order_detail['product_attribute_id'],
                1,
                $cart->id_shop
            );

            BillwerkSubscriptionOrder::saveOrderSubscription($this->context->shop->id, $order->id, $sub_id, $inv_handle, $order_type);
        }
    }
}
