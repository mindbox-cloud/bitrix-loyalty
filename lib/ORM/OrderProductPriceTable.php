<?php

namespace Mindbox\Loyalty\ORM;

use Bitrix\Main\Entity\FloatField;
use Bitrix\Main\Entity\IntegerField;
use Bitrix\Main\ORM\Data\DataManager;

class OrderProductPriceTable extends DataManager
{
    public static function getTableName()
    {
        return 'lbi_order_product_price';
    }

    public static function getMap()
    {
        return [
            'ID' => new IntegerField('ID', [
                'primary' => true,
                'autocomplete' => true,
                'title' => 'ID'
            ]),
            'ORDER_ID' => new IntegerField('ORDER_ID', [
                'title' => 'ORDER_ID',
            ]),
            'PRODUCT_ID' => new IntegerField('PRODUCT_ID', [
                'title' => 'PRODUCT_ID',
            ]),
            'PRICE_PER_ITEM' => new FloatField('PRICE_PER_ITEM', [
                'title' => 'PRICE_PER_ITEM',
            ])
        ];
    }
}