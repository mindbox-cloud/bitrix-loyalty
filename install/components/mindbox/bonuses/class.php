<?php

use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Mindbox\Loyalty\Support\SessionStorage;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

class MindboxBonuses extends CBitrixComponent implements Controllerable
{
    protected $actions = [
        'calculate',
        'apply',
        'cancel',
    ];

    public function __construct(CBitrixComponent $component = null)
    {
        parent::__construct($component);

        try {
            if (!Loader::includeModule('mindbox.loyalty')) {
                ShowError(GetMessage('MB_CART_MODULE_NOT_INCLUDED', ['#MODULE#' => 'mindbox.loyalty']));

                return;
            }

            if (!Loader::includeModule('sale')) {
                ShowError(GetMessage('MB_CART_MODULE_NOT_INCLUDED', ['#MODULE#' => 'sale']));

                return;
            }

            if (!Loader::includeModule('catalog')) {
                ShowError(GetMessage('MB_CART_MODULE_NOT_INCLUDED', ['#MODULE#' => 'catalog']));

                return;
            }
        } catch (LoaderException $e) {
            ShowError($e->getMessage());

            return;
        }
    }

    public function configureActions()
    {
        $actionConfig = [];
        foreach ($this->actions as $action) {
            $actionConfig[$action] = ['prefilters' => []];
        }

        return $actionConfig;
    }

    public function calculateAction()
    {
        return [
            'bonuses' => SessionStorage::getInstance()->getPayBonuses(),
            'available' => SessionStorage::getInstance()->getOrderAvailableBonuses(),
            'total' => SessionStorage::getInstance()->getBonusesBalanceAvailable(),
            'earned' => SessionStorage::getInstance()->getOrderEarnedBonuses(),
        ];
    }


    public function applyAction($bonuses)
    {
        $bonuses = floatval($bonuses);

        SessionStorage::getInstance()->setPayBonuses($bonuses);

        return [
            'type' => 'success'
        ];
    }

    public function cancelAction()
    {
        SessionStorage::getInstance()->clearField(SessionStorage::PAY_BONUSES);

        return [
            'type' => 'success'
        ];
    }


    public function executeComponent()
    {
        $this->arResult = [
            'bonuses' => SessionStorage::getInstance()->getPayBonuses(),
            'available' => SessionStorage::getInstance()->getOrderAvailableBonuses(),
            'total' => SessionStorage::getInstance()->getBonusesBalanceAvailable(),
            'earned' => SessionStorage::getInstance()->getOrderEarnedBonuses(),
        ];

        $this->includeComponentTemplate();
    }
}
