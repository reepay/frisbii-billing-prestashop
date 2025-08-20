<?php

class BillwerkSubscriptionSubscriptions extends ObjectModel
{
    public $id;

    public $id_shop;
    public $id_cart;
    public $sub_handle;
    public $plan_handle;
    public $customer_handle;
    public $presta_customer_id;

    public static $definition = [
        'table' => 'billwerksubscription_subscriptions',
        'primary' => 'id',
        'fields' => [
            'id_shop' => ['type' => self::TYPE_INT, 'validate' => 'isInt'],
            'id_cart' => ['type' => self::TYPE_INT, 'validate' => 'isInt'],
            'id_product' => ['type' => self::TYPE_INT, 'validate' => 'isInt'],
            'sub_handle' => ['type' => self::TYPE_STRING, 'validate' => 'isString'],
            'plan_handle' => ['type' => self::TYPE_STRING, 'validate' => 'isString'],
            'customer_handle' => ['type' => self::TYPE_STRING, 'validate' => 'isString'],
            'presta_customer_id' => ['type' => self::TYPE_INT, 'validate' => 'isInt'],
            'created_at' => ['type' => self::TYPE_DATE, 'validate' => 'isDateFormat'],
            'updated_at' => ['type' => self::TYPE_DATE, 'validate' => 'isDateFormat'],
        ],
    ];

    public static function getById($sub_handle)
    {
        $query = new DbQuery();
        $query->from('billwerksubscription_subscriptions');
        $query->where('`sub_handle` = "'.$sub_handle.'"');

        return Db::getInstance()->getRow($query);
    }

    public static function getAll($id_shop, $presta_customer_id)
    {
        $query = new DbQuery();
        $query->from('billwerksubscription_subscriptions');
        $query->where('`id_shop` = '.$id_shop);
        $query->where('`presta_customer_id` = '.$presta_customer_id);

        return Db::getInstance()->query($query)->fetchAll();
    }

    public static function getByShopIdAndProductId($id_product, $id_shop)
    {
        $query = new DbQuery();
        $query->from('billwerksubscription_subscriptions');
        $query->where('`id_shop` = '.$id_shop);
        $query->where('`id_product` = '.$id_product);

        return Db::getInstance()->query($query)->fetchAll();
    }

    public static function saveSubscription($id_shop, $id_cart, $id_product, $sub_handle, $plan_handle, $customer_handle, $presta_customer_id)
    {
        $model = new self();
        $model->id_shop = $id_shop;
        $model->id_cart = $id_cart;
        $model->id_product = $id_product;
        $model->sub_handle = $sub_handle;
        $model->plan_handle = $plan_handle;
        $model->customer_handle = $customer_handle;
        $model->presta_customer_id = $presta_customer_id;
        $model->created_at = date('Y-m-d H:i:s');
        $model->updated_at = date('Y-m-d H:i:s');
        $model->save();
    }
}
