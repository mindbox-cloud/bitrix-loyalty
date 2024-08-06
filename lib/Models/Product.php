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
    private mixed $externalId;

    public function __construct(int $productId, Settings $settings)
    {
        $this->productId = $productId;
        $this->settings = $settings;
        $this->externalId = \Mindbox\Loyalty\Feed\Helper::getElementCode($productId);
    }

    public function getExternalId(): mixed
    {
        return $this->externalId;
    }

    public function getPrice(): ?float
    {
        static $basePrices = [];

        if (!isset($basePrices[$this->productId])) {
            $iterPrices = \Bitrix\Catalog\PriceTable::getList([
                'select' => ['PRICE'],
                'filter' => [
                    '=PRODUCT_ID' => $this->productId,
                    '=CATALOG_GROUP_ID' => $this->settings->getBasePriceId()
                ],
                'limit' => 1
            ]);

            if ($price = $iterPrices->fetch()) {
                $basePrices[$this->productId] = (float) $price['PRICE'];
            } else {
                $iterPrices = \Bitrix\Catalog\PriceTable::getList([
                    'select' => ['PRICE'],
                    'filter' => [
                        '=PRODUCT_ID' => $this->productId,
                        'CATALOG_GROUP.BASE' => 'Y'
                    ],
                    'limit' => 1
                ]);

                $price = $iterPrices->fetch();

                $basePrices[$this->productId] = (float) $price['PRICE'];
            }
        }

        return $basePrices[$this->productId];
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