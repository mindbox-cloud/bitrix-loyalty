<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\ORM;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;

class TransactionTable extends DataManager
{
    public static function getTableName()
    {
        return 'lbi_transaction';
    }

    /**
     * Returns entity map definition.
     *
     * @return array
     */
    public static function getMap()
    {
        return [
            new IntegerField(
                'ID',
                [
                    'autocomplete' => true,
                    'primary' => true,
                ]
            ),
            new StringField(
                'TEMP_ORDER_ID',
                [
                    'required' => true,
                    'unique' => true
                ]
            ),
            new IntegerField(
                'ORDER_ID',
                [
                    'title' => 'ORDER_ID',
                    'nullable' => true
                ]
            ),
            new StringField(
                'SITE_ID',
                [
                ]
            ),
            new DatetimeField('DATE_INSERT',
                [
                    'default_value' => function() {
                        return new \Bitrix\Main\Type\DateTime();
                    },
                ]
            ),
        ];
    }

    public static function setTmpId(string $tempOrderId, string $siteId)
    {
        $find = self::getList([
            'filter' => ['=TEMP_ORDER_ID' => $tempOrderId],
            'select' => ['*'],
        ]);

        if (!$find->fetch()) {
            return self::add([
                'TEMP_ORDER_ID' => $tempOrderId,
                'SITE_ID' => $siteId,
            ]);
        }
    }

    public static function unset(string $transactionId)
    {
        $find = self::getList([
            'filter' => ['=TEMP_ORDER_ID' => $transactionId, 'ORDER_ID' => null],
            'select' => ['*'],
        ]);

        while ($el = $find->fetch()) {
            self::delete($el['ID']);
        }
    }

    public static function setOrderId(string $tempOrderId, int $orderId)
    {
        $find = self::getList([
            'filter' => ['=TEMP_ORDER_ID' => $tempOrderId],
            'select' => ['*'],
        ]);

        if ($item = $find->fetch()) {
            return self::update(
                $item['ID'],
                [
                    'ORDER_ID' => $orderId
                ]
            );
        }
    }
}