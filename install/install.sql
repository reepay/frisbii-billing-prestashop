CREATE TABLE IF NOT EXISTS  `PREFIX_billwerksubscription_product`
(
     `id` int(11) NOT NULL AUTO_INCREMENT,
     `id_shop` int(11) NOT NULL,
     `id_product` int(11) NOT NULL,
     `plan_handle` varchar(255) NOT NULL,
     `plan_name` varchar(255) NOT NULL,
     `created_at` datetime DEFAULT NULL,
     `updated_at` datetime DEFAULT NULL,
      PRIMARY KEY (`id`) USING BTREE
) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `PREFIX_billwerksubscription_subscriptions`
(
     `id` int(11) NOT NULL AUTO_INCREMENT,
     `id_shop` int(11) NOT NULL,
     `id_cart` int(11) NOT NULL,
     `id_product` int(11) NOT NULL,
     `sub_handle` varchar(255) NOT NULL,
     `plan_handle` varchar(255) NOT NULL,
     `customer_handle` varchar(255) NOT NULL,
     `presta_customer_id` int(11) NOT NULL,
     `created_at` datetime DEFAULT NULL,
     `updated_at` datetime DEFAULT NULL,
      PRIMARY KEY (`id`) USING BTREE
) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `PREFIX_billwerksubscription_order`
(
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `id_shop` int(11) NOT NULL,
    `id_order` int(11) NOT NULL,
    `sub_handle` varchar(255) NOT NULL,
    `inv_handle` varchar(255) NOT NULL,
    `order_type` varchar(12),
    `created_at` datetime DEFAULT NULL,
    `updated_at` datetime DEFAULT NULL,
    PRIMARY KEY (`id`) USING BTREE
) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;