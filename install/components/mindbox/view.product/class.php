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
        $this->arResult['PRODUCT_GROUP_ID'] = $this->arParams['PRODUCT_GROUP_ID'];
        $this->arResult['PRICE'] = $this->arParams['PRICE'];
        $this->arResult['OPERATION_PREFIX'] = $settings->getWebsitePrefix();
        $this->arResult['ID_KEY'] = $settings->getExternalProductId();

        $this->arResult['PAYLOAD'] = $this->getPayload();

        $this->includeComponentTemplate();
    }

    public function onPrepareComponentParams($arParams)
    {
        return $arParams;
    }

    public function getPayload()
    {
        $payload = [
            'operation' => $this->arResult['OPERATION_PREFIX'] . '.ViewProduct',
            'data' => [
                'viewProduct' => [

                ]
            ]
        ];

        if ($this->arResult['PRODUCT_ID']) {
            $payload['data']['viewProduct'] = [
                'product' => [
                    'ids' => [
                        $this->arResult['ID_KEY'] => $this->arResult['PRODUCT_ID']
                    ]
                ]
            ];
        }

        if ($this->arResult['PRODUCT_GROUP_ID']) {
            $payload['data']['viewProduct'] = [
                'productGroup' => [
                    'ids' => [
                        $this->arResult['ID_KEY'] => $this->arResult['PRODUCT_GROUP_ID']
                    ]
                ]
            ];
        }

        if (isset($this->arResult['PRICE'])) {
            $payload['data']['viewProduct']['price'] = $this->arResult['PRICE'];
        }

        return $payload;
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