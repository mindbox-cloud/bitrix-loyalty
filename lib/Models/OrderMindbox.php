<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Models;

use Bitrix\Sale\Order;
use Mindbox\DTO\V3\Requests\OrderRequestDTO;
use Mindbox\Loyalty\PropertyCodeEnum;
use Mindbox\Loyalty\Support\Settings;

class OrderMindbox
{
    private Order $order;
    private Settings $settings;

    public function __construct(Order $order, Settings $settings)
    {
        $this->order = $order;
        $this->settings = $settings;
    }

    public function getOrder()
    {
        return $this->order;
    }

    public function getEmail(): string
    {
        $propertyEmail = $this->order->getPropertyCollection()->getUserEmail();

        if ($propertyEmail instanceof \Bitrix\Sale\PropertyValue) {
            return (string) $propertyEmail->getValue();
        }

        return '';
    }

    public function getDeliveryCost()
    {
        // todo Переделать на передачу стоимости доставки без скикдки
        return $this->order->getDeliveryPrice();
    }

    public function getMobilePhone(): string
    {
        $propertyPhone = $this->order->getPropertyCollection()->getPhone();

        if ($propertyPhone instanceof \Bitrix\Sale\PropertyValue) {
            return (string)  $propertyPhone->getValue();
        }

        return '';
    }

    public function getBonusesValue()
    {
        $propertyBonus = $this->order->getPropertyCollection()->getItemByOrderPropertyCode(PropertyCodeEnum::PROPERTIES_MINDBOX_BONUS);

        if ($propertyBonus instanceof \Bitrix\Sale\PropertyValue) {
            return (float) $propertyBonus->getValue();
        }

        return 0;
    }

    public function getBonusPoints(): array
    {
        $propertyBonus = $this->order->getPropertyCollection()->getItemByOrderPropertyCode(PropertyCodeEnum::PROPERTIES_MINDBOX_BONUS);

        if (!$propertyBonus instanceof \Bitrix\Sale\PropertyValue || empty($propertyBonus->getValue())) {
            return [];
        }

        return [[
            'amount' => $propertyBonus->getValue(),
        ]];
    }

    public function setBonuses(float $value): void
    {
        $propertyBonus = $this->order->getPropertyCollection()->getItemByOrderPropertyCode(PropertyCodeEnum::PROPERTIES_MINDBOX_BONUS);

        if ($propertyBonus instanceof \Bitrix\Sale\PropertyValue) {
            $propertyBonus->setValue($value);
        }
    }

    public function getCoupons(): array
    {
        $propertyCoupon = $this->order->getPropertyCollection()->getItemByOrderPropertyCode(PropertyCodeEnum::PROPERTIES_MINDBOX_PROMO_CODE);

        if (!$propertyCoupon instanceof \Bitrix\Sale\PropertyValue || empty($propertyCoupon->getValue())) {
            return [];
        }

        return [
            [
                'ids' => [
                    'code' => $propertyCoupon->getValue()
                ]
            ]
        ];
    }

    public function setCoupons(string $value): void
    {
        $propertyCoupon = $this->order->getPropertyCollection()->getItemByOrderPropertyCode(PropertyCodeEnum::PROPERTIES_MINDBOX_PROMO_CODE);

        if ($propertyCoupon instanceof \Bitrix\Sale\PropertyValue) {
            $propertyCoupon->setValue($value);
        }
    }

    public function getPayments(): array
    {
        $payments = [];
        /** @var \Bitrix\Sale\Payment $payment */
        foreach ($this->order->getPaymentCollection() as $payment) {
            $payments[] = array_filter([
                'type'   => $payment->getPaymentSystemId(),
                'amount' => $payment->getSum()
            ]);
        }

        return $payments;
    }

    public function getLines()
    {
        if (!isset($this->lines)) {
            $this->lines = new OrderLines($this->order->getBasket(), $this->settings);
        }

        return $this->lines;
    }

    public function getExternalOrderId()
    {
        return $this->order->getField('ACCOUNT_NUMBER') ? $this->order->getField('ACCOUNT_NUMBER') : '';
    }

    public function getIds()
    {
        return [
            $this->settings->getExternalOrderId() => $this->getExternalOrderId()
        ];
    }

    public function getCustomFields()
    {
        $customFields = [];
        /** @var \Bitrix\Sale\PropertyValueCollection $propertyCollection */
        $propertyCollection = $this->order->getPropertyCollection();

        // todo сделать получение кастом полей
        return array_filter($customFields);
    }

    public function getData(): array
    {
        return array_filter([
            'ids' => $this->getIds(),
            'lines' => $this->getLines()->getData(),
            'customFields' => $this->getCustomFields(),
            'payments' => $this->getPayments(),
            'coupons' => $this->getCoupons(),
            'bonusPoints' => $this->getBonusPoints(),
            'deliveryCost' => $this->getDeliveryCost(),
            'email' => $this->getEmail(),
            'mobilePhone' => $this->getMobilePhone(),
        ]);
    }

    public function getDto()
    {
        return new OrderRequestDTO(array_filter([
            'ids' => $this->getIds(),
            'lines' => $this->getLines()->getData(),
            'customFields' => $this->getCustomFields(),
            'payments' => $this->getPayments(),
            'coupons' => $this->getCoupons(),
            'bonusPoints' => $this->getBonusPoints(),
            'deliveryCost' => $this->getDeliveryCost(),
            'email' => $this->getEmail(),
            'mobilePhone' => $this->getMobilePhone(),
        ]));
    }
}