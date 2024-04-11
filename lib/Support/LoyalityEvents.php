<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Support;


use Bitrix\Main\Localization\Loc;

class LoyalityEvents
{
    public const AUTH = 'auth';
    public const REGISTRATION = 'registration';
    public const EDIT_USER = 'edit_user';

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
        return in_array($eventName, $settings->getEnableEvents());
    }
}
