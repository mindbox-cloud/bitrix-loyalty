<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Controllers;

use Bitrix\Main\Engine\ActionFilter\Authentication;
use Bitrix\Main\Engine\ActionFilter\Csrf;
use Mindbox\Loyalty\Support\SessionStorage;

class AdminPage extends \Bitrix\Main\Engine\Controller
{
    public function configureActions()
    {
        return [
            'get' => [
                'prefilters' => [
                    new Csrf(),
                    new Authentication()
                ]
            ]
        ];
    }

    public function getAction()
    {
        return [
            'pay' => SessionStorage::getInstance()->getPayBonuses(),
            'available' => SessionStorage::getInstance()->getOrderAvailableBonuses(),
            'total' => SessionStorage::getInstance()->getBonusesBalanceAvailable(),
            'earned' => SessionStorage::getInstance()->getOrderEarnedBonuses(),
            'promocode_error' => SessionStorage::getInstance()->getPromocodeError(),
        ];
    }
}