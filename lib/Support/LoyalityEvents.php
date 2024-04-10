<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Support;


use Bitrix\Main\Localization\Loc;

class LoyalityEvents
{
    public const AUTH = 'auth';
    public const REGISTRARION = 'registration';
    public const EDIT_USER = 'edit_user';

    public static function getAll(): array
    {
        $result = [];
        foreach ((new \ReflectionClass(self::class))->getConstants() as $constant) {
            $result[$constant] = Loc::getMessage('MINDBOX_LOYALTY_DISABLE_EVENTS_' . $constant) ?: $constant;
        }
        return $result;
    }

    public static function checkDisableEvents(string $eventName): bool
    {
        $settings = SettingsFactory::create();
        $fields = explode(',', $settings->getDisableEvents());
        return in_array($eventName, $fields);
    }

    public static function checkAuth(): bool
    {
        return LoyalityEvents::checkDisableEvents(self::AUTH);
    }

    public static function checkRegistration(): bool
    {
        return LoyalityEvents::checkDisableEvents(self::REGISTRARION);
    }

    public static function checkEditUser(): bool
    {
        return LoyalityEvents::checkDisableEvents(self::EDIT_USER);
    }
}
