<?php

namespace Mindbox\Loyalty\Feed;

use Bitrix\Main\SiteTable;
use Bitrix\Main\Type\DateTime;
use Mindbox\Loyalty\Support\SettingsFactory;

class AgentRunner
{
    public static function run(): string
    {
        $siteIterator = SiteTable::query()
            ->setSelect(['ID'])
            ->exec();

        $now = new DateTime();
        foreach ($siteIterator as $site) {
            $siteId = $site['ID'];

            $settings = SettingsFactory::createBySiteId($siteId);
            if (!$settings->enabledFeed()) {
                continue;
            }

            \CAgent::AddAgent(
                "\Mindbox\Loyalty\Feed\FeedGenerator::generate('$siteId');",
               'mindbox.loyalty',
                "N",
                86400,
                $now,
                "Y",
                $now,
                30
            );
        }


        return "\Mindbox\Loyalty\Feed\AgentRunner::run();";
    }
}