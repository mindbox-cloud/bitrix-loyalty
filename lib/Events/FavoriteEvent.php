<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Events;

use Mindbox\DTO\V3\OperationDTO;
use Mindbox\Exceptions\MindboxClientException;
use Mindbox\Loyalty\Exceptions\IntegrationLoyaltyException;
use Mindbox\Loyalty\Operations\SetFavourite;
use Mindbox\Loyalty\Support\LoyalityEvents;
use Mindbox\Loyalty\Support\SettingsFactory;

class FavoriteEvent
{
    public static function onAfterUserUpdate($arUser)
    {
        if (!$arUser['RESULT']) {
            return;
        }

        $settings = SettingsFactory::create();
        $userGroupArray = \Bitrix\Main\UserTable::getUserGroupIds((int) $arUser['ID']);

        $fieldName = $settings->getFavoriteFieldName();
        $type = $settings->getFavoriteType();

        if (empty($fieldName)) {
            return;
        }

        if (empty($type)) {
            return;
        }

        if (!array_key_exists($fieldName, $arUser)) {
            return;
        }

        if (!method_exists(self::class, $type)) {
            return;
        }

        $favorites = self::{$type}($arUser[$fieldName]);

        if ($favorites) {
            if (
                LoyalityEvents::checkEnableEvent(LoyalityEvents::ADD_FAVORITE)
                && LoyalityEvents::checkEnableEventsForUserGroup(LoyalityEvents::ADD_FAVORITE, $userGroupArray, $settings)
            ) {
                self::setWishList($favorites);
            }
        } else {
            if (
                LoyalityEvents::checkEnableEvent(LoyalityEvents::REMOVE_FROM_FAVORITE)
                && LoyalityEvents::checkEnableEventsForUserGroup(LoyalityEvents::REMOVE_FROM_FAVORITE, $userGroupArray, $settings)
            ) {
                self::clearWishList();
            }
        }
    }

    private static function comma($value): array
    {
        if (!is_string($value)) {
            return [];
        }
        if (preg_match('/^\d+$|^((\d+)(,?))+\d+$/', $value)) {
            return explode(',', $value);
        }
        return [];
    }

    private static function pipe($value): array
    {
        if (!is_string($value)) {
            return [];
        }
        if (preg_match('/^\d+$|^((\d+)(\|?))+\d+$/', $value)) {
            return explode('|', $value);
        }
        return [];
    }

    private static function semicolon($value): array
    {
        if (!is_string($value)) {
            return [];
        }
        if (preg_match('/^\d+$|^((\d+)(;?))+\d+$/', $value)) {
            return explode(';', $value);
        }
        return [];
    }

    private static function serialize_array($value): array
    {
        if (!is_string($value)) {
            return [];
        }
        if (is_array(unserialize($value, ['allowed_classes' => false]))) {
            return unserialize($value, ['allowed_classes' => false]);
        }
        return [];
    }

    private static function iblock_elements($value): array
    {
        if (is_array($value) && !empty(current($value))) {
            return $value;
        }
        return [];
    }

    private static function setWishList(array $favorites): void
    {
        global $USER;

        try {
            $settings = SettingsFactory::create();
            $customer = (is_object($USER) && $USER->isAuthorized()) ? new \Mindbox\Loyalty\Models\Customer((int)$USER->getID()) : null;

            $payload = [];
            foreach ($favorites as $favorite) {
                $product = new \Mindbox\Loyalty\Models\Product((int)$favorite, $settings);

                $payload['productList'][] = [
                    'product' => [
                        'ids' => $product->getIds()
                    ],
                    'priceOfLine' => $product->getPrice(),
                    'count' => 1
                ];
            }

            if ($customer) {
                $payload['customer'] = array_filter([
                    'ids' => $customer->getIds(),
                    'email' => $customer->getEmail(),
                    'mobilePhone' => $customer->getMobilePhone(),
                ]);
            } else {
                $payload['customer'] = [
                    'ids' => [
                        $settings->getExternalUserId() => null
                    ]
                ];
            }

            $dto = new OperationDTO($payload);
            /** @var SetFavourite $operation */
            $operation = \Bitrix\Main\DI\ServiceLocator::getInstance()->get('mindboxLoyalty.setFavourite');
            $operation->execute($dto);
        } catch (\Mindbox\Loyalty\Exceptions\ErrorCallOperationException $e) {
        }
    }

    private static function clearWishList(): void
    {
        try {
            $settings = SettingsFactory::create();

            global $USER;

            if (is_object($USER) && $USER->isAuthorized()) {
                $customer = new \Mindbox\Loyalty\Models\Customer((int) $USER->getID());
                $dto = new \Mindbox\DTO\DTO([
                    'customer' =>  array_filter([
                        'ids' => $customer->getIds(),
                        'email' => $customer->getEmail(),
                        'mobilePhone' => $customer->getMobilePhone(),
                    ])
                ]);
            } else {
                $dto = new \Mindbox\DTO\DTO([
                    'customer' => [
                        'ids' => [
                            $settings->getExternalUserId() => null
                        ]
                    ]
                ]);
            }

            $serviceLocator = \Bitrix\Main\DI\ServiceLocator::getInstance();
            /** @var \Mindbox\Loyalty\Operations\ClearFavourite $clearFavourite */
            $clearFavourite = $serviceLocator->get('mindboxLoyalty.clearFavourite');
            $clearFavourite->setSettings($settings);
            $clearFavourite->execute($dto);
        } catch (MindboxClientException|IntegrationLoyaltyException $e) {
        }
    }
}