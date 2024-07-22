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
        $this->externalId = \Mindbox\Loyalty\Feed\Helper::getElementCode($productId);
    }

    public function getExternalId(): string
    {
        return $this->externalId;
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