<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Support;

use Bitrix\Main\Context;

class SettingsFactory
{
    public static function create(): Settings
    {
        if (Context::getCurrent()->getRequest()->isAdminSection()) {
            return self::createByDomain((string) Context::getCurrent()->getRequest()->getHttpHost(), (string) Context::getCurrent()->getRequest()->getRequestedPageDirectory());
        }

        return self::createBySiteId((string) Context::getCurrent()->getSite());
    }

    public static function createBySiteId(string $siteId): Settings
    {
        return Settings::getInstance($siteId);
    }

    public static function createByDomain(string $host, string $directory): Settings
    {
        $domain = \Bitrix\Main\SiteTable::getByDomain($host, $directory);

        if ($domain) {
            return self::createBySiteId($domain['LID']);
        }

        return self::createByDefaultSite();
    }

    public static function createByDefaultSite(): Settings
    {
        $defaultSite = \Bitrix\Main\SiteTable::getList([
            'filter' => ['=ACTIVE' => 'Y', '=DEF' => 'Y'],
            'select' => ['LID'],
        ])->fetch();

        return self::createBySiteId($defaultSite['LID']);
    }
}
