<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Events;

use Bitrix\Main\Page\Asset;
use Mindbox\Loyalty\Support\LoyalityEvents;

class CommonEvent
{
    public static function OnProlog()
    {
        if (!LoyalityEvents::checkEnableEvent(LoyalityEvents::INCLUDE_TRACKER)) {
            return;
        }

        $jsString = '<script data-skip-moving="true"> mindbox = window.mindbox || function() { mindbox.queue.push(arguments); }; mindbox.queue = mindbox.queue || []; mindbox("create"); </script>';
        $jsString .= '<script data-skip-moving="true" src="https://api.mindbox.ru/scripts/v1/tracker.js" async></script>';
        Asset::getInstance()->addString($jsString, true);
    }
}