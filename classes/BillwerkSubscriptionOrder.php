<?php

class BillwerkSubscriptionOrder extends ObjectModel
{
    public $id;
    public $id_shop;
    public $id_order;
    public $sub_handle;
    public $inv_handle;
    public $created_at;
    public $updated_at;
    public $order_type;
    public const TABLE_NAME = 'billwerksubscription_order';

    public static $definition = [
        'table' => self::TABLE_NAME,
        'primary' => 'id',
        'fields' => [
            'id_shop' => ['type' => self::TYPE_INT, 'validate' => 'isInt'],
            'id_order' => ['type' => self::TYPE_INT, 'validate' => 'isInt'],
            'sub_handle' => ['type' => self::TYPE_STRING, 'validate' => 'isString'],
            'inv_handle' => ['type' => self::TYPE_STRING, 'validate' => 'isString'],
            'order_type' => ['type' => self::TYPE_STRING, 'validate' => 'isString'],
            'created_at' => ['type' => self::TYPE_DATE, 'validate' => 'isDateFormat'],
            'updated_at' => ['type' => self::TYPE_DATE, 'validate' => 'isDateFormat'],
        ],
    ];

    public static function saveOrderSubscription($id_shop, $id_order, $sub_handle, $inv_handle, $order_type = 'renewal')
    {
        $model = new self();
        $model->id_shop = $id_shop;
        $model->id_order = $id_order;
        $model->sub_handle = $sub_handle;
        $model->inv_handle = $inv_handle;
        $model->order_type = $order_type;
        $model->created_at = date('Y-m-d H:i:s');
        $model->updated_at = date('Y-m-d H:i:s');
        $model->save();
    }

    public static function getByShppIdOrderId($id_shop, $id_order)
    {
        $query = new DbQuery();
        $query->from(self::TABLE_NAME);
        $query->where('`id_shop` = '.(int) $id_shop);
        $query->where('`id_order` = '.(int) $id_order);

        return Db::getInstance()->getRow($query);
    }

    /**
     * RSCC-002: true when this invoice handle was already used for an order, so a repeated
     * (or replayed) webhook event does not create a second order for it.
     */
    public static function existsByInvoiceHandle($inv_handle)
    {
        if (!is_string($inv_handle) || $inv_handle === '') {
            return false;
        }
        $query = new DbQuery();
        $query->from(self::TABLE_NAME);
        $query->where('`inv_handle` = "'.pSQL($inv_handle).'"');

        return (bool) Db::getInstance()->getRow($query);
    }
}
