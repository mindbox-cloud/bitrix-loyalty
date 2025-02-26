<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$arComponentDescription = [
        'NAME' => Loc::getMessage('MINDBOX_COMPONENT_PROMOCODE_NAME'),
        'DESCRIPTION' => Loc::getMessage('MINDBOX_COMPONENT_PROMOCODE_DESCRIPTION'),
        'SORT' => 10,
        'PATH' => [
                'ID' => 'mindbox',
                'NAME' => 'mindbox',
        ],
];
