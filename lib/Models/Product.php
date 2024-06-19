<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Models;

use Mindbox\Loyalty\Support\Settings;
use Bitrix\Iblock\ElementTable;
use Bitrix\Main\EventResult;
use Bitrix\Main\Event;

class Product
{
    private int $productId;
    private Settings $settings;
    /**
     * @var null
     */
    private string $externalId;

    public function __construct(int $productId, Settings $settings)
    {
        $this->productId = $productId;
        $this->settings = $settings;
        $this->externalId = $this->loadExternal();
    }

    public function getExternalId(): string
    {
        return $this->externalId;
    }

    public function loadExternal()
    {
        $fields = [
            'ID' => $this->productId,
            'IBLOCK_ID' => null,
            'VALUE' => $this->productId
        ];

        $iterator = ElementTable::getList([
            'filter' => ['=ID' => $this->productId],
            'select' => ['IBLOCK_ID', 'XML_ID'],
            'limit' => 1
        ]);

        if ($el = $iterator->fetch()) {
            $fields['IBLOCK_ID'] = $el['IBLOCK_ID'];
            $fields['VALUE'] = !empty($el['XML_ID']) ? $el['XML_ID'] : $this->productId;
        }

        $event = new Event($this->settings->getModuleId(), 'onGetProductExternal', $fields);
        $event->send();

        foreach ($event->getResults() as $eventResult) {
            if ($eventResult->getType() !== EventResult::SUCCESS) {
                continue;
            }

            if ($eventResultData = $eventResult->getParameters()) {
                if (isset($eventResultData['VALUE']) && $eventResultData['VALUE'] != $fields['VALUE']) {
                    $fields['VALUE'] = $eventResultData['VALUE'];
                }
            }
        }

        return (string)$fields['VALUE'];
    }

    public function getPrice(): ?float
    {
        $return = null;

        $getPrice = \Bitrix\Catalog\Model\Price::getList([
            'filter'=>[
                'CATALOG_GROUP_ID' => $this->settings->getBasePriceId(),
                'PRODUCT_ID' => $this->productId
            ]
        ]);

        if ($el = $getPrice->fetch()) {
            $return = (float)$el['PRICE'];
        }

        return $return;
    }

    public function getIds(): array
    {
        return [
            $this->settings->getExternalProductId() => $this->getExternalId()
        ];
    }

    public function getData(): array
    {
        return [
            'ids' => $this->getIds()
        ];
    }
}