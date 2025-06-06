<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Events;

use Bitrix\Sale\BasketItem;
use Bitrix\Sale\PriceMaths;
use Mindbox\Loyalty\Support\LoyalityEvents;
use Mindbox\Loyalty\Support\Settings;

class InternalEvent
{
    public static function onCustomPromotionsBasketItem(\Bitrix\Main\Event $event)
    {
        if (!LoyalityEvents::checkEnableEvent(LoyalityEvents::DISCOUNT_FOR_PRICE_TYPE)) {
            return new \Bitrix\Main\EventResult(\Bitrix\Main\EventResult::SUCCESS);
        }

        /** @var BasketItem $basketItem */
        /** @var Settings $settings */
        $basketItem = $event->getParameter('ENTYTY');
        $settings = $event->getParameter('SETTINGS');

        global $USER;

        if ($USER instanceof \CUser && !LoyalityEvents::checkEnableEventsForUserGroup(LoyalityEvents::DISCOUNT_FOR_PRICE_TYPE, $USER->GetUserGroupArray(), $settings)) {
            return new \Bitrix\Main\EventResult(\Bitrix\Main\EventResult::SUCCESS);
        }

        if (!\Bitrix\Main\Loader::includeModule('catalog')) {
            return new \Bitrix\Main\EventResult(\Bitrix\Main\EventResult::SUCCESS);
        }

        $productPrices = \Mindbox\Loyalty\Helper::getProductPrices($basketItem->getProductId());
        if ($productPrices === []) {
            return new \Bitrix\Main\EventResult(\Bitrix\Main\EventResult::SUCCESS);
        }

        $basePriceGroupId = (int) $settings->getBasePriceId();
        $basePriceGroupId = $basePriceGroupId !== 0 ? $basePriceGroupId : \Mindbox\Loyalty\Helper::getBasePriceId();

        $catalogGroupId = (int) $basketItem->getField('PRICE_TYPE_ID');
        if ($catalogGroupId === 0) {
            foreach ($productPrices as $productPrice) {
                if (PriceMaths::roundPrecision($productPrice['PRICE']) === PriceMaths::roundPrecision($basketItem->getBasePrice())) {
                    $catalogGroupId = (int) $productPrice['CATALOG_GROUP_ID'];
                }
            }
        }
        unset($productPrice);

        $returnDiscount = [];
        foreach ($productPrices as $productPrice) {
            if (
                (int) $productPrice['CATALOG_GROUP_ID'] === $basePriceGroupId
                && $productPrice['PRICE'] > $basketItem->getBasePrice()
                && $catalogGroupId > 0
            ) {
                $realDiscountId = 'CATALOG-GROUP-' . $catalogGroupId;

                $returnDiscount = [
                    'BASKET' => [
                        'DISCOUNT_ID' => $realDiscountId,
                        'RESULT' => [
                            'BASKET' => [
                                $basketItem->getBasketCode() => [
                                    'APPLY' => 'Y',
                                    'DESCR' => 'Discount by price type',
                                    'DESCR_DATA' => [
                                        [
                                            'TYPE' => 'CATALOG_GROUP_CUSTOM',
                                            'VALUE_TYPE' => 'CATALOG_GROUP_CUSTOM',
                                            'RESULT_VALUE' => $productPrice['PRICE'] - $basketItem->getBasePrice(),
                                        ]
                                    ],
                                    'MODULE_ID' => 'catalog',
                                    'PRODUCT_ID' => $basketItem->getProductId(),
                                    'BASKET_ID' => $basketItem->getBasketCode(),
                                ]
                            ]
                        ]
                    ],
                    'DISCOUNT' => [
                        'ID' => $realDiscountId,
                        'DISCOUNT_ID' => $realDiscountId,
                        'REAL_DISCOUNT_ID' => $realDiscountId,
                        'MODULE_ID' => 'catalog',
                        'NAME' => 'Discount by price type',
                    ]
                ];
            }
        }
        unset($productPrices, $productPrice);

        $event->addResult(
            new \Bitrix\Main\EventResult(
                \Bitrix\Main\EventResult::SUCCESS,
                [
                    'VALUE' => $returnDiscount
                ]
            )
        );
    }
}