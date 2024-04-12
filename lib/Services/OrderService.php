<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Services;

use Bitrix\Main\Context;
use Bitrix\Sale\Order;
use Mindbox\Loyalty\Helper;
use Mindbox\Loyalty\Models\Customer;
use Mindbox\Loyalty\Models\OrderMindbox;
use Mindbox\Loyalty\Operations\CreateUnauthorizedOrder;
use Mindbox\Loyalty\Support\SettingsFactory;

class OrderService
{
    private static $storage;
    private \Bitrix\Main\DI\ServiceLocator $serviceLocator;

    public function __construct()
    {
        if (self::$storage === null) {
            self::$storage = new \WeakMap();
        }

        $this->serviceLocator = \Bitrix\Main\DI\ServiceLocator::getInstance();
    }

    public function saveOrder(Order $order)
    {
        if (Context::getCurrent()->getRequest()->isAdminSection()) {
            $orderResponseDTO = $this->saveOrderAdmin($order);
        } elseif (Helper::isUserAuthorized((int) $order->getField('USER_ID'))) {
            $orderResponseDTO = $this->saveAuthorizedOrder($order);
        } else {
            $orderResponseDTO = $this->saveUnauthorizedOrder($order);
        }
    }

    private function saveOrderAdmin(Order $order)
    {

    }

    private function saveAuthorizedOrder(Order $order)
    {

    }

    private function saveUnauthorizedOrder(Order $order)
    {
        $settings = SettingsFactory::createBySiteId($order->getSiteId());

        $mindboxOrder = new OrderMindbox($order, $settings);
        $customer = new Customer($order->getUserId());

        /** @var CreateUnauthorizedOrder $createUnauthorizedOrder */
        $createUnauthorizedOrder = $this->serviceLocator->get('mindboxLoyalty.createUnauthorizedOrder');

        $customerDTO = new \Mindbox\DTO\V3\Requests\CustomerRequestDTO();
        $customerDTO->setIds($customer->getIds());

        $orderData = $mindboxOrder->getData();

        $DTO = new \Mindbox\DTO\V3\Requests\PreorderRequestDTO();
        $DTO->setOrder($orderData);
        $DTO->setCustomer($customerDTO);

        $transactionId = self::getTransactionId($order);

        return $createUnauthorizedOrder->execute($DTO);
    }

    public static function getTransactionId(Order $order)
    {
        return self::$storage[$order] ??= \Mindbox\Loyalty\Helper::GUID();
    }
}