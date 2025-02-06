<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Events;

use Bitrix\Main\LoaderException;
use Mindbox\Loyalty\Support\LoyalityEvents;
use Mindbox\Loyalty\Support\SettingsFactory;

class FavoriteEvent
{
    public static function onAfterUserUpdate($arUser)
    {
        global $USER;
        $settings = SettingsFactory::create();
        $userGroupArray = \Bitrix\Main\UserTable::getUserGroupIds((int)$arUser['ID']);

        if (array_key_exists($settings->getFavoriteFieldName(), $arUser)) {
            if (method_exists(self::class, $settings->getFavoriteType())) {
                $favorites = self::{$settings->getFavoriteType()}($arUser[$settings->getFavoriteFieldName()]);
                if ($USER->IsAuthorized()) {
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
            }
        }
        return new \Bitrix\Main\EventResult(\Bitrix\Main\EventResult::SUCCESS);
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

    private static function iblock_elemetns($value): array
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
            $service = new \Mindbox\Loyalty\Services\ProductListService($settings);
            $customer = (is_object($USER) && $USER->isAuthorized()) ? new \Mindbox\Loyalty\Models\Customer((int)$USER->getID()) : null;
            foreach ($favorites as $favorite) {
                $service->editFavourite(
                    new \Mindbox\Loyalty\Models\Product((int)$favorite, $settings),
                    1,
                    $customer
                );
            }

        } catch (\Bitrix\Main\ObjectNotFoundException|\Mindbox\Loyalty\Exceptions\ErrorCallOperationException|LoaderException $e) {
        }
    }

    private static function clearWishList(): void
    {
        global $USER;

        $settings = SettingsFactory::create();
        $service = new \Mindbox\Loyalty\Services\ProductListService($settings);
        $customer = (is_object($USER) && $USER->isAuthorized()) ? new \Mindbox\Loyalty\Models\Customer((int)$USER->getID()) : null;
        if ($customer) {
            $service->clearFavourite($customer);
        }
    }
}