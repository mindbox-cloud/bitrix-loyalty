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
        return $this->basketItem->getBasePrice();
    }

    public function getQuantity()
    {
        return $this->basketItem->getQuantity();
    }

    public function getLineId()
    {
        return $this->basketItem->getId();
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
}