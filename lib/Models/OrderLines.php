<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Models;

use Bitrix\Sale\Basket;
use Mindbox\Loyalty\Exceptions\EmptyLineException;
use Mindbox\Loyalty\Support\Settings;

class OrderLines
{
    protected Basket $basket;
    protected Settings $settings;

    public function __construct(Basket $basket, Settings $settings)
    {
        $this->basket = $basket;
        $this->settings = $settings;
    }

    public function getData()
    {
        OrderLine::resetNumber();
        /** @var \Bitrix\Sale\BasketItem $basketItem */
        $lines = [];
        foreach ($this->basket as $basketItem) {
            if ($basketItem->canBuy() && !$basketItem->isDelay() && $basketItem->getQuantity() > 0) {
                $lines[] = (new OrderLine($basketItem, $this->settings))->getData();
            }
        }

        if ($lines === []) {
            throw new EmptyLineException();
        }

        return $lines;
    }
}