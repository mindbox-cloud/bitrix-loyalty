<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Controllers;

use Bitrix\Main\Engine\ActionFilter\Authentication;
use Bitrix\Main\Engine\ActionFilter\Csrf;
use Bitrix\Main\Localization\Loc;

class FeedController extends \Bitrix\Main\Engine\Controller
{
    public function configureActions()
    {
        return [
            'update' => [
                'prefilters' => [
                    new Csrf(),
                    new Authentication()
                ]
            ]
        ];
    }

    public function updateAction(string $siteId)
    {
        $iterSites = \Bitrix\Main\SiteTable::getList([
            'filter' => [],
            'select' => ['LID'],
        ]);

        $isReturned = true;
        foreach ($iterSites as $site) {
            if ($site['LID'] === $siteId) {
                $isReturned = false;
            }
        }

        if ($isReturned) {
            return ['message' => Loc::getMessage('FEED_IS_RUNNING')];
        }

        $iterator = \CAgent::GetList(
            [],
            [
                'MODULE_ID' => 'mindbox.loyalty',
                'NAME' => '\Mindbox\Loyalty\Feed\FeedGenerator::generate(\'' . $siteId . '%',
            ]
        );

        if ($iterator->Fetch()) {
            return ['message' => Loc::getMessage('FEED_IS_RUNNING')];
        }

        $agent = \CAgent::AddAgent(
            "\Mindbox\Loyalty\Feed\FeedGenerator::generate('$siteId');",
            'mindbox.loyalty'
        );

        if ($agent) {
            return ['message' => Loc::getMessage('FEED_IS_RUN')];
        }
        return ['message' => Loc::getMessage('FEED_IS_RUNNING')];
    }
}