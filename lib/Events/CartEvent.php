<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Events;

use Bitrix\Sale\Order;
use Mindbox\Loyalty\Support\LoyalityEvents;
use Mindbox\Loyalty\Support\SettingsFactory;

class CartEvent
{
    public static function onSaleBasketItemEntitySaved(\Bitrix\Main\Event $event)
    {
        global $USER;

        /** @var \Bitrix\Sale\BasketItem $basketItem */
        $basketItem = $event->getParameter('ENTITY');
        $values = $event->getParameter('VALUES');

        $order = $basketItem->getCollection()->getOrder();

        if ($order instanceof Order && !$order->isNew()) {
            return new \Bitrix\Main\EventResult(\Bitrix\Main\EventResult::SUCCESS);
        }

        if ($basketItem->isDelay() && !LoyalityEvents::checkEnableEvent(LoyalityEvents::ADD_FAVORITE)) {
            return new \Bitrix\Main\EventResult(\Bitrix\Main\EventResult::SUCCESS);
        }

        if (!$basketItem->isDelay() && !LoyalityEvents::checkEnableEvent(LoyalityEvents::ADD_CART)) {
            return new \Bitrix\Main\EventResult(\Bitrix\Main\EventResult::SUCCESS);
        }

        if (!empty($values)) {
            $needProcessedKeys = ['QUANTITY', 'ID', 'PRODUCT_ID'];

            $needProcessed = false;

            foreach ($needProcessedKeys as $item) {
                if (array_key_exists($item, $values)) {
                    $needProcessed = true;
                    break;
                }
            }

            if (!$needProcessed) {
                return new \Bitrix\Main\EventResult(\Bitrix\Main\EventResult::SUCCESS);
            }

            $settings = SettingsFactory::create();
            $service = new \Mindbox\Loyalty\Services\ProductListService($settings);
            $customer = (is_object($USER) && $USER->isAuthorized()) ? new \Mindbox\Loyalty\Models\Customer((int) $USER->getID()) : null;
            $method = $basketItem->isDelay() ? 'editFavourite' : 'editCart';

            try {
                if (array_key_exists('PRODUCT_ID', $values)) {
                    $service->$method(
                        new \Mindbox\Loyalty\Models\Product((int)$values['PRODUCT_ID'], $settings),
                        0,
                        $customer
                    );
                }

                $service->$method(
                    new \Mindbox\Loyalty\Models\Product($basketItem->getProductId(), $settings),
                    (int) $basketItem->getQuantity(),
                    $customer
                );
            } catch (\Mindbox\Loyalty\Exceptions\ErrorCallOperationException $e) {
            }
        }

    }

    public static function onBeforeSaleBasketItemEntityDeleted(\Bitrix\Main\Event $event)
    {
        $values = $event->getParameter('VALUES');

        if (!empty($values)) {
            return new \Bitrix\Main\EventResult(\Bitrix\Main\EventResult::SUCCESS);
        }

        global $USER;

        /** @var \Bitrix\Sale\BasketItem $basketItem */
        $basketItem = $event->getParameter('ENTITY');
        $order = $basketItem->getCollection()->getOrder();

        if ($order instanceof Order && !$order->isNew()) {
            return new \Bitrix\Main\EventResult(\Bitrix\Main\EventResult::SUCCESS);
        }

        if ($basketItem->isDelay() && !LoyalityEvents::checkEnableEvent(LoyalityEvents::REMOVE_FROM_FAVORITE)) {
            return new \Bitrix\Main\EventResult(\Bitrix\Main\EventResult::SUCCESS);
        }

        if (!$basketItem->isDelay() && !LoyalityEvents::checkEnableEvent(LoyalityEvents::REMOVE_FROM_CART)) {
            return new \Bitrix\Main\EventResult(\Bitrix\Main\EventResult::SUCCESS);
        }

        $settings = SettingsFactory::create();

        $service = new \Mindbox\Loyalty\Services\ProductListService($settings);

        $customer = (is_object($USER) && $USER->isAuthorized()) ? new \Mindbox\Loyalty\Models\Customer((int)$USER->getID()) : null;
        $method = ($basketItem->isDelay()) ? 'editFavourite' : 'editCart';

        try {
            $service->$method(
                new \Mindbox\Loyalty\Models\Product($basketItem->getProductId(), $settings),
                0,
                $customer
            );
        } catch (\Mindbox\Loyalty\Exceptions\ErrorCallOperationException $e) {
        }
    }
}
