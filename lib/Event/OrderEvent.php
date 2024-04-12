<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Event;

use Bitrix\Sale\Order;
use Mindbox\Loyalty\Services\CalculateService;

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

    }


    public static function onSaleOrderSaved(\Bitrix\Main\Event $event)
    {

    }
}