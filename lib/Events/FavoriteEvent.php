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
        if (
            !LoyalityEvents::checkEnableEvent(LoyalityEvents::ADD_FAVORITE)
            || !LoyalityEvents::checkEnableEvent(LoyalityEvents::REMOVE_FROM_FAVORITE)
        ) {
            return new \Bitrix\Main\EventResult(\Bitrix\Main\EventResult::SUCCESS);
        }

        $settings = SettingsFactory::create();
        $userGroupArray = \Bitrix\Main\UserTable::getUserGroupIds((int)$arUser['ID']);
        
        if (
            !LoyalityEvents::checkEnableEventsForUserGroup(LoyalityEvents::ADD_FAVORITE, $userGroupArray, $settings)
            || !LoyalityEvents::checkEnableEventsForUserGroup(LoyalityEvents::REMOVE_FROM_FAVORITE, $userGroupArray, $settings)
        ) {
            return new \Bitrix\Main\EventResult(\Bitrix\Main\EventResult::SUCCESS);
        }

        global $USER;

        if (array_key_exists($settings->getFavoriteFieldName(), $arUser)) {
            if (method_exists(self::class, $settings->getFavoriteType())) {

                $favorites = self::{$settings->getFavoriteType()}($arUser[$settings->getFavoriteFieldName()]);
                if ($USER->IsAuthorized()) {
                    if ($favorites) {
                        self::setWishList($favorites);
                    } else {
                        self::clearWishList();
                    }
                }
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