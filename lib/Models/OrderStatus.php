<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Models;

use Bitrix\Sale\Order;
use Mindbox\Loyalty\Exceptions\NotMatchedOrderStatuses;
use Mindbox\Loyalty\Support\Settings;

class OrderStatus
{
    protected static string $defaultStatus = 'TECH_CREATE_ORDER';

    protected Order $order;
    protected Settings $settings;
    protected ?string $status;

    public function __construct(Order $order, Settings $settings)
    {
        $this->order = $order;
        $this->settings = $settings;

        $this->status = $this->loadStatus();
    }

    protected function loadStatus()
    {
        if ($this->order->getField('ID') !== null) {
            $orderStatus = (string) $this->order->getField('STATUS_ID');
            $matchStatuses = $this->settings->getOrderStatusFieldsMatch();

            if ($matchStatuses[$orderStatus]) {
                return $matchStatuses[$orderStatus];
            }
        }

        return self::$defaultStatus;
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

    /**
     * @throws NotMatchedOrderStatuses
     */
    public static function getCancelStatus(Settings $settings): string
    {
        $orderStatus = 'CANCEL';
        $matchStatuses = $settings->getOrderStatusFieldsMatch();

        if ($matchStatuses[$orderStatus]) {
            return $matchStatuses[$orderStatus];
        }

        throw new NotMatchedOrderStatuses('Cancel status not matched');
    }
}