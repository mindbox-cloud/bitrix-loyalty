<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;

class ViewCategory extends CBitrixComponent
{
    public function __construct(CBitrixComponent $component = null)
    {
        parent::__construct($component);
    }

    public function executeComponent()
    {
        if (!$this->loadModule()) {
            return;
        }

        $settings = \Mindbox\Loyalty\Support\SettingsFactory::create();

        $this->arResult['CATEGORY_ID'] = $this->arParams['CATEGORY_ID'];
        $this->arResult['OPERATION_PREFIX'] = $settings->getWebsitePrefix();
        $this->arResult['ID_KEY'] = $settings->getExternalProductId();

        $this->includeComponentTemplate();
    }

    public function onPrepareComponentParams($arParams)
    {
        return $arParams;
    }

    private function loadModule()
    {
        try {
            return Loader::includeModule('mindbox.loyalty');
        } catch (LoaderException $e) {
            return false;
        }
    }
}