<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) {
    die();
}

use Bitrix\Main\Loader;
use Bitrix\Currency;

if (!Loader::includeModule('iblock'))
{
    return;
}
Loader::includeModule('catalog');

$arComponentParameters = [
    "GROUPS" => [
        "LOYALTY" => [
            "SORT" => 100,
            "NAME" => 'Лояльность',
        ],
        "HISTORY_BONUSES" => [
            "SORT" => 200,
            "NAME" => 'История бонусов',
        ],
    ],
    "PARAMETERS" => [
        'CURRENCY_ID' => [
            'PARENT' => 'BASE',
            'NAME' => 'Валюта',
            'TYPE' => 'LIST',
            'VALUES' => Currency\CurrencyManager::getCurrencyList(),
            'DEFAULT' => Currency\CurrencyManager::getBaseCurrency(),
            "ADDITIONAL_VALUES" => "N",
        ],
        "HISTORY_PAGE_SIZE" => [
            "PARENT" => "HISTORY_BONUSES",
            "NAME" => "Количество на страницы",
            "TYPE" => "NUMBER",
            "MULTIPLE" => "N",
            "DEFAULT" => "20",
        ],
        "HISTORY_DATE_FORMAT" => CIBlockParameters::GetDateFormat('Формат даты', "HISTORY_BONUSES"),
        'LEVEL_NAMES_LOYALTY' => [
            "PARENT" => 'LOYALTY',
            "NAME" => 'Названия уровней',
            "TYPE" => "STRING",
            "MULTIPLE" => "Y",
            "ADDITIONAL_VALUES" => "Y",
            "VALUES" => [
            ],
        ],
        'SEGMETS_LOYALTY' => [
            "PARENT" => 'LOYALTY',
            "NAME" => 'Идентификаторы сегментов',
            "TYPE" => "STRING",
            "MULTIPLE" => "Y",
            "ADDITIONAL_VALUES" => "Y",
            "VALUES" => [
            ],
        ],
        'LEVEL_PRICES_LOYALTY' => [
            "PARENT" => 'LOYALTY',
            "NAME" => 'Ценовые уровни',
            "TYPE" => "STRING",
            "MULTIPLE" => "Y",
            "ADDITIONAL_VALUES" => "Y",
            "VALUES" => [
            ],
        ]
    ],
];