<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\ORM;

use Bitrix\Main;
use Bitrix\Main\ORM\Data\DataManager;

class DeliveryDiscountTable extends DataManager
{
    public static function getTableName()
    {
        return 'lbi_delivery_discount';
    }

    public static function getMap()
    {
        return [
            'ID' => new Main\Entity\IntegerField('ID', [
                'primary' => true,
                'autocomplete' => true,
                'title' => 'ID'
            ]),
            'DELIVERY_ID' => new Main\Entity\IntegerField('DELIVERY_ID', [
                'title' => 'DELIVERY_ID',
            ]),
            'ORDER_ID' => new Main\Entity\IntegerField('ORDER_ID', [
                'title' => 'ORDER_ID',
                'nullable' => true
            ]),
            'FUSER_ID' => new Main\Entity\IntegerField('FUSER_ID', [
                'title' => 'FUSER_ID',
                'nullable' => true
            ]),
            'DISCOUNTED_PRICE' => new Main\Entity\FloatField('DISCOUNTED_PRICE', [
                'title' => 'DISCOUNTED_PRICE'
            ]),
        ];
    }

    public static function getRowByFilter(array $filter)
    {
        if (empty(array_values($filter))) {
            return false;
        }

        $iterator = self::getList([
            'filter' => $filter,
            'limit' => 1,
            'select' => ['*']
        ]);

        return $iterator->fetch();
    }

    public static function deleteByFilter(array $filter)
    {
        if (empty(array_values($filter))) {
            return;
        }

        $iterator = self::getList([
            'filter' => $filter,
            'select' => ['ID']
        ]);

        while ($row = $iterator->fetch()) {
            self::delete($row['ID']);
        }
    }
}