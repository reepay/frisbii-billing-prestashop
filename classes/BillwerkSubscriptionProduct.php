<?php

class BillwerkSubscriptionProduct extends ObjectModel
{
    public $id;
    public $id_shop;
    public $id_product;
    public $plan_handle;
    public $plan_name;
    public $created_at;
    public $updated_at;
    public const TABLE_NAME = 'billwerksubscription_product';
    public static $definition = [
        'table' => self::TABLE_NAME,
        'primary' => 'id',
        'fields' => [
            'id_shop' => ['type' => self::TYPE_INT, 'validate' => 'isInt'],
            'id_product' => ['type' => self::TYPE_INT, 'validate' => 'isInt'],
            'plan_handle' => ['type' => self::TYPE_STRING, 'validate' => 'isString'],
            'plan_name' => ['type' => self::TYPE_STRING, 'validate' => 'isString'],
            'created_at' => ['type' => self::TYPE_DATE, 'validate' => 'isDateFormat'],
            'updated_at' => ['type' => self::TYPE_DATE, 'validate' => 'isDateFormat'],
        ],
    ];

    public static function getByShopProductId($id_product, $id_shop)
    {
        $query = new DbQuery();
        $query->from(self::TABLE_NAME);
        $query->where('`id_product` = '.(int) $id_product);
        $query->where('`id_shop` = '.(int) $id_shop);

        return Db::getInstance()->getRow($query);
    }

    public static function saveProduct($shopId, $planHandle, $planName, $productId)
    {
        $model = new self();
        $subscriptionProduct = self::getByShopProductId($productId, $shopId);

        if ($subscriptionProduct) {
            $model->id = $subscriptionProduct['id'];
        }

        $model->id_product = $productId;
        $model->id_shop = $shopId;
        $model->plan_handle = $planHandle;
        $model->plan_name = $planName;
        $model->created_at = date('Y-m-d H:i:s');
        $model->updated_at = date('Y-m-d H:i:s');
        $model->save();
    }

    public static function removeProduct($id_shop, $id_product)
    {
        $sql = 'DELETE FROM '._DB_PREFIX_.'billwerksubscription_product  WHERE `id_shop`='.
                (int) $id_shop.' AND `id_product`= '.(int) $id_product;

        return Db::getInstance()->execute($sql);
    }
}
