<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Event;

use Bitrix\Sale\Order;
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
            $service = new OrderService();

            $transactionId = Transaction::getInstance()->get($order);

            $mindboxId = $service->saveOrder($order, $transactionId);

            SessionStorage::getInstance()->setMindboxOrderId($mindboxId);
        } catch (PriceHasBeenChangedException $exception) {
            // тут заказ в мб не должен создаться
            SessionStorage::getInstance()->clear();
            Transaction::getInstance()->close($order);

            return new \Bitrix\Main\EventResult(
                \Bitrix\Main\EventResult::ERROR,
                new \Bitrix\Sale\ResultError($exception->getMessage(), 'PriceHasBeenChanged'),
                'sale'
            );
        } catch (ValidationErrorCallOperationException $exception) {
            Transaction::getInstance()->close($order);
        }
    }


    public static function onSaleOrderSaved(\Bitrix\Main\Event $event)
    {
        /** @var Order $order */
        $order = $event->getParameter('ENTITY');

        if (!$order instanceof Order) {
            return;
        }

        // На прошлом этапе произошла ошибка
        if (!Transaction::getInstance()->has($order)) {
            return;
        }

        try {
            $service = new OrderService();

            $transactionId = Transaction::getInstance()->get($order);
            $service->saveOrder($order, $transactionId);
        } catch (PriceHasBeenChangedException $exception) {
        } catch (ValidationErrorCallOperationException $exception) {
        }

        SessionStorage::getInstance()->clear();
        Transaction::getInstance()->close($order);
    }
}