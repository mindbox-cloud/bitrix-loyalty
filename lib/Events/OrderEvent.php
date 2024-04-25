<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Events;

use Bitrix\Sale\Order;
use Mindbox\Exceptions\MindboxUnavailableException;
use Mindbox\Loyalty\Exceptions\PriceHasBeenChangedException;
use Mindbox\Loyalty\Exceptions\ValidationErrorCallOperationException;
use Mindbox\Loyalty\Models\Transaction;
use Mindbox\Loyalty\Services\CalculateService;
use Mindbox\Loyalty\Services\OrderService;
use Mindbox\Loyalty\Support\SessionStorage;

class OrderEvent
{
    public static function onBeforeSaleOrderFinalAction(\Bitrix\Main\Event $event)
    {

        /** @var Order $order */
        $order = $event->getParameter('ENTITY');

        if (!$order instanceof Order) {
            return;
        }

        $service = new CalculateService();
        $service->calculateOrder($order);
    }

    public static function onSaleOrderBeforeSaved(\Bitrix\Main\Event $event)
    {
        /** @var Order $order */
        $order = $event->getParameter('ENTITY');

        if (!$order instanceof Order) {
            return;
        }

        try {
            SessionStorage::getInstance()->clearField(SessionStorage::MINDBOX_ORDER_ID);
            $service = new OrderService();
            $transactionId = null;

            if ($order->isNew()) {
                // Временый идентификатор заказа
                $transactionId = Transaction::getInstance()->get($order);
            }


            $mindboxId = $service->saveOrder($order, $transactionId);

            SessionStorage::getInstance()->setMindboxOrderId($mindboxId);
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

            $service = new CalculateService();
            $service->resetDiscount($order);

            return new \Bitrix\Main\EventResult(
                \Bitrix\Main\EventResult::ERROR,
                new \Bitrix\Sale\ResultError($exception->getMessage(), 'MindboxUnavailableException'),
                'sale'
            );
        }
    }


    public static function onSaleOrderSaved(\Bitrix\Main\Event $event)
    {
        /** @var Order $order */
        $order = $event->getParameter('ENTITY');
        $isNew = $event->getParameter('IS_NEW');

        if (!$order instanceof Order) {
            return;
        }

        if ($isNew) {
            // На прошлом этапе произошла ошибка
            if (Transaction::getInstance()->has($order)) {
                $service = new OrderService();
                $service->confirmSaveOrder($order);
                Transaction::getInstance()->close($order);
            } else {
                $service = new OrderService();
                $service->saveOfflineOrder($order);
            }
        }

        SessionStorage::getInstance()->clear();
    }
}