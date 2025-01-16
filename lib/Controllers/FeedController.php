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
        $agent = \CAgent::AddAgent(
            "\Mindbox\Loyalty\Feed\FeedGenerator::generate('$siteId');",
            'mindbox.loyalty',
        );
        if ($agent) {
            return ['message' => Loc::getMessage('FEED_IS_RUN')];
        }
        return ['message' => Loc::getMessage('FEED_IS_RUNNING')];
    }
}