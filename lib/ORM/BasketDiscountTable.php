<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\ORM;

use Bitrix\Main;
use Bitrix\Main\ORM\Data\DataManager;

class BasketDiscountTable extends DataManager
{
    public static function getTableName()
    {
        return 'lbi_basket_discount';
    }

    public static function getMap()
    {
        return [
            'ID' => new Main\Entity\IntegerField('ID', [
                'primary' => true,
                'autocomplete' => true,
                'title' => 'ID'
            ]),
            'BASKET_ITEM_ID' => new Main\Entity\IntegerField('BASKET_ITEM_ID', [
                'title' => 'BASKET_ITEM_ID',
                'required' => true
            ]),
            'DISCOUNTED_PRICE' => new Main\Entity\FloatField('DISCOUNTED_PRICE', [
                'title' => 'DISCOUNTED_PRICE'
            ]),
            'BASKET' => new Main\Entity\ReferenceField(
                'BASKET',
                '\Bitrix\Sale\Internals\Basket',
                [
                    '=this.BASKET_ITEM_ID' => 'ref.ID'
                ]
            ),
        ];
    }

    public static function set(int $basketId, float $price)
    {
        $find = self::getList([
            'filter' => ['=BASKET_ITEM_ID' => $basketId],
            'select' => ['*'],
        ]);

        if ($element = $find->fetch()) {
            return self::update($element['ID'], [
                'DISCOUNTED_PRICE' => $price
            ]);
        } else {
            return self::add([
                'BASKET_ITEM_ID' => $basketId,
                'DISCOUNTED_PRICE' => $price
            ]);
        }
    }
}