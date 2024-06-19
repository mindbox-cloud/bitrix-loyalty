<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;

class ViewProduct extends CBitrixComponent
{
    public function __construct(CBitrixComponent $component = null)
    {
        parent::__construct($component);

        if (!$this->loadModule()) {
            return;
        }
    }

    public function executeComponent()
    {
        $settings = \Mindbox\Loyalty\Support\SettingsFactory::create();

        $this->arResult['PRODUCT_ID'] = $this->arParams['PRODUCT_ID'];
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