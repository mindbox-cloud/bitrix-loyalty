<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Models;


use Bitrix\Sale\BasketItem;
use Bitrix\Sale\Order;
use Mindbox\Loyalty\Support\Settings;

class OrderLine
{
    protected static $storage;
    protected static $lineNumberCount = 0;

    private BasketItem $basketItem;
    private RequestedPromotions $requestedPromotions;
    private Settings $settings;
    private Product $product;

    private LineStatus $status;

    protected ?int $lineNumber = null;

    public function __construct(BasketItem $basketItem, Settings $settings)
    {
        if (self::$storage === null) {
            self::$storage = new \WeakMap();
        }

        $this->basketItem = $basketItem;
        $this->settings = $settings;
        $this->product = new Product($basketItem->getProductId(), $settings);
        $this->status = new LineStatus($basketItem, $settings);

        $this->requestedPromotions = $this->createRequestedPromotions();
    }

    public static function resetNumber()
    {
        self::$lineNumberCount = 0;
    }

    public function getBasePricePerItem()
    {
        static $basePrices = [];

        if (!isset($basePrices[$this->getLineId()])) {
            $iterPrices = \Bitrix\Catalog\PriceTable::getList([
                'select' => ['PRICE'],
                'filter' => [
                    '=PRODUCT_ID' => $this->basketItem->getProductId(),
                    '=CATALOG_GROUP_ID' => $this->getBasePriceId()
                ],
                'limit' => 1
            ]);

            if ($price = $iterPrices->fetch()) {
                $basePrices[$this->getLineId()] = $price['PRICE'];
            } else {
                $basePrices[$this->getLineId()] = $this->basketItem->getBasePrice();
            }
        }

        return $basePrices[$this->getLineId()];
    }

    public function getQuantity()
    {
        return $this->basketItem->getQuantity();
    }

    public function getLineId()
    {
        return $this->basketItem->getBasketCode();
    }

    public function getLineNumber()
    {
        if ($this->lineNumber === null) {
            self::$lineNumberCount++;
            $this->lineNumber = self::$lineNumberCount;
        }

        return $this->lineNumber;
    }

    protected function createRequestedPromotions()
    {
        $order = $this->basketItem->getBasket()->getOrder();

        return self::$storage[$order] ??= new RequestedPromotions($order, $this->settings);
    }

    public function getProduct(): Product
    {
        return $this->product;
    }

    public function getRequestedPromotions(): RequestedPromotions
    {
        return $this->requestedPromotions;
    }

    public function getStatus(): LineStatus
    {
        return $this->status;
    }

    public function getData(): array
    {
        return array_filter([
            'basePricePerItem' => $this->getBasePricePerItem(),
            'quantity' => $this->getQuantity(),
            'lineId' => $this->getLineId(),
            'lineNumber' => $this->getLineNumber(),
            'product' => $this->getProduct()->getData(),
            'status' => $this->getStatus()->getData(),
            'requestedPromotions' => $this->getRequestedPromotions()->getDataForBasketItem($this->basketItem)
        ]);
    }

    protected function getBasePriceId(): int
    {
        $basePriceGroupId = (int) $this->settings->getBasePriceId();

        if ($basePriceGroupId !== 0) {
            return $basePriceGroupId;
        }

        $basePrice = \Bitrix\Catalog\GroupTable::getList([
            'filter' => ['BASE' => 'Y'],
            'select' => ['ID']
        ])->fetch();

        return (int) $basePrice['ID'];
    }
}