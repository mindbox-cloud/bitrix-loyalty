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
        $settings = SettingsFactory::create();
        if (!$settings->enabledLoyalty()) {
            return false;
        }

        if (FeatureManager::isHandlerDisabled($eventName)) {
            return false;
        }

        return in_array($eventName, $settings->getEnableEvents());
    }
}
