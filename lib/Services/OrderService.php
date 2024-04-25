<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Services;

use Bitrix\Main\Context;
use Bitrix\Sale\Order;
use Mindbox\DTO\V3\Requests\PreorderRequestDTO;
use Mindbox\Exceptions\MindboxClientException;
use Mindbox\Exceptions\MindboxException;
use Mindbox\Exceptions\MindboxUnavailableException;
use Mindbox\Loyalty\Exceptions\PriceHasBeenChangedException;
use Mindbox\Loyalty\Exceptions\ValidationErrorCallOperationException;
use Mindbox\Loyalty\Helper;
use Mindbox\Loyalty\Models\Customer;
use Mindbox\Loyalty\Models\OrderMindbox;
use Mindbox\Loyalty\Models\OrderStatus;
use Mindbox\Loyalty\Operations\AbstractOperation;
use Mindbox\Loyalty\Operations\ChangeStatus;
use Mindbox\Loyalty\Operations\CreateAuthorizedOrder;
use Mindbox\Loyalty\Operations\CreateAuthorizedOrderAdmin;
use Mindbox\Loyalty\Operations\CreateUnauthorizedOrder;
use Mindbox\Loyalty\Operations\SaveOfflineOrder;
use Mindbox\Loyalty\Support\SessionStorage;
use Mindbox\Loyalty\Support\SettingsFactory;

class OrderService
{
    private \Bitrix\Main\DI\ServiceLocator $serviceLocator;

    public function __construct()
    {
        $this->serviceLocator = \Bitrix\Main\DI\ServiceLocator::getInstance();
    }

    /**
     * @param Order $order
     * @param string $transactionId
     * @return string
     * @throws PriceHasBeenChangedException
     * @throws ValidationErrorCallOperationException
     * @throws MindboxUnavailableException
     */
    public function saveOrder(Order $order, ?string $transactionId)
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

    public function saveOrderAdmin(Order $order, ?string $transactionId)
    {
        $settings = SettingsFactory::createBySiteId($order->getSiteId());

        $mindboxOrder = new OrderMindbox($order, $settings);
        $customer = new Customer((int)$order->getUserId());

        $ids = $mindboxOrder->getIds();

        if (isset($transactionId)) {
            $ids[$settings->getTmpOrderId()] = $transactionId;
        }

        $orderData = array_filter([
            'ids' => $ids,
            'totalPrice' => $mindboxOrder->getTotalPrice(),
            'deliveryCost' => $mindboxOrder->getDeliveryCost(),
            'lines' => $mindboxOrder->getLines()->getData(),
            'customFields' => $mindboxOrder->getCustomFields(),
            'payments' => $mindboxOrder->getPayments(),
            'coupons' => $mindboxOrder->getCoupons(),
            'email' => $mindboxOrder->getEmail(),
            'mobilePhone' => $mindboxOrder->getMobilePhone(),
        ]);

        $customerData = array_filter([
            'ids' => $customer->getIds(),
            'email' => $customer->getEmail(),
            'mobilePhone' => $customer->getMobilePhone(),
        ]);

        $DTO = new \Mindbox\DTO\V3\Requests\PreorderRequestDTO();
        $DTO->setOrder($orderData);
        $DTO->setCustomer($customerData);

        /** @var CreateAuthorizedOrderAdmin $createAuthorizedOrderAdmin */
        $createAuthorizedOrderAdmin = $this->serviceLocator->get('mindboxLoyalty.createAuthorizedOrderAdmin');

        return $this->execute($createAuthorizedOrderAdmin, $DTO);
    }

    public function saveAuthorizedOrder(Order $order, ?string $transactionId)
    {
        $settings = SettingsFactory::createBySiteId($order->getSiteId());

        $mindboxOrder = new OrderMindbox($order, $settings);
        $customer = new Customer((int)$order->getUserId());

        $ids = $mindboxOrder->getIds();

        if (isset($transactionId)) {
            $ids[$settings->getTmpOrderId()] = $transactionId;
        }

        $orderData = array_filter([
            'ids' => $ids,
            'totalPrice' => $mindboxOrder->getTotalPrice(),
            'deliveryCost' => $mindboxOrder->getDeliveryCost(),
            'lines' => $mindboxOrder->getLines()->getData(),
            'customFields' => $mindboxOrder->getCustomFields(),
            'payments' => $mindboxOrder->getPayments(),
            'coupons' => $mindboxOrder->getCoupons(),
            'email' => $mindboxOrder->getEmail(),
            'mobilePhone' => $mindboxOrder->getMobilePhone(),
        ]);

        $customerData = array_filter([
            'ids' => $customer->getIds(),
            'email' => $customer->getEmail(),
            'mobilePhone' => $customer->getMobilePhone(),
        ]);

        $DTO = new \Mindbox\DTO\V3\Requests\PreorderRequestDTO();
        $DTO->setOrder($orderData);
        $DTO->setCustomer($customerData);

        /** @var CreateAuthorizedOrder $createAuthorizedOrder */
        $createAuthorizedOrder = $this->serviceLocator->get('mindboxLoyalty.createAuthorizedOrder');

        return $this->execute($createAuthorizedOrder, $DTO);
    }

    public function saveUnauthorizedOrder(Order $order, ?string $transactionId)
    {
        $settings = SettingsFactory::createBySiteId($order->getSiteId());

        $mindboxOrder = new OrderMindbox($order, $settings);
        $customer = new Customer((int)$order->getUserId());

        $ids = $mindboxOrder->getIds();

        if (isset($transactionId)) {
            $ids[$settings->getTmpOrderId()] = $transactionId;
        }

        $orderData = array_filter([
            'ids' => $ids,
            'totalPrice' => $mindboxOrder->getTotalPrice(),
            'deliveryCost' => $mindboxOrder->getDeliveryCost(),
            'lines' => $mindboxOrder->getLines()->getData(),
            'customFields' => $mindboxOrder->getCustomFields(),
            'payments' => $mindboxOrder->getPayments(),
            'coupons' => $mindboxOrder->getCoupons(),
            'email' => $mindboxOrder->getEmail(),
            'mobilePhone' => $mindboxOrder->getMobilePhone(),
        ]);

        $customerData = array_filter([
            'ids' => $customer->getIds(),
            'email' => $customer->getEmail(),
            'mobilePhone' => $customer->getMobilePhone(),
            'firstName' => $customer->getFirstName(),
            'lastName' => $customer->getLastName(),
            'middleName' => $customer->getMiddleName(),
        ]);

        $DTO = new \Mindbox\DTO\V3\Requests\PreorderRequestDTO();
        $DTO->setOrder($orderData);
        $DTO->setCustomer($customerData);

        /** @var CreateUnauthorizedOrder $createUnauthorizedOrder */
        $createUnauthorizedOrder = $this->serviceLocator->get('mindboxLoyalty.createUnauthorizedOrder');

        return $this->execute($createUnauthorizedOrder, $DTO);
    }

    protected function execute(AbstractOperation $operation, PreorderRequestDTO $DTO)
    {
        $transactionId = \spl_object_hash($DTO);

        for ($i = 0, $i < 3; $i++;) {
            try {
                $response = $operation->execute($DTO, $transactionId);
                break;
            } catch (MindboxUnavailableException $e) {
            }
        }

        if (!isset($response)) {
            throw new MindboxUnavailableException('Процессинг временно недосутпен. Часть скидок и бонусы будут недоступны для заказа');
        }

        return $response;
    }

    public function confirmSaveOrder(Order $order)
    {
        $settings = SettingsFactory::createBySiteId($order->getSiteId());

        $mindboxOrder = new OrderMindbox($order, $settings);
        $statusOrder = new OrderStatus($order, $settings);

        $orderData = array_filter([
            'ids' => array_merge($mindboxOrder->getIds(), ['mindboxId' => SessionStorage::getInstance()->getMindboxOrderId()]),
            'email' => $mindboxOrder->getEmail(),
            'mobilePhone' => $mindboxOrder->getMobilePhone(),
        ]);

        $DTO = new \Mindbox\DTO\V3\Requests\PreorderRequestDTO([
            'orderLinesStatus' => $statusOrder->getStatus(),
            'order' => $orderData
        ]);

        /** @var ChangeStatus $changeStatus */
        $changeStatus = $this->serviceLocator->get('mindboxLoyalty.changeStatus');
        try {
            $response = $changeStatus->execute($DTO);
        } catch (MindboxClientException $e) {
            // todo добавить в очередь или сразу его в очередь
        }
    }

    public function saveOfflineOrder(Order $order)
    {
        $settings = SettingsFactory::createBySiteId($order->getSiteId());

        $mindboxOrder = new OrderMindbox($order, $settings);
        $customer = new Customer((int)$order->getUserId());

        $orderData = array_filter([
            'ids' => $mindboxOrder->getIds(),
            'totalPrice' => $mindboxOrder->getOrder()->getPrice(),
            'deliveryCost' => $mindboxOrder->getOrder()->getDeliveryPrice(),
            'lines' => $mindboxOrder->getLines()->getData(),
            'customFields' => $mindboxOrder->getCustomFields(),
            'payments' => $mindboxOrder->getPayments(),
            'email' => $mindboxOrder->getEmail(),
            'mobilePhone' => $mindboxOrder->getMobilePhone(),
        ]);

        $customerData = array_filter([
            'ids' => $customer->getIds(),
        ]);

        $DTO = new \Mindbox\DTO\V3\Requests\PreorderRequestDTO();
        $DTO->setOrder($orderData);
        $DTO->setCustomer($customerData);

        /** @var SaveOfflineOrder $saveOfflineOrder */
        $saveOfflineOrder = $this->serviceLocator->get('mindboxLoyalty.saveOfflineOrder');
        try {
            $response = $saveOfflineOrder->execute($DTO);
        } catch (MindboxClientException $e) {
            // todo добавить в очередь или сразу его в очередь
        }
    }
}