<?php


use Mindbox\Loyalty\Support\SessionStorage;

class MindboxBonuses extends CBitrixComponent implements \Bitrix\Main\Engine\Contract\Controllerable
{

    public function configureActions()
    {
        return [
            'get' => [
                'prefilters' => [
                    new \Bitrix\Main\Engine\ActionFilter\Csrf(),
                    new \Bitrix\Main\Engine\ActionFilter\Authentication(true),
                    new \Bitrix\Main\Engine\ActionFilter\HttpMethod([
                        \Bitrix\Main\Engine\ActionFilter\HttpMethod::METHOD_POST
                    ])
                ],
                'postfilters' => []
            ],
            'apply' => [
                'prefilters' => [
                    new \Bitrix\Main\Engine\ActionFilter\Csrf(),
                    new \Bitrix\Main\Engine\ActionFilter\Authentication(true),
                    new \Bitrix\Main\Engine\ActionFilter\HttpMethod([
                        \Bitrix\Main\Engine\ActionFilter\HttpMethod::METHOD_POST
                    ])
                ],
                'postfilters' => []
            ],
            'cancel' => [
                'prefilters' => [
                    new \Bitrix\Main\Engine\ActionFilter\Csrf(),
                    new \Bitrix\Main\Engine\ActionFilter\Authentication(true),
                    new \Bitrix\Main\Engine\ActionFilter\HttpMethod([
                        \Bitrix\Main\Engine\ActionFilter\HttpMethod::METHOD_POST
                    ])
                ],
                'postfilters' => []
            ],
        ];
    }

    public function executeComponent()
    {
        global $USER;
        if ($USER->IsAuthorized()) {
            $this->arResult = [
                'bonuses' => SessionStorage::getInstance()->getPayBonuses(),
                'available' => SessionStorage::getInstance()->getOrderAvailableBonuses(),
                'total' => SessionStorage::getInstance()->getBonusesBalanceAvailable(),
                'earned' => SessionStorage::getInstance()->getOrderEarnedBonuses(),
            ];
        }
        $this->includeComponentTemplate();
    }

    public function getAction()
    {
        return [
            'bonuses' => SessionStorage::getInstance()->getPayBonuses(),
            'available' => SessionStorage::getInstance()->getOrderAvailableBonuses(),
            'total' => SessionStorage::getInstance()->getBonusesBalanceAvailable(),
            'earned' => SessionStorage::getInstance()->getOrderEarnedBonuses(),
        ];
    }

    public function applyAction(int $bonus)
    {
        $bonus = floatval($bonus);
        SessionStorage::getInstance()->setPayBonuses($bonus);
        return [];
    }

    public function cancelAction()
    {
        SessionStorage::getInstance()->clearField(SessionStorage::PAY_BONUSES);
        return [];
    }

}