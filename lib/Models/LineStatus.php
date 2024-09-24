<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Models;

use Bitrix\Sale\BasketItem;
use Mindbox\Loyalty\Support\Settings;

class LineStatus
{
    protected static string $defaultStatus = 'TECH_CREATE_ORDER';
    protected BasketItem $basketItem;
    protected Settings $settings;
    protected ?string $status;

    public function __construct(BasketItem $basketItem, Settings $settings)
    {
        $this->basketItem = $basketItem;
        $this->settings = $settings;

        $this->status = $this->loadStatus();
    }

    protected function loadStatus()
    {
        $order = $this->basketItem->getBasket()->getOrder();

        if ($order instanceof \Bitrix\Sale\Order && $order->getField('ID') !== null) {
            $orderStatus = (string) $order->getField('STATUS_ID');
            $matchStatuses = $this->settings->getOrderStatusFieldsMatch();

            if ($matchStatuses[$orderStatus]) {
                return $matchStatuses[$orderStatus];
            }
        }

        return $this->getStartStatus();
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getData(): array
    {
        return [
            'ids' => [
                'externalId' => $this->getStatus()
            ]
        ];
    }

    protected function getStartStatus()
    {
        $matchStatuses = $this->settings->getOrderStatusFieldsMatch();

        if ($matchStatuses[self::$defaultStatus]) {
            return $matchStatuses[self::$defaultStatus];
        }

        return self::$defaultStatus;
    }
}