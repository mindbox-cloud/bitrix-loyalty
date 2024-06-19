<?php
declare(strict_types=1);

namespace Mindbox\Loyalty\Support;

class FeatureManager
{
    private static array $disabledHandlers = [];
    private static array $forceEnabledHandlers = [];

    public static function disableHandler(string $loyaltyEvent): void
    {
        self::$disabledHandlers[$loyaltyEvent] = true;
    }

    public static function enableHandler(string $loyaltyEvent): void
    {
        if (isset(self::$disabledHandlers[$loyaltyEvent])) {
            unset(self::$disabledHandlers[$loyaltyEvent]);
        }
    }

    public static function isHandlerDisabled(string $loyaltyEvent): bool
    {
        return isset(self::$disabledHandlers[$loyaltyEvent]);
    }

    public static function forceEnableHandler(string $loyaltyEvent): void
    {
        self::$forceEnabledHandlers[$loyaltyEvent] = true;
    }

    public static function isForceHandlerEnabled(string $loyaltyEvent): bool
    {
        return isset(self::$forceEnabledHandlers[$loyaltyEvent]);
    }
    public static function enableConfirmPhone(): void
    {
        $session = \Bitrix\Main\Application::getInstance()->getSession();
        $session->set('mindbox_need_confirm_phone', true);
    }
}
