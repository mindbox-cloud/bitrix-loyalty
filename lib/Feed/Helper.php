<?php

namespace Mindbox\Loyalty\Feed;

class Helper
{
    public static function getSectionCode($sectionId): mixed
    {
        $fields = [
            'ID' => (int) $sectionId,
            'IBLOCK_ID' => null,
            'VALUE' => $sectionId
        ];

        $iterator = \Bitrix\Iblock\SectionTable::getList([
            'filter' => ['=ID' => (int) $sectionId],
            'select' => ['IBLOCK_ID', 'XML_ID'],
            'limit' => 1
        ]);

        if ($arSection = $iterator->fetch()) {
            $fields['IBLOCK_ID'] = $arSection['IBLOCK_ID'];
            $fields['VALUE'] = !empty($arSection['XML_ID']) ? $arSection['XML_ID'] : $sectionId;
        }

        $event = new \Bitrix\Main\Event('mindbox.loyalty', 'onGetSectionCode', $fields);
        $event->send();

        foreach ($event->getResults() as $eventResult) {
            if ($eventResult->getType() !== \Bitrix\Main\EventResult::SUCCESS) {
                continue;
            }

            if ($eventResultData = $eventResult->getParameters()) {
                if (isset($eventResultData['VALUE']) && $eventResultData['VALUE'] != $fields['VALUE']) {
                    $fields['VALUE'] = $eventResultData['VALUE'];
                }
            }
        }

        return $fields['VALUE'];
    }

    public static function getElementCode($elementId): mixed
    {
        $fields = [
            'ID' => (int) $elementId,
            'IBLOCK_ID' => null,
            'VALUE' => $elementId
        ];

        $iterator = \Bitrix\Iblock\ElementTable::getList([
            'filter' => ['=ID' => (int) $elementId],
            'select' => ['IBLOCK_ID', 'XML_ID'],
            'limit' => 1
        ]);

        if ($el = $iterator->fetch()) {
            $fields['IBLOCK_ID'] = $el['IBLOCK_ID'];
            $fields['VALUE'] = !empty($el['XML_ID']) ? $el['XML_ID'] : $elementId;
        }

        $event = new \Bitrix\Main\Event('mindbox.loyalty', 'onGetProductExternal', $fields);
        $event->send();

        foreach ($event->getResults() as $eventResult) {
            if ($eventResult->getType() !== \Bitrix\Main\EventResult::SUCCESS) {
                continue;
            }

            if ($eventResultData = $eventResult->getParameters()) {
                if (isset($eventResultData['VALUE']) && $eventResultData['VALUE'] != $fields['VALUE']) {
                    $fields['VALUE'] = $eventResultData['VALUE'];
                }
            }
        }

        return $fields['VALUE'];
    }
}