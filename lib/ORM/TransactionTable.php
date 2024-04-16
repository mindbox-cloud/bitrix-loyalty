<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\ORM;

use Bitrix\Main\ORM\Data\DataManager;
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
                'TRANSACTION_ID',
                [
                    'required' => true,
                ]
            )
        ];
    }

    public static function set(string $transactionId)
    {
        $find = self::getList([
            'filter' => ['=TRANSACTION_ID' => $transactionId],
            'select' => ['*'],
        ]);

        if (!$find->fetch()) {
            return self::add([
                'TRANSACTION_ID' => $transactionId
            ]);
        }
    }

    public static function unset(string $transactionId)
    {
        $find = self::getList([
            'filter' => ['=TRANSACTION_ID' => $transactionId],
            'select' => ['*'],
        ]);

        while ($el = $find->fetch()) {
            self::delete($el['ID']);
        }
    }
}