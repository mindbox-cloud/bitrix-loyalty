<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Events;

use Bitrix\Main\Context;
use Bitrix\Sale\Order;
use Mindbox\Exceptions\MindboxUnavailableException;
use Mindbox\Loyalty\Exceptions\IntegrationLoyaltyException;
use Mindbox\Loyalty\Models\OrderMindbox;
use Mindbox\Loyalty\Support\CallBlocking;
use Mindbox\Loyalty\Exceptions\EmptyLineException;
use Mindbox\Loyalty\Exceptions\PriceHasBeenChangedException;
use Mindbox\Loyalty\Exceptions\ResponseErrorExceprion;
use Mindbox\Loyalty\Exceptions\ValidationErrorCallOperationException;
use Mindbox\Loyalty\Models\Transaction;
use Mindbox\Loyalty\ORM\OrderOperationTypeTable;
use Mindbox\Loyalty\PropertyCodeEnum;
use Mindbox\Loyalty\Services\CalculateService;
use Mindbox\Loyalty\Services\OrderService;
use Mindbox\Loyalty\Support\LoyalityEvents;
use Mindbox\Loyalty\Support\SessionStorage;
use Mindbox\Loyalty\Support\SettingsFactory;

class OrderEvent
{
    public static function onBeforeSaleOrderFinalAction(\Bitrix\Main\Event $event)
    {
        if (!LoyalityEvents::checkEnableEvent(LoyalityEvents::CALCULATE_DISCOUNT)) {
            return;
        }
        /** @var Order $order */
        $order = $event->getParameter('ENTITY');

        if (!$order instanceof Order) {
            return new \Bitrix\Main\EventResult(\Bitrix\Main\EventResult::SUCCESS);
        }

        if (CallBlocking::getInstance()->isLocked()) {
            return new \Bitrix\Main\EventResult(\Bitrix\Main\EventResult::SUCCESS);
        }

        // В админке бонусы сразу в свойства записываются
        if ($order->isNew() && !Context::getCurrent()->getRequest()->isAdminSection()) {
            $settings = SettingsFactory::createBySiteId($order->getSiteId());

            $mindboxOrder = new OrderMindbox($order, $settings);
            $mindboxOrder->setBonuses(SessionStorage::getInstance()->getPayBonuses());
            $mindboxOrder->setCoupons(SessionStorage::getInstance()->getPromocodeValue());
        }

        $service = new CalculateService();
        try {
            $service->calculateOrder($order);
        } catch (EmptyLineException $e) {
        } catch (ResponseErrorExceprion $e) {
            $service->resetDiscount($order);
        } catch (\Exception $e) {
            $service->resetDiscount($order);
        }

        return new \Bitrix\Main\EventResult(\Bitrix\Main\EventResult::SUCCESS);
    }

    public static function onSaleOrderBeforeSaved(\Bitrix\Main\Event $event)
    {
        if (!LoyalityEvents::checkEnableEvent(LoyalityEvents::CREATE_ORDER)) {
            return;
        }

        /** @var Order $order */
        $order = $event->getParameter('ENTITY');
        $oldValues = $event->getParameter('VALUES');

        $changeValue = array_keys($oldValues);
        $continueValues = [
            // Смена статуса
            'STATUS_ID',
            'DATE_STATUS',
            // Оплата
            'DATE_PAYED',
            'EMP_PAYED_ID',
            'PAYED',
            'SUM_PAID',
            // Отмена просто
            'CANCELED',
            'DATE_CANCELED',
            'EMP_CANCELED_ID',
            'REASON_CANCELED',
        ];

        if (!$order->isNew() && array_diff($changeValue, $continueValues) === []) {
            return new \Bitrix\Main\EventResult(\Bitrix\Main\EventResult::SUCCESS);
        }

        if (!$order instanceof Order) {
            return new \Bitrix\Main\EventResult(\Bitrix\Main\EventResult::SUCCESS);
        }

        if (CallBlocking::getInstance()->isLocked()) {
            return new \Bitrix\Main\EventResult(\Bitrix\Main\EventResult::SUCCESS);
        }

        try {
            SessionStorage::getInstance()->clearField(SessionStorage::MINDBOX_ORDER_ID);
            SessionStorage::getInstance()->clearField(SessionStorage::OPERATION_TYPE);

            $service = new OrderService();
            $transactionId = null;

            if ($order->isNew()) {
                // Временый идентификатор заказа
                $transactionId = Transaction::getInstance()->get($order);
            }

            if ($order->isNew() && !Context::getCurrent()->getRequest()->isAdminSection()) {
                $settings = SettingsFactory::createBySiteId($order->getSiteId());

                $mindboxOrder = new OrderMindbox($order, $settings);
                $mindboxOrder->setBonuses(SessionStorage::getInstance()->getPayBonuses());
                $mindboxOrder->setCoupons(SessionStorage::getInstance()->getPromocodeValue());
            }

            if (Context::getCurrent()->getRequest()->isAdminSection()) {
                // @info сохраним корзину, чтобы получить корректный lineId
                $order->getBasket()->save();
            }

            $mindboxId = $service->saveOrder($order, $transactionId);

            if ($order->isNew() && $mindboxId) {
                $propertyMindboxId = $order->getPropertyCollection()->getItemByOrderPropertyCode(PropertyCodeEnum::PROPERTIES_MINDBOX_ORDER_ID);
                if ($propertyMindboxId instanceof \Bitrix\Sale\PropertyValue) {
                    $propertyMindboxId->setValue($mindboxId);
                }
            }
        } catch (PriceHasBeenChangedException $exception) {
            // тут заказ в мб не должен создаться
            SessionStorage::getInstance()->clear();

            // Временый идентификатор заказа следует удалить, так как заказ на стороне МБ не был создан
            Transaction::getInstance()->close($order);

            return new \Bitrix\Main\EventResult(
                \Bitrix\Main\EventResult::ERROR,
                new \Bitrix\Sale\ResultError($exception->getMessage(), 'PriceHasBeenChanged'),
                'sale'
            );
        } catch (ValidationErrorCallOperationException $exception) {
            // Временый идентификатор заказа следует удалить, так как заказ на стороне МБ не был создан
            Transaction::getInstance()->close($order);

            return new \Bitrix\Main\EventResult(
                \Bitrix\Main\EventResult::ERROR,
                new \Bitrix\Sale\ResultError($exception->getMessage(), 'ValidationErrorCallOperationException'),
                'sale'
            );
        } catch (MindboxUnavailableException $exception) {
            // тут заказ в мб не должен создаться
            SessionStorage::getInstance()->clear();

            // Временый идентификатор заказа следует удалить, так как заказ на стороне МБ не был создан
            // Заказ будет создавться через оффлайн
            Transaction::getInstance()->close($order);

            // Блокирую на минуту
            CallBlocking::getInstance()->lock();

            $service = new CalculateService();
            $service->resetDiscount($order);

            return new \Bitrix\Main\EventResult(
                \Bitrix\Main\EventResult::ERROR,
                new \Bitrix\Sale\ResultError($exception->getMessage(), 'MindboxUnavailableException'),
                'sale'
            );
        } catch (EmptyLineException $exception) {
            // тут заказ в мб не должен создаться
            SessionStorage::getInstance()->clear();

            // Временый идентификатор заказа следует удалить, так как заказ на стороне МБ не был создан
            // Заказ будет создавться через оффлайн
            Transaction::getInstance()->close($order);
        }

        return new \Bitrix\Main\EventResult(\Bitrix\Main\EventResult::SUCCESS);
    }

    public static function onSaleOrderSaved(\Bitrix\Main\Event $event)
    {
        if (!LoyalityEvents::checkEnableEvent(LoyalityEvents::CONFIRM_ORDER)) {
            return;
        }

        /** @var Order $order */
        $order = $event->getParameter('ENTITY');
        $isNew = $event->getParameter('IS_NEW');

        if (!$order instanceof Order) {
            return new \Bitrix\Main\EventResult(\Bitrix\Main\EventResult::SUCCESS);
        }

        if (!$isNew) {
            SessionStorage::getInstance()->clear();

            return new \Bitrix\Main\EventResult(\Bitrix\Main\EventResult::SUCCESS);
        }

        // На прошлом этапе произошла ошибка
        if (Transaction::getInstance()->has($order)) {
            $service = new OrderService();
            $service->confirmSaveOrder($order, Transaction::getInstance()->get($order));
            $service->confirmDeliveryDiscount($order);

            Transaction::getInstance()->save($order);
            Transaction::getInstance()->close($order);
        } else {
            $service = new OrderService();
            $service->saveOfflineOrder($order);
            SessionStorage::getInstance()->clear();

            $service = new OrderService();
            $service->clearBasketByOrder($order);

            return new \Bitrix\Main\EventResult(\Bitrix\Main\EventResult::SUCCESS);
        }

        if (
            $isNew
            && SessionStorage::getInstance()->getOperationType() !== null
        ) {
            OrderOperationTypeTable::setTypeOrder((string) $order->getField('ACCOUNT_NUMBER'), SessionStorage::getInstance()->getOperationType());
        }

        SessionStorage::getInstance()->clear();

        $service = new OrderService();
        $service->clearBasketByOrder($order);

        return new \Bitrix\Main\EventResult(\Bitrix\Main\EventResult::SUCCESS);
    }

    public static function onSaleStatusOrderChange(\Bitrix\Main\Event $event)
    {
        if (!LoyalityEvents::checkEnableEvent(LoyalityEvents::CHANGE_STATUS_ORDER)) {
            return;
        }

        $order = $event->getParameter('ENTITY');

        if (!isset($order)) {
            return new \Bitrix\Main\EventResult(\Bitrix\Main\EventResult::SUCCESS);
        }

        try {
            $service = new OrderService();
            $service->changeStatus($order);
        } catch (IntegrationLoyaltyException $exception) {

        }

        return new \Bitrix\Main\EventResult(\Bitrix\Main\EventResult::SUCCESS);
    }

    public static function onSaleOrderCanceled(\Bitrix\Main\Event $event)
    {
        if (!LoyalityEvents::checkEnableEvent(LoyalityEvents::CANCEL_ORDER)) {
            return;
        }

        $order = $event->getParameter('ENTITY');

        if (!isset($order)) {
            return new \Bitrix\Main\EventResult(\Bitrix\Main\EventResult::SUCCESS);;
        }

        try {
            $service = new OrderService();
            $service->cancelOrder($order);
        } catch (IntegrationLoyaltyException $exception) {

        }


        return new \Bitrix\Main\EventResult(\Bitrix\Main\EventResult::SUCCESS);
    }

    public static function onSaleOrderDeleted(\Bitrix\Main\Event $event)
    {
        if (!LoyalityEvents::checkEnableEvent(LoyalityEvents::DELETE_ORDER)) {
            return;
        }

        $order = $event->getParameter('ENTITY');
        $isSuccess =  $event->getParameter('VALUE');

        if (!isset($order)) {
            return new \Bitrix\Main\EventResult(\Bitrix\Main\EventResult::SUCCESS);;
        }
        if (!$isSuccess) {
            return new \Bitrix\Main\EventResult(\Bitrix\Main\EventResult::SUCCESS);;
        }

        try {
            $service = new OrderService();
            $service->cancelOrder($order);
        } catch (IntegrationLoyaltyException $exception) {
        }

        return new \Bitrix\Main\EventResult(\Bitrix\Main\EventResult::SUCCESS);
    }
}