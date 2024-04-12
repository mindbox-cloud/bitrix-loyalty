<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Models;

use Bitrix\Sale\Basket;
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
        $lines = [];
        OrderLine::resetNumber();
        /** @var \Bitrix\Sale\BasketItem $basketItem */
        foreach ($this->basket as $basketItem) {
            $lines[] = (new OrderLine($basketItem, $this->settings))->getData();
        }

        return $lines;
    }
}