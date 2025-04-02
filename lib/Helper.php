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
            return true;
        }

        if ($USER instanceof \CUser && $USER->IsAuthorized()) {
            return false;
        }

        return true;
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
    public static function isDisableProccessingForUser(int $userId, Settings $settings): bool
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

    public static function isAdminSection(): bool
    {
        $request = \Bitrix\Main\Context::getCurrent()->getRequest();

        return ($request->isAdminSection() || str_starts_with($request->getRequestedPageDirectory(), '/bitrix/admin/'));
    }

    public static function getProductPrices(int $productId): array
    {
        $iterPrices = \Bitrix\Catalog\PriceTable::getList([
            'select' => ['*'],
            'filter' => [
                '=PRODUCT_ID' => $productId,
            ],
            'order'  => ['CATALOG_GROUP_ID' => 'ASC']
        ]);

        $allProductPrices = [];
        while ($price = $iterPrices->fetch()) {
            $allProductPrices[] = $price;
        }

        return $allProductPrices;
    }

    public static function getBasePriceId(): int
    {
        $basePrice = \Bitrix\Catalog\GroupTable::getList([
            'filter' => ['BASE' => 'Y'],
            'select' => ['ID']
        ])->fetch();

        return (int) $basePrice['ID'];
    }

    public static function getMatchByCode($code, $matches = [])
    {
        if (empty($matches)) {
            return '';
        }

        $matches = array_change_key_case($matches, CASE_UPPER);
        $code = mb_strtoupper($code);

        if (empty($matches[$code])) {
            return '';
        }

        return $matches[$code];
    }

    public static function parseDomainName($domain): array
    {
        $domainParts = explode('.', $domain);

        if (count($domainParts) < 2) {
            return [];
        }

        $domainZone = array_pop($domainParts);
        return [implode('.', $domainParts), $domainZone];
    }
}