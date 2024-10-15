<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Support;


use Bitrix\Main\Localization\Loc;

class LoyalityEvents
{
    public const AUTH = 'auth';
    public const REGISTRATION = 'registration';
    public const EDIT_USER = 'edit_user';
    public const CHECK_CHANGE_USER_EMAIL = 'change_user_email';

    public const CALCULATE_DISCOUNT = 'calculate_discount';
    public const CREATE_ORDER = 'create_order';
    public const CONFIRM_ORDER = 'confirm_order';
    public const CHANGE_STATUS_ORDER = 'change_status_order';
    public const CANCEL_ORDER = 'cancel_order';
    public const DELETE_ORDER = 'delete_order';

    public const EDIT_ORDER_TO_ADMIN_PAGE = 'edit_order_to_admin_page';
    public const CREATE_ORDER_TO_ADMIN_PAGE = 'create_order_to_admin_page';

    public const INCLUDE_TRACKER = 'include_tracker';
    public const ADD_CART = 'add_to_cart';
    public const REMOVE_FROM_CART = 'remove_form_cart';
    public const ADD_FAVORITE = 'add_to_favorite';
    public const REMOVE_FROM_FAVORITE = 'remove_from_favorite';
    public const DISCOUNT_FOR_PRICE_TYPE = 'discount_for_price_type';

    public static function getAll(): array
    {
        $result = [];

        foreach ((new \ReflectionClass(self::class))->getConstants() as $constant) {
            $result[$constant] = Loc::getMessage('MINDBOX_LOYALTY_ENABLE_EVENTS_' . $constant) ?: $constant;
        }

        return $result;
    }

    public static function checkEnableEvent(string $eventName): bool
    {
        if (FeatureManager::isForceHandlerEnabled($eventName)) {
            return true;
        }

        $settings = SettingsFactory::create();
        if (!$settings->enabledLoyalty()) {
            return false;
        }

        if (FeatureManager::isHandlerDisabled($eventName)) {
            return false;
        }

        return in_array($eventName, $settings->getEnableEvents());
    }

    public static function checkEnableEventsForUserGroup(string $eventName, array $userGroup = [2], Settings $settings = null): bool
    {
        if ($settings === null) {
            $settings = SettingsFactory::create();
        }

        if (!$settings->enabledLoyalty()) {
            return false;
        }

        static $storage = null;

        if ($storage === null) {
            $storage = [];
            $disabledEvents = $settings->getDisabledEvents();

            foreach ($disabledEvents as $event) {
                $storage[$event['value']][] = $event['key'];
            }
        }

        if (!isset($storage[$eventName])) {
            return true;
        }

        $commonGroups = array_intersect($storage[$eventName], $userGroup);

        return empty($commonGroups);
    }
}
