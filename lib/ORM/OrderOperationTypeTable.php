<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\ORM;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\EnumField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;

class OrderOperationTypeTable  extends DataManager
{
    const OPERATION_TYPE_AUTH = 'AUTH';
    const OPERATION_TYPE_NOT_AUTH = 'NOT_AUTH';

    public static function getTableName(): string
    {
        return 'lbi_order_operation_type';
    }

    public static function getMap(): array
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
                'ORDER_SITE_ID',
                [
                    'required' => true,
                    'size' => 100
                ]
            ),
            new EnumField(
                'OPERATION_TYPE',
                [
                    'required' => true,
                    'values' => [self::OPERATION_TYPE_AUTH, self::OPERATION_TYPE_NOT_AUTH]
                ]
            )
        ];
    }

    public static function setTypeOrder(string $orderSiteId, string $type)
    {
        $find = self::getList([
            'filter' => ['=ORDER_SITE_ID' => $orderSiteId],
            'select' => ['*'],
        ]);

        if (!$find->fetch()) {
            return self::add([
                'ORDER_SITE_ID' => $orderSiteId,
                'OPERATION_TYPE' => $type,
            ]);
        }
    }

    public static function getOrderType(string $orderSiteId): string
    {
        $find = self::getList([
            'filter' => ['=ORDER_SITE_ID' => $orderSiteId],
            'select' => ['*'],
        ]);

        if ($orderType = $find->fetch()) {
            return $orderType['OPERATION_TYPE'];
        }

        return self::OPERATION_TYPE_NOT_AUTH;
    }
}