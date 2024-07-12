<?php

declare(strict_types=1);

namespace Mindbox\Loyalty;

use Bitrix\Main\Localization\Loc;
use Mindbox\DTO\DTO;
use Bitrix\Main\Config\Option;
use Mindbox\Loyalty\Support\Settings;

class Helper
{
    public static function formatPhone($phone)
    {
        return str_replace([' ', '(', ')', '-', '+'], "", $phone);
    }

    public static function isUserUnAuthorized(): bool
    {
        global $USER;

        if ($USER instanceof \CUser && !$USER->IsAuthorized()) {
            return true;
        }

        if (
            $USER instanceof \CUser
            && $USER->IsAuthorized()
            && \Mindbox\Loyalty\Support\FeatureManager::isUserRegisterAndLogin()
        ) {
            return false;
        }

        if ($USER instanceof \CUser && $USER->IsAuthorized()) {
            return false;
        }

        return true;
    }
    public static function isUserAuthorized(?int $userId): bool
    {
        global $USER;

        if ($USER instanceof \CUser && !$USER->IsAuthorized()) {
            return false;
        }

        if ($userId === null || $userId === 0) {
            return false;
        }

        static $userRegisterDelta = 30;

        $iterUser = \Bitrix\Main\UserTable::query()
            ->where('ID', $userId)
            ->setLimit(1)
            ->setSelect(['DATE_REGISTER'])
            ->exec();

        if ($findUser = $iterUser->fetch()) {
            /** @var \Bitrix\Main\Type\Date|\Bitrix\Main\Type\DateTime $dateRegister */
            $dateRegister = $findUser['DATE_REGISTER'];
            $diff = time() - $dateRegister->getTimestamp();

            // С момента регистрации пользователя прошло меньше $userRegisterDelta времени
            // Считаем что пользователь был создан компонентом sale.order.ajax
            // Такой пользователь должен считаться не авторизованным
            if ($diff > $userRegisterDelta) {
                return true;
            }
        }

        return false;
    }

    public static function GUID()
    {
        if (function_exists('com_create_guid') === true) {
            return strtolower(trim(\com_create_guid(), '{}'));
        }

        return strtolower(sprintf(
            '%04X%04X-%04X-%04X-%04X-%04X%04X%04X',
            mt_rand(0, 65535),
            mt_rand(0, 65535),
            mt_rand(0, 65535),
            mt_rand(16384, 20479),
            mt_rand(32768, 49151),
            mt_rand(0, 65535),
            mt_rand(0, 65535),
            mt_rand(0, 65535)
        ));
    }

    public static function sanitizeNamesForMindbox(string $name): string
    {
        $regexNotChars = '/[^a-zA-Z0-9]/m';
        $regexFirstLetter = '/^[a-zA-Z]/m';

        $name = preg_replace($regexNotChars, '', $name);

        if (!empty($name) && preg_match($regexFirstLetter, $name) === 1) {
            return $name;
        }

        return '';
    }

    /**
     * Проверка, доступен ли данному пользователю процессинг
     *
     * @param int $userId
     * @param Settings $settings
     *
     * @return bool
     */
    public static function isDisableProccessingForUser(int $userId, Settings $settings)
    {
        if ($userId === 0) {
            return false;
        }

        $internalUserGroups = $settings->getInternalGroups();

        if (!is_array($internalUserGroups) || $internalUserGroups === []) {
            return false;
        }

        $userGroup = \Bitrix\Main\UserTable::getUserGroupIds($userId);

        $commonGroups = array_intersect($userGroup, $internalUserGroups);

        return !empty($commonGroups);
    }
}