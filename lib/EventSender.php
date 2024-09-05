<?php

declare(strict_types=1);

namespace Mindbox\Loyalty;

use Bitrix\Main\EventManager;
use Bitrix\Sale\BasketItem;
use Mindbox\Loyalty\Support\Settings;

final class EventSender
{
    const MODULE_NAME = 'mindbox.loyalty';

    public static function callEventOnCustomPromotionsBasketItem(BasketItem $basketItem, Settings $settings): array
    {
        $result = [];

        if (EventManager::getInstance()->findEventHandlers(self::MODULE_NAME, 'OnCustomPromotionsBasketItem')) {
            $fields = [
                'ENTYTY' => $basketItem,
                'SETTINGS' => $settings,
                'VALUE' => []
            ];

            /** @var \Bitrix\Main\Entity\Event $event */
            $event = new \Bitrix\Main\Event(self::MODULE_NAME, 'OnCustomPromotionsBasketItem', $fields);
            $event->send();

            if ($event->getResults()) {
                /** @var \Bitrix\Main\EventResult $eventResult */
                foreach($event->getResults() as $eventResult) {
                    if ($eventResult->getType() !== \Bitrix\Main\EventResult::SUCCESS) {
                        continue;
                    }

                    $eventResultData = $eventResult->getParameters();
                    if (!empty($eventResultData['VALUE'])) {
                        $result[] = $eventResultData['VALUE'];
                    }
                }
            }
        }

        return $result;
    }
}