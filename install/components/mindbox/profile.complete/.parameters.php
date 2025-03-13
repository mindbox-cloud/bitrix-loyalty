<?php

use Bitrix\Main\Localization\Loc;

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

Loc::loadMessages(__FILE__);

$arComponentParameters = [
    "PARAMETERS" => [
        "REDIRECT_PAGE" => Array(
            "NAME" => Loc::getMessage("REDIRECT_PAGE"),
            "TYPE" => "STRING",
            "MULTIPLE" => "N",
            "DEFAULT" => "",
            "COLS" => 25,
        ),
    ]
];