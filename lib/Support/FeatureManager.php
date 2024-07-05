<?php
declare(strict_types=1);

namespace Mindbox\Loyalty\Support;

class FeatureManager
{
    private static array $disabledHandlers = [];
    private static array $forceEnabledHandlers = [];
    private static array $enabledAutoSubscribePoints = [];
    private static array $unsubscribePoints = [];

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

    public static function enableAutoSubscribe(string $pointOfContact): void
    {
        self::$enabledAutoSubscribePoints[] = $pointOfContact;
    }

    public static function getAutoSubscribePoints(): array
    {
        return self::$enabledAutoSubscribePoints;
    }

    public static function unsubscribePoint(string $pointOfContact): void
    {
        self::$unsubscribePoints[] = $pointOfContact;
    }

    public static function getUnsubscribePoints(): array
    {
        return self::$unsubscribePoints;
    }
}
