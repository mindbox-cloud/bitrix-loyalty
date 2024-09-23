<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Services;

use Bitrix\Sale\Order;
use Mindbox\DTO\DTO;
use Mindbox\DTO\V3\Requests\PreorderRequestDTO;
use Mindbox\Exceptions\MindboxClientException;
use Mindbox\Exceptions\MindboxUnavailableException;
use Mindbox\Loyalty\Exceptions\PriceHasBeenChangedException;
use Mindbox\Loyalty\Exceptions\ValidationErrorCallOperationException;
use Mindbox\Loyalty\Helper;
use Mindbox\Loyalty\Models\Customer;
use Mindbox\Loyalty\Models\OrderMindbox;
use Mindbox\Loyalty\Models\OrderStatus;
use Mindbox\Loyalty\Operations\AbstractOperation;
use Mindbox\Loyalty\Operations\ChangeStatus;
use Mindbox\Loyalty\Operations\ChangeStatusAdmin;
use Mindbox\Loyalty\Operations\CreateAuthorizedOrder;
use Mindbox\Loyalty\Operations\CreateOrderAdmin;
use Mindbox\Loyalty\Operations\CreateUnauthorizedOrder;
use Mindbox\Loyalty\Operations\SaveOfflineOrder;
use Mindbox\Loyalty\ORM\OrderOperationTypeTable;
use Mindbox\Loyalty\Support\SessionStorage;
use Mindbox\Loyalty\Support\SettingsFactory;
use Mindbox\MindboxRequest;
use Mindbox\MindboxResponse;

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
    public function saveOrder(Order $order, ?string $tmpOrderId)
    {
        if (Helper::isAdminSection()) {
            $response = $this->saveOrderAdmin($order, $tmpOrderId);
            $type = OrderOperationTypeTable::OPERATION_TYPE_AUTH;
            SessionStorage::getInstance()->setOperationType($type);
        } elseif ($order->isNew()) {
            if (Helper::isUserUnAuthorized()) {
                $response = $this->saveUnauthorizedOrder($order, $tmpOrderId);
                $type = OrderOperationTypeTable::OPERATION_TYPE_NOT_AUTH;
            } else {
                $response = $this->saveAuthorizedOrder($order, $tmpOrderId);
                $type = OrderOperationTypeTable::OPERATION_TYPE_AUTH;
            }
            SessionStorage::getInstance()->setOperationType($type);
        } else {
            $type = OrderOperationTypeTable::getOrderType((string) $order->getId());

            if ($type === OrderOperationTypeTable::OPERATION_TYPE_AUTH) {
                $response = $this->saveAuthorizedOrder($order, $tmpOrderId);
            } else {
                $response = $this->saveUnauthorizedOrder($order, $tmpOrderId);
            }
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

        if ($response->isError()) {
            throw new ValidationErrorCallOperationException(
                message: 'Response error!!!',
            );
        }

        $orderData = $responceData['order'];
        if ($orderData['processingStatus'] === 'PriceHasBeenChanged') {
            $statusDescription = $orderData['statusDescription'] ?? 'PriceHasBeenChanged';

            throw new PriceHasBeenChangedException(
                message: $statusDescription
            );
        }

        return (string) $orderData['ids']['mindboxId'];
    }

    public function saveOrderAdmin(Order $order, ?string $tmpOrderId)
    {
        $settings = SettingsFactory::createBySiteId($order->getSiteId());

        $mindboxOrder = new OrderMindbox($order, $settings);
        $customer = new Customer((int)$order->getUserId());

        $ids = $mindboxOrder->getIds();

        if (isset($tmpOrderId)) {
            $ids[$settings->getTmpOrderId()] = $tmpOrderId;
        }

        $orderData = array_filter([
            'ids' => $ids,
            'totalPrice' => $mindboxOrder->getTotalPrice(),
            'deliveryCost' => $mindboxOrder->getDeliveryCost(),
            'lines' => $mindboxOrder->getLines()->getData(),
            'customFields' => $mindboxOrder->getCustomFields(),
            'payments' => $mindboxOrder->getPayments(),
            'bonusPoints' => $mindboxOrder->getBonusPoints(),
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

        /** @var CreateOrderAdmin $createOrderAdmin */
        $createOrderAdmin = $this->serviceLocator->get('mindboxLoyalty.createOrderAdmin');
        $createOrderAdmin->setSettings($settings);

        return $this->execute($createOrderAdmin, $DTO);
    }

    public function saveAuthorizedOrder(Order $order, ?string $tmpOrderId)
    {
        $settings = SettingsFactory::createBySiteId($order->getSiteId());

        $mindboxOrder = new OrderMindbox($order, $settings);
        $customer = new Customer((int)$order->getUserId());

        $ids = $mindboxOrder->getIds();

        if (isset($tmpOrderId)) {
            $ids[$settings->getTmpOrderId()] = $tmpOrderId;
        }

        $orderData = array_filter([
            'ids' => $ids,
            'totalPrice' => $mindboxOrder->getTotalPrice(),
            'deliveryCost' => $mindboxOrder->getDeliveryCost(),
            'lines' => $mindboxOrder->getLines()->getData(),
            'customFields' => $mindboxOrder->getCustomFields(),
            'payments' => $mindboxOrder->getPayments(),
            'bonusPoints' => $mindboxOrder->getBonusPoints(),
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
        $createAuthorizedOrder->setSettings($settings);

        return $this->execute($createAuthorizedOrder, $DTO);
    }

    public function saveUnauthorizedOrder(Order $order, ?string $tmpOrderId)
    {
        $settings = SettingsFactory::createBySiteId($order->getSiteId());

        $mindboxOrder = new OrderMindbox($order, $settings);
        $customer = new Customer((int)$order->getUserId());
        SubscribeService::setSubscriptionsToCustomer($customer, $settings);

        $ids = $mindboxOrder->getIds();

        if (isset($tmpOrderId)) {
            $ids[$settings->getTmpOrderId()] = $tmpOrderId;
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
            'subscriptions' => $customer->getSubscriptions(),
        ]);

        $DTO = new \Mindbox\DTO\V3\Requests\PreorderRequestDTO();
        $DTO->setOrder($orderData);
        $DTO->setCustomer($customerData);

        /** @var CreateUnauthorizedOrder $createUnauthorizedOrder */
        $createUnauthorizedOrder = $this->serviceLocator->get('mindboxLoyalty.createUnauthorizedOrder');
        $createUnauthorizedOrder->setSettings($settings);

        return $this->execute($createUnauthorizedOrder, $DTO);
    }

    /**
     * @throws MindboxUnavailableException
     */
    protected function execute(AbstractOperation $operation, PreorderRequestDTO $DTO): MindboxResponse
    {
        $transactionId = md5(\spl_object_hash($DTO) . time());

        for ($i = 1; $i < 3; $i++) {
            try {
                $operation->execute($DTO, $transactionId);
                $response = $operation->getResponse();
                break;
            } catch (MindboxUnavailableException $e) {
            }
        }

        if (!isset($response)) {
            throw new MindboxUnavailableException('Процессинг временно недосутпен. Часть скидок и бонусы будут недоступны для заказа');
        }

        return $response;
    }

    public function confirmSaveOrder(Order $order, string $tmpOrderId)
    {
        $settings = SettingsFactory::createBySiteId($order->getSiteId());

        $mindboxOrder = new OrderMindbox($order, $settings);
        $statusOrder = new OrderStatus($order, $settings);

        $ids = $mindboxOrder->getIds();
        $ids[$settings->getTmpOrderId()] = $tmpOrderId;

        $orderData = array_filter([
            'ids' => $ids,
            'email' => $mindboxOrder->getEmail(),
            'mobilePhone' => $mindboxOrder->getMobilePhone(),
        ]);

        $DTO = new \Mindbox\DTO\V3\Requests\PreorderRequestDTO([
            'orderLinesStatus' => $statusOrder->getStatus(),
            'order' => $orderData
        ]);

        /** @var ChangeStatus $changeStatus */
        $changeStatus = $this->serviceLocator->get('mindboxLoyalty.changeStatus');
        $changeStatus->setSettings($settings);

        try {
            $changeStatus->execute($DTO);
        } catch (MindboxClientException $e) {
            $request = $changeStatus->getRequest();
            if ($request instanceof MindboxRequest) {
                \Mindbox\Loyalty\ORM\QueueTable::push($request, $order->getSiteId());
            }
        }
    }

    public function cancelBrokenOrder(string $tmpOrderId, string $siteId): bool
    {
        $settings = SettingsFactory::createBySiteId($siteId);

        $matchStatuses = $settings->getOrderStatusFieldsMatch();
        $cancelStatus = $matchStatuses['CANCEL'];

        if (!$cancelStatus) {
            return false;
        }

        $DTO = new \Mindbox\DTO\V3\Requests\PreorderRequestDTO([
            'orderLinesStatus' => $cancelStatus,
            'order' => [
                'ids' => [
                    $settings->getTmpOrderId() => $tmpOrderId
                ]
            ]
        ]);

        /** @var ChangeStatus $changeStatus */
        $changeStatus = $this->serviceLocator->get('mindboxLoyalty.changeStatus');
        $changeStatus->setSettings($settings);

        try {
            $changeStatus->execute($DTO);
        } catch (MindboxClientException $e) {
            $request = $changeStatus->getRequest();

            if ($request instanceof MindboxRequest) {
                \Mindbox\Loyalty\ORM\QueueTable::push($request, $settings->getSiteId());
            }
        }

        return true;
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
        $saveOfflineOrder->setSettings($settings);

        try {
            $saveOfflineOrder->execute($DTO);
        } catch (MindboxClientException $e) {
            $request = $saveOfflineOrder->getRequest();
            if ($request instanceof MindboxRequest) {
                \Mindbox\Loyalty\ORM\QueueTable::push($request, $order->getSiteId());
            }
        }
    }

    public function changeStatus(Order $order)
    {
        $settings = SettingsFactory::createBySiteId($order->getSiteId());

        $mindboxOrder = new OrderMindbox($order, $settings);
        $statusOrder = new OrderStatus($order, $settings);

        $orderData = array_filter([
            'ids' => $mindboxOrder->getIds(),
            'email' => $mindboxOrder->getEmail(),
            'mobilePhone' => $mindboxOrder->getMobilePhone(),
        ]);

        $DTO = new \Mindbox\DTO\V3\Requests\PreorderRequestDTO([
            'orderLinesStatus' => $statusOrder->getStatus(),
            'order' => $orderData
        ]);

        /** @var ChangeStatus $changeStatus */
        $changeStatus = $this->serviceLocator->get('mindboxLoyalty.changeStatus');
        $changeStatus->setSettings($settings);

        try {
            $changeStatus->execute($DTO);
        } catch (MindboxClientException $e) {
            $request = $changeStatus->getRequest();
            if ($request instanceof MindboxRequest) {
                \Mindbox\Loyalty\ORM\QueueTable::push($request, $order->getSiteId());
            }
        }
    }

    public function changeStatusAdmin(Order $order)
    {
        $settings = SettingsFactory::createBySiteId($order->getSiteId());

        $mindboxOrder = new OrderMindbox($order, $settings);
        $statusOrder = new OrderStatus($order, $settings);

        $orderData = array_filter([
            'ids' => $mindboxOrder->getIds(),
            'email' => $mindboxOrder->getEmail(),
            'mobilePhone' => $mindboxOrder->getMobilePhone(),
        ]);

        $DTO = new \Mindbox\DTO\V3\Requests\PreorderRequestDTO([
            'orderLinesStatus' => $statusOrder->getStatus(),
            'order' => $orderData
        ]);

        /** @var ChangeStatusAdmin $changeStatusAdmin */
        $changeStatusAdmin = $this->serviceLocator->get('mindboxLoyalty.changeStatusAdmin');
        $changeStatusAdmin->setSettings($settings);

        try {
            $changeStatusAdmin->execute($DTO);
        } catch (MindboxClientException $e) {
            $request = $changeStatusAdmin->getRequest();
            if ($request instanceof MindboxRequest) {
                \Mindbox\Loyalty\ORM\QueueTable::push($request, $order->getSiteId());
            }
        }
    }

    public function cancelOrder(Order $order)
    {
        $settings = SettingsFactory::createBySiteId($order->getSiteId());
        $mindboxOrder = new OrderMindbox($order, $settings);

        $orderData = array_filter([
            'ids' => $mindboxOrder->getIds(),
            'email' => $mindboxOrder->getEmail(),
            'mobilePhone' => $mindboxOrder->getMobilePhone(),
        ]);

        $DTO = new \Mindbox\DTO\V3\Requests\PreorderRequestDTO([
            'orderLinesStatus' => \Mindbox\Loyalty\Models\OrderStatus::getCancelStatus($settings),
            'order' => $orderData
        ]);

        /** @var ChangeStatus $changeStatus */
        $changeStatus = $this->serviceLocator->get('mindboxLoyalty.changeStatus');
        $changeStatus->setSettings($settings);

        try {
            $changeStatus->execute($DTO);
        } catch (MindboxClientException $e) {
            $request = $changeStatus->getRequest();
            if ($request instanceof MindboxRequest) {
                \Mindbox\Loyalty\ORM\QueueTable::push($request, $order->getSiteId());
            }
        }
    }

    public function clearBasketByOrder(Order $order): bool
    {
        try {
            $settings = SettingsFactory::createBySiteId($order->getSiteId());
            $clearCart = $this->serviceLocator->get('mindboxLoyalty.clearCart');
            $clearCart->setSettings($settings);

            $customer = new Customer((int)$order->getUserId());
            $clearCart->execute(new DTO(['customer' => ['ids' => $customer->getIds()]]));

            return true;
        } catch (MindboxClientException $e) {
            return false;
        }
    }
}
