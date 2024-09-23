<?php
namespace Mindbox\Loyalty\Feed;

use Bitrix\Main\LoaderException;
use Bitrix\Main\SiteTable;
use Mindbox\Loyalty\Support\Settings;
use Mindbox\Loyalty\Support\SettingsFactory;

class FeedGenerator
{
    /**
     * @param string $siteId
     * @return void
     * @throws \Bitrix\Main\ObjectNotFoundException
     */
    public static function generate(string $siteId): void
    {
        if (!$siteId) {
            $siteId = SITE_ID;
        }

        $settings = SettingsFactory::createBySiteId($siteId);
        if (!$settings->enabledFeed()) {
            return;
        }

        try {
            self::requireModules();
        } catch (LoaderException $exception) {
            return;
        }

        $serviceLocator = \Bitrix\Main\DI\ServiceLocator::getInstance();

        /** @var YmlFeedMindbox $feedGenerator */
        $feedGenerator = $serviceLocator->get('mindboxLoyalty.feedGenerator');

        /** @var CatalogRepository $catalogRepository */
        $catalogRepository = $serviceLocator->get('mindboxLoyalty.feedCatalogRepository');

        $catalogRepository->setIblockId($settings->getFeedCatalogId());
        $catalogRepository->setLid($siteId);
        $catalogRepository->setBasePriceId($settings->getFeedBasePriceId());
        $catalogRepository->setStepSize($settings->getFeedChunkSize());
        $catalogRepository->setCatalogPropertyCode($settings->getFeedCatalogProperties());
        $catalogRepository->setOffersPropertyCode($settings->getFeedOffersProperties());

        $feedGenerator->setProtocol($settings->isFeedHttps());
        $feedGenerator->setFeedPath($settings->getFeedPath());
        $feedGenerator->setCatalogRepository($catalogRepository);

        $serverName = self::findServerName($settings, $siteId);

        $feedGenerator->setServerName($serverName);

        $feedGenerator->generateYml();
    }

    public static function findServerName(Settings $settings, string $siteId): string
    {
        $serverName = $settings->getFeedServerName();

        if (!$serverName) {
            $siteIterator = SiteTable::query()
                ->setSelect(['SERVER_NAME'])
                ->setFilter(['LID' => $siteId])
                ->exec()
                ->fetch();

            if ($siteIterator && $siteIterator['SERVER_NAME']) {
                $serverName = $siteIterator['SERVER_NAME'];
            }
        }

        if (!$serverName) {
            $serverName = $_SERVER['SERVER_NAME'];
        }

        if (!$serverName && defined('SITE_SERVER_NAME')) {
            $serverName = SITE_SERVER_NAME;
        }

        return (string)$serverName;
    }

    /**
     * @return void
     * @throws LoaderException
     */
    public static function requireModules(): void
    {
        $modules = [
            'iblock',
            'catalog',
            'currency'
        ];

        foreach ($modules as $module) {
            \Bitrix\Main\Loader::requireModule($module);
        }
    }
}