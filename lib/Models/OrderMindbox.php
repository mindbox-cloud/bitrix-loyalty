<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Models;

use Bitrix\Sale\Order;
use Mindbox\DTO\V3\Requests\OrderRequestDTO;
use Mindbox\Helper;
use Mindbox\Loyalty\PropertyCodeEnum;
use Mindbox\Loyalty\Support\SessionStorage;
use Mindbox\Loyalty\Support\Settings;

class OrderMindbox
{
    protected string $testPrefix = 'test-';
    private Order $order;
    private Settings $settings;

    public function __construct(Order $order, Settings $settings)
    {
        $this->order = $order;
        $this->settings = $settings;
    }

    public function getOrder(): Order
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
        /** @var \Bitrix\Sale\Shipment $shipment */
        foreach ($this->order->getShipmentCollection() as $shipment) {
            if ($shipment->isSystem()) {
                continue;
            }

            if ($shipment->getField('CUSTOM_PRICE_DELIVERY') === 'Y') {
                return $this->order->getDeliveryPrice();
            }

            return $shipment->getField('BASE_PRICE_DELIVERY');
        }

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

        return [
            [
                'amount' => $propertyBonus->getValue(),
            ]
        ];
    }

    public function setBonuses(float $value): void
    {
        $propertyBonus = $this->order->getPropertyCollection()->getItemByOrderPropertyCode(PropertyCodeEnum::PROPERTIES_MINDBOX_BONUS);

        if ($propertyBonus instanceof \Bitrix\Sale\PropertyValue) {
            $propertyBonus->setValue($value);
        }
    }

    public function getPromocodes(): array
    {
        $propertyCoupon = $this->order->getPropertyCollection()->getItemByOrderPropertyCode(\Mindbox\Loyalty\PropertyCodeEnum::PROPERTIES_MINDBOX_PROMOCODES);

        if (!$propertyCoupon instanceof \Bitrix\Sale\PropertyValue || empty($propertyCoupon->getValue())) {
            return [];
        }

        $result = [];
        foreach ($propertyCoupon->getValue() as $coupon) {
            if (trim($coupon)) {
                $result[] = [
                    'ids' => [
                        'code' => trim($coupon)
                    ]
                ];
            }
        }

        return $result;
    }

    public function getCoupons(): array
    {
        $propertyCoupon = $this->order->getPropertyCollection()->getItemByOrderPropertyCode(PropertyCodeEnum::PROPERTIES_MINDBOX_PROMO_CODE);

        if (!$propertyCoupon instanceof \Bitrix\Sale\PropertyValue || empty($propertyCoupon->getValue())) {
            return [];
        }

        $coupons = explode(',', $propertyCoupon->getValue());
        $result = [];
        foreach ($coupons as $coupon) {
            if (trim(strval($coupon))) {
                $result[] = [
                    'ids' => [
                        'code' => trim(strval($coupon))
                    ]
                ];
            }
        }

        return $result;
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
            if ($payment->getSum() && $payment->getPaymentSystemId()) {
                $payments[] = array_filter([
                    'type'   => $payment->getPaymentSystemId(),
                    'amount' => $payment->getSum()
                ]);
            }
        }

        return $payments;
    }

    public function getLines(): OrderLines
    {
        if (!isset($this->lines)) {
            $this->lines = new OrderLines($this->order->getBasket(), $this->settings);
        }

        return $this->lines;
    }

    public function getExternalOrderId()
    {
        return $this->order->getField($this->settings->getWebsiteOrderField())
            ? $this->settings->isTestMode()
                ? $this->testPrefix . $this->order->getField($this->settings->getWebsiteOrderField())
                : $this->order->getField($this->settings->getWebsiteOrderField())
            : null;
    }

    public function getIds()
    {
        return array_filter([
            $this->settings->getExternalOrderId() => $this->getExternalOrderId()
        ]);
    }

    public function getCustomFields()
    {
        $customFields = [];

        /** @var \Bitrix\Sale\PropertyValueCollection $propertyCollection */
        $propertyCollection = $this->order->getPropertyCollection();

        $matches = $this->settings->getOrderFieldsMatch();

        /** @var \Bitrix\Sale\PropertyValue $property */
        foreach ($propertyCollection as $property) {
            if (!array_key_exists($property->getPropertyId(), $matches)) {
                continue;
            }
            $value = $property->getValue();

            if (is_array($value) && count($value) >= 1) {
                $value = current($value);
            }

            if ($property->getType() === 'LOCATION' && !empty($value)) {
                if (!\CSaleLocation::checkIsCode($value)) {
                    $value = \CSaleLocation::getLocationCODEbyID($value);
                }

                $arLocs = \CSaleLocation::GetList(
                    [
                        'SORT' => 'ASC',
                        'COUNTRY_NAME_LANG' => 'ASC',
                        'CITY_NAME_LANG' => 'ASC'
                    ],
                    ['CODE' => $value, 'LID' => $this->settings->getSiteId()],
                    false,
                    false,
                    ['REGION_NAME', 'CITY_NAME', 'CODE', 'COUNTRY_NAME']
                )->fetch();

                $value = sprintf('%s, %s', $arLocs['COUNTRY_NAME_LANG'], $arLocs['CITY_NAME_LANG']);
            }

            $customName = \Mindbox\Loyalty\Helper::sanitizeNamesForMindbox($matches[$property->getPropertyId()]);
            $customFields[$customName] = $value;
        }

        $deliveryId = (int) $this->order->getField('DELIVERY_ID');
        /** @var \Bitrix\Sale\ShipmentCollection $shipmentCollection */
        $shipmentCollection = $this->order->getShipmentCollection();
        /** @var \Bitrix\Sale\Shipment $shipment */
        foreach ($shipmentCollection as $shipment) {
            if ($shipment->isSystem()) {
                continue;
            }

            $deliveryId = (int) $shipment->getDeliveryId();
            break;
        }

        if ($deliveryId !== 0) {
            $customFields['deliveryType'] = $deliveryId;
        }

        return array_filter($customFields);
    }

    public function getTotalPrice()
    {
        return SessionStorage::getInstance()->getTotalPrice();
    }

    public function getData(): array
    {
        return array_filter([
            'ids' => $this->getIds(),
            'totalPrice' => $this->getTotalPrice(),
            'lines' => $this->getLines()->getData(),
            'customFields' => $this->getCustomFields(),
            'payments' => $this->getPayments(),
            'coupons' => array_merge($this->getCoupons(), $this->getPromocodes()),
            'bonusPoints' => $this->getBonusPoints(),
            'deliveryCost' => $this->getDeliveryCost(),
            'email' => $this->getEmail(),
            'mobilePhone' => $this->getMobilePhone(),
        ]);
    }
}