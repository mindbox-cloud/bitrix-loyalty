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
        "FORM_NAME" => Array(
            "NAME" => Loc::getMessage("FORM_NAME"),
            "TYPE" => "STRING",
            "MULTIPLE" => "N",
            "DEFAULT" => "mindbox-profile-complete",
            "COLS" => 25,
        ),
    ]
];