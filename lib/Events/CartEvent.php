<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Events;

use Mindbox\Loyalty\Support\SettingsFactory;

class CartEvent
{
    public function onSaleBasketItemEntitySaved($event)
    {
        global $USER;
        /** @var \Bitrix\Sale\BasketItem $basket */
        $basket = $event->getParameter("ENTITY");
        $values = $event->getParameter("VALUES");

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
            $customer = ($USER->isAuthorized()) ? new \Mindbox\Loyalty\Models\Customer((int)$USER->getID()) : null;
            $method = ($basket->isDelay()) ? 'editFavourite' : 'editCart';

            try {
                if (array_key_exists('PRODUCT_ID', $values)) {
                    $service->$method(
                        new \Mindbox\Loyalty\Models\Product($values['PRODUCT_ID'], $settings),
                        0,
                        $customer
                    );
                }

                $service->$method(
                    new \Mindbox\Loyalty\Models\Product($basket->getProductId(), $settings),
                    $basket->getQuantity(),
                    $customer
                );
            } catch (\Mindbox\Loyalty\Exceptions\ErrorCallOperationException $e) {
            }
        }

    }

    public function onBeforeSaleBasketItemEntityDeleted($event)
    {
        global $USER;
        /** @var \Bitrix\Sale\BasketItem $basket */
        $basket = $event->getParameter("ENTITY");

        \Bitrix\Main\Loader::includeModule('mindbox.loyalty');
        $settings = SettingsFactory::create();

        $service = new \Mindbox\Loyalty\Services\ProductListService($settings);

        $customer = ($USER->isAuthorized()) ? new \Mindbox\Loyalty\Models\Customer((int)$USER->getID()) : null;
        $method = ($basket->isDelay()) ? 'editFavourite' : 'editCart';

        try {
            $service->$method(
                new \Mindbox\Loyalty\Models\Product($basket->getProductId(), $settings),
                0,
                $customer
            );
        } catch (\Mindbox\Loyalty\Exceptions\ErrorCallOperationException $e) {
        }
    }
}