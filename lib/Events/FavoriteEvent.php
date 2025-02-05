<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Events;

use Bitrix\Main\Diag\Debug;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Mindbox\Loyalty\Exceptions\ErrorCallOperationException;
use Mindbox\Loyalty\Operations\EditFavourite;
use Mindbox\Loyalty\Support\LoyalityEvents;
use Mindbox\Loyalty\Support\SettingsFactory;

class FavoriteEvent
{
    public static function onAfterUserUpdate($arUser)
    {
        if (!LoyalityEvents::checkEnableEvent(LoyalityEvents::EDIT_USER)) {
            return true;
        }

        $settings = SettingsFactory::create();

        $userGroupArray = \Bitrix\Main\UserTable::getUserGroupIds((int) $arUser['ID']);
        if (!LoyalityEvents::checkEnableEventsForUserGroup(LoyalityEvents::EDIT_USER, $userGroupArray, $settings)) {
            return true;
        }

        global $USER;

        if (array_key_exists($settings->getFavoriteFieldName(), $arUser)) {
            if (method_exists(self::class, $settings->getFavoriteType() . 'Prepare')) {

                $favorites = self::{$settings->getFavoriteType() . 'Prepare'}($arUser[$settings->getFavoriteFieldName()]);
                Debug::writeToFile($favorites, 'fav', '/local/debug.log');
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

    private static function commaPrepare($value): array
    {
        if (!is_string($value)) {
            return [];
        }
        if (preg_match('/^\d+$|^((\d+)(,?))+\d+$/', $value)) {
            return explode(',', $value);
        }
        return [];
    }

    private static function pipePrepare($value): array
    {
        if (!is_string($value)) {
            return [];
        }
        if (preg_match('/^\d+$|^((\d+)(\|?))+\d+$/', $value)) {
            return explode('|', $value);
        }
        return [];
    }

    private static function semicolonPrepare($value): array
    {
        if (!is_string($value)) {
            return [];
        }
        if (preg_match('/^\d+$|^((\d+)(;?))+\d+$/', $value)) {
            return explode(';', $value);
        }
        return [];
    }
    private static function serialize_arrayPrepare($value): array
    {
        if (!is_string($value)) {
            return [];
        }
        if (is_array(unserialize($value, ['allowed_classes' => false]))) {
            return unserialize($value, ['allowed_classes' => false]);
        }
        return [];
    }

    private static function iblock_elemetnsPrepare($value): array
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
            Loader::includeModule('sale');
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

        try {
            Loader::includeModule('sale');
        } catch (LoaderException $e) {
        }
        $settings = SettingsFactory::create();
        $service = new \Mindbox\Loyalty\Services\ProductListService($settings);
        $customer = (is_object($USER) && $USER->isAuthorized()) ? new \Mindbox\Loyalty\Models\Customer((int)$USER->getID()) : null;
        $service->clearFavourite($customer);

    }
}