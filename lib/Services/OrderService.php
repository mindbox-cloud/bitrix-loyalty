<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Services;

use Bitrix\Main\Context;
use Bitrix\Sale\Order;
use Mindbox\Loyalty\Exceptions\PriceHasBeenChangedException;
use Mindbox\Loyalty\Exceptions\ValidationErrorCallOperationException;
use Mindbox\Loyalty\Helper;
use Mindbox\Loyalty\Models\Customer;
use Mindbox\Loyalty\Models\OrderMindbox;
use Mindbox\Loyalty\Models\Transaction;
use Mindbox\Loyalty\Operations\CreateAuthorizedOrder;
use Mindbox\Loyalty\Operations\CreateAuthorizedOrderAdmin;
use Mindbox\Loyalty\Operations\CreateUnauthorizedOrder;
use Mindbox\Loyalty\Support\SettingsFactory;

class OrderService
{
    private \Bitrix\Main\DI\ServiceLocator $serviceLocator;

    public function __construct()
    {
        $this->serviceLocator = \Bitrix\Main\DI\ServiceLocator::getInstance();
    }

    public function saveOrder(Order $order, string $transactionId)
    {
        if (Context::getCurrent()->getRequest()->isAdminSection()) {
            $response = $this->saveOrderAdmin($order, $transactionId);
        } elseif (Helper::isUserUnAuthorized((int) $order->getField('USER_ID'))) {
            $response = $this->saveUnauthorizedOrder($order, $transactionId);
        } else {
            $response = $this->saveAuthorizedOrder($order, $transactionId);
        }

        $resultDTO = $response->getResult();
        $responceData = $resultDTO->getFieldsAsArray();

        if ($responceData['status'] === 'ValidationError') {
            $errorMessage = '';
            foreach ($responceData['validationMessages'] as $validationMessages) {
                $errorMessage .= $validationMessages['message'];
            }

            throw new ValidationErrorCallOperationException(
                message: $errorMessage,
                validationMessage: $resultDTO->getValidationMessages()
            );
        }

        $orderData = $responceData['order'];

        if ($orderData['processingStatus'] === 'PriceHasBeenChanged') {
            $statusDescription = $orderData['statusDescription'] ?? 'PriceHasBeenChanged';

            throw new PriceHasBeenChangedException(
                message: $statusDescription
            );
        }

        $mindboxId = (string) $orderData['ids']['mindboxId'];

        return $mindboxId;
    }

    public function saveOrderAdmin(Order $order, string $transactionId)
    {
        $settings = SettingsFactory::createBySiteId($order->getSiteId());

        $mindboxOrder = new OrderMindbox($order, $settings);
        $customer = new Customer((int)$order->getUserId());

        /** @var CreateAuthorizedOrderAdmin $createAuthorizedOrderAdmin */
        $createAuthorizedOrderAdmin = $this->serviceLocator->get('mindboxLoyalty.createAuthorizedOrderAdmin');

        $customerDTO = new \Mindbox\DTO\V3\Requests\CustomerRequestDTO();
        $customerDTO->setIds($customer->getIds());
        $customerDTO->setEmail($customer->getEmail());
        $customerDTO->setMobilePhone($customer->getMobilePhone());

        $orderData = $mindboxOrder->getData();

        $DTO = new \Mindbox\DTO\V3\Requests\PreorderRequestDTO();
        $DTO->setOrder($orderData);
        $DTO->setCustomer($customerDTO);


        return $createAuthorizedOrderAdmin->execute($DTO, $transactionId);
    }

    public function saveAuthorizedOrder(Order $order, string $transactionId)
    {
        $settings = SettingsFactory::createBySiteId($order->getSiteId());

        $mindboxOrder = new OrderMindbox($order, $settings);
        $customer = new Customer((int)$order->getUserId());

        /** @var CreateAuthorizedOrder $createAuthorizedOrder */
        $createAuthorizedOrder = $this->serviceLocator->get('mindboxLoyalty.createAuthorizedOrder');

        $customerDTO = new \Mindbox\DTO\V3\Requests\CustomerRequestDTO();
        $customerDTO->setIds($customer->getIds());
        $customerDTO->setEmail($customer->getEmail());
        $customerDTO->setMobilePhone($customer->getMobilePhone());

        $orderData = $mindboxOrder->getData();

        $DTO = new \Mindbox\DTO\V3\Requests\PreorderRequestDTO();
        $DTO->setOrder($orderData);
        $DTO->setCustomer($customerDTO);

        return $createAuthorizedOrder->execute($DTO, $transactionId);
    }

    public function saveUnauthorizedOrder(Order $order, string $transactionId)
    {
        $settings = SettingsFactory::createBySiteId($order->getSiteId());

        $mindboxOrder = new OrderMindbox($order, $settings);
        $customer = new Customer((int)$order->getUserId());

        /** @var CreateUnauthorizedOrder $createUnauthorizedOrder */
        $createUnauthorizedOrder = $this->serviceLocator->get('mindboxLoyalty.createUnauthorizedOrder');

        // todo должно быть гораздо больше параметров
        $customerDTO = new \Mindbox\DTO\V3\Requests\CustomerRequestDTO();
        $customerDTO->setIds($customer->getIds());
        $customerDTO->setEmail($customer->getEmail());
        $customerDTO->setMobilePhone($customer->getMobilePhone());

        $orderData = $mindboxOrder->getData();

        $DTO = new \Mindbox\DTO\V3\Requests\PreorderRequestDTO();
        $DTO->setOrder($orderData);
        $DTO->setCustomer($customerDTO);

        return $createUnauthorizedOrder->execute($DTO, $transactionId);
    }

    public function cancelOrder(Order $order)
    {
        // Тут должны отменить заказ
    }
}