<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) {
    die();
}

use Bitrix\Main\Loader;
use Bitrix\Currency;
use Bitrix\Main\Localization\Loc;

if (!Loader::includeModule('iblock'))
{
    return;
}
Loader::includeModule('catalog');

$arComponentParameters = [
    "GROUPS" => [
        "LOYALTY" => [
            "SORT" => 100,
            "NAME" => Loc::getMessage('LOYALTY'),
        ],
        "HISTORY_BONUSES" => [
            "SORT" => 200,
            "NAME" => Loc::getMessage('HISTORY_BONUSES'),
        ],
    ],
    "PARAMETERS" => [
        "HISTORY_ENABLE" => [
            "PARENT" => "HISTORY_BONUSES",
            "NAME" => Loc::getMessage('HISTORY_ENABLE'),
            "TYPE" => "CHECKBOX",
            "MULTIPLE" => "N",
            "DEFAULT" => "N",
        ],
        'CURRENCY_ID' => [
            'PARENT' => 'BASE',
            'NAME' => Loc::getMessage('CURRENCY_ID'),
            'TYPE' => 'LIST',
            'VALUES' => Currency\CurrencyManager::getCurrencyList(),
            'DEFAULT' => Currency\CurrencyManager::getBaseCurrency(),
            "ADDITIONAL_VALUES" => "N",
        ],
        "HISTORY_PAGE_SIZE" => [
            "PARENT" => "HISTORY_BONUSES",
            "NAME" => Loc::getMessage('HISTORY_PAGE_SIZE'),
            "TYPE" => "NUMBER",
            "MULTIPLE" => "N",
            "DEFAULT" => "20",
        ],
        "HISTORY_DATE_FORMAT" => CIBlockParameters::GetDateFormat(Loc::getMessage('HISTORY_DATE_FORMAT'), "HISTORY_BONUSES"),
        "LOYALTY_ENABLE" => [
            "PARENT" => "HISTORY_BONUSES",
            "NAME" => Loc::getMessage('LOYALTY_ENABLE'),
            "TYPE" => "CHECKBOX",
            "MULTIPLE" => "N",
            "DEFAULT" => "N",
        ],
        'LEVEL_NAMES_LOYALTY' => [
            "PARENT" => 'LOYALTY',
            "NAME" => Loc::getMessage('LEVEL_NAMES_LOYALTY'),
            "TYPE" => "STRING",
            "MULTIPLE" => "Y",
            "ADDITIONAL_VALUES" => "Y",
            "VALUES" => [
            ],
        ],
        'SEGMETS_LOYALTY' => [
            "PARENT" => 'LOYALTY',
            "NAME" => Loc::getMessage('SEGMETS_LOYALTY'),
            "TYPE" => "STRING",
            "MULTIPLE" => "Y",
            "ADDITIONAL_VALUES" => "Y",
            "VALUES" => [
            ],
        ],
        'LEVEL_PRICES_LOYALTY' => [
            "PARENT" => 'LOYALTY',
            "NAME" => Loc::getMessage('LEVEL_PRICES_LOYALTY'),
            "TYPE" => "STRING",
            "MULTIPLE" => "Y",
            "ADDITIONAL_VALUES" => "Y",
            "VALUES" => [
            ],
        ]
    ],
];