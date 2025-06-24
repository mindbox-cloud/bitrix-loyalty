<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Services;

use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\BasketItem;
use Bitrix\Sale\Order;
use Mindbox\Loyalty\Exceptions\ResponseErrorException;
use Mindbox\Loyalty\Operations\CalculateCartAdmin;
use Mindbox\Loyalty\Operations\CalculateUnauthorizedCart;
use Mindbox\Loyalty\Helper;
use Mindbox\Loyalty\Models\Customer;
use Mindbox\Loyalty\Models\OrderMindbox;
use Mindbox\Loyalty\Operations\CalculateAuthorizedCart;
use Mindbox\Loyalty\ORM\DeliveryDiscountTable;
use Mindbox\Loyalty\ORM\OrderOperationTypeTable;
use Mindbox\Loyalty\PropertyCodeEnum;
use Mindbox\Loyalty\Support\SessionStorage;
use Mindbox\Loyalty\Support\SettingsFactory;
use Mindbox\MindboxResponse;

class CalculateService
{
    protected \Bitrix\Main\DI\ServiceLocator $serviceLocator;
    protected SessionStorage $sessionStorage;

    public function __construct()
    {
        $this->serviceLocator = \Bitrix\Main\DI\ServiceLocator::getInstance();
        $this->sessionStorage = SessionStorage::getInstance();
    }

    public function calculateOrder(Order $order)
    {
        if (Helper::isAdminSection()) {
            $response = $this->calculateOrderAdmin($order);
        } elseif ($order->isNew()) {
            if (Helper::isUserUnAuthorized()) {
                $response = $this->calculateUnauthorizedOrder($order);
            } else {
                $response = $this->calculateAuthorizedOrder($order);
            }
        } else {
            $type = OrderOperationTypeTable::getOrderType((string) $order->getId());

            if ($type === OrderOperationTypeTable::OPERATION_TYPE_AUTH) {
                $response = $this->calculateAuthorizedOrder($order);
            } else {
                $response = $this->calculateUnauthorizedOrder($order);
            }
        }

        if ($response->isError()) {
            throw new ResponseErrorException();
        }

        $orderResponseDTO = $response->getResult()->getOrder();

        $orderData = $orderResponseDTO->getFieldsAsArray();

        // расчетная стоимость заказа из МБ
        $mbTotalPrice = $orderData['totalPrice'] ?? $order->getPrice();
        $this->sessionStorage->setTotalPrice(floatval($mbTotalPrice));

        // проверяем списанные бонусы, чтобы не списывали больше доступного
        if (isset($orderData['totalBonusPointsInfo'])) {
            $totalBonusPointInfo = $orderData['totalBonusPointsInfo'];
            if (!empty($totalBonusPointInfo) && $totalBonusPointInfo['availableAmountForCurrentOrder'] < $this->sessionStorage->getPayBonuses()) {
                // Списываю за заказ, больше чем доступно
                $this->sessionStorage->setPayBonuses(intval($totalBonusPointInfo['availableAmountForCurrentOrder']));
            }

            // Доступно для заказа
            $this->sessionStorage->setOrderAvailableBonuses(intval($totalBonusPointInfo['availableAmountForCurrentOrder']));
            // Всего бонусов
            $this->sessionStorage->setBonusesBalanceAvailable(intval($totalBonusPointInfo['balance']['available']));
            unset($totalBonusPointInfo);
        } else {
            $this->sessionStorage->setBonusesBalanceAvailable(0);
            $this->sessionStorage->setOrderAvailableBonuses(0);
            $this->sessionStorage->setPayBonuses(0);
        }

        // проверяем начисление бонусов за заказ
        if (isset($orderData['bonusPointsChanges']) && is_array($orderData['bonusPointsChanges'])) {
            $bonusPointsChanges = current($orderData['bonusPointsChanges']);
            // Будет начислено за заказ
            $earnedAmount = $bonusPointsChanges['earnedAmount'] ?? 0;
            $this->sessionStorage->setOrderEarnedBonuses(intval($earnedAmount));

            unset($bonusPointsChanges, $earnedAmount);
        }

        // проверяем, применение промокода
        if (isset($orderData['couponsInfo']) && is_array($orderData['couponsInfo'])) {
            $index = 0;
            foreach ($orderData['couponsInfo'] as $couponInfo) {
                $setCouponError = null;
                $couponCode = (string) $couponInfo['coupon']['ids']['code'];

                if ($couponInfo['coupon']['status'] === 'NotFound') {
                    $setCouponError = Loc::getMessage('MINDBOX_LOYALTY_COUPON_NOT_FOUND');
                } elseif ($couponInfo['coupon']['status'] === 'CanNotBeUsedForCurrentOrder' || $couponInfo['coupon']['status'] === 'AlreadyNotActive') {
                    $setCouponError = Loc::getMessage('MINDBOX_LOYALTY_COUPON_CAN_NOT_BE_USER');
                } elseif ($couponInfo['coupon']['status'] === 'Used') {
                    $setCouponError = Loc::getMessage('MINDBOX_LOYALTY_COUPON_USED');
                }

                if ($setCouponError !== null && $index === 0) {
                    $this->sessionStorage->setPromocodeError($setCouponError);
                }

                $this->sessionStorage->setPromocodeData($couponCode, [
                    'apply' => $setCouponError === null,
                    'error' => $setCouponError
                ]);
                $index++;
                unset($couponsInfo, $setCouponError, $couponCode);
            }
        }

        /** функционал применения скидки на корзину Mindbox  */
        $mindboxBasket = [];
        $basket = $order->getBasket();
        foreach ($orderData['lines'] as $line) {
            $basketCode = $line['lineId'];
            $discountedPrice = (float) $line['discountedPriceOfLine'];
            $quantity = (float) $line['quantity'];

            // todo необходимо реализовать скидку в МБ, которое бы нарушало данное условие
            if (!isset($mindboxBasket[$basketCode])) {
                $basketPrice = $discountedPrice / $quantity;

                /**
                 * @var \Bitrix\Sale\BasketItem $basketItem - Элемент корзины
                 * @var \Bitrix\Sale\BasketPropertiesCollectionBase $collection - Коллекция свойств
                 */
                $basketItem = $basket->getItemByBasketCode($basketCode);
                $collection = $basketItem->getPropertyCollection();
                $propertyValues = $collection->getPropertyValues();

                if (!isset($propertyValues[PropertyCodeEnum::BASKET_PROPERTY_CODE])) {
                    $propertyItem = $collection->createItem();
                } else {
                    $propertyItem = $collection->getPropertyItemByValue($propertyValues[PropertyCodeEnum::BASKET_PROPERTY_CODE]);
                }

                $result = $propertyItem->setFields([
                    'NAME' => 'Mindbox',
                    'CODE' => PropertyCodeEnum::BASKET_PROPERTY_CODE,
                    'VALUE' => $basketPrice,
                    'SORT' => 100
                ]);

                $mindboxBasket[$basketCode] = [
                    'price' => $basketPrice,
                    'quantity' => $quantity,
                    'lineId' => $basketCode,
                ];
            }
        }

        /** функционал применения скидки на доставку Mindbox  */
        $deliveryPrice = $orderData['deliveryCost'];
        if ((int) $order->getField('USER_ID') > 0) {
            $fUserId = \Bitrix\Sale\Fuser::getIdByUserId((int)$order->getField('USER_ID'));
        } else {
            $fUserId = \Bitrix\Sale\Fuser::getId();
        }

        $deliveryFilter = [
            'FUSER_ID' => $fUserId ?: null,
            'DELIVERY_ID' => $order->getField('DELIVERY_ID'),
            'ORDER_ID' => $order->getField('ID')
        ];

        if (isset($deliveryPrice)
            && ($findRow = DeliveryDiscountTable::getRowByFilter($deliveryFilter))
        ) {
            DeliveryDiscountTable::update((int)$findRow['ID'], [
                'DISCOUNTED_PRICE' => (float) $deliveryPrice
            ]);
        } elseif (isset($deliveryPrice)) {
            DeliveryDiscountTable::add(array_merge([
                'DISCOUNTED_PRICE' => (float) $deliveryPrice
            ], $deliveryFilter));
        } else {
            DeliveryDiscountTable::deleteByFilter($deliveryFilter);
        }

        unset($mindboxBasket, $line, $lineId, $discountedPrice, $quantity, $deliveryFilter);
    }

    public function calculateOrderAdmin(Order $order): MindboxResponse
    {
        $settings = SettingsFactory::createBySiteId($order->getSiteId());
        $mindboxOrder = new OrderMindbox($order, $settings);
        $customer = new Customer((int) $order->getField('USER_ID'));

        $orderData = array_filter([
            'ids' => $mindboxOrder->getIds(),
            'deliveryCost' => $mindboxOrder->getDeliveryCost(),
            'lines' => $mindboxOrder->getLines()->getData(),
            'customFields' => $mindboxOrder->getCustomFields(),
            'payments' => $mindboxOrder->getPayments(),
            'bonusPoints' => $mindboxOrder->getBonusPoints(),
            'coupons' => array_merge($mindboxOrder->getCoupons(), $mindboxOrder->getPromocodes()),
            'email' => $mindboxOrder->getEmail(),
            'mobilePhone' => $mindboxOrder->getMobilePhone(),
        ]);

        $customerData = array_filter([
            'ids' => $customer->getIds(),
            'email' => $customer->getEmail(),
            'mobilePhone' => $customer->getMobilePhone(),
        ]);

        $DTO = new \Mindbox\DTO\V3\Requests\PreorderRequestDTO();
        $DTO->setOrder($orderData);
        $DTO->setCustomer($customerData);

        /** @var CalculateCartAdmin $calculateCartAdmin */
        $calculateCartAdmin = $this->serviceLocator->get('mindboxLoyalty.calculateCartAdmin');

        return $calculateCartAdmin->execute($DTO);
    }

    public function calculateAuthorizedOrder(Order $order): MindboxResponse
    {
        $settings = SettingsFactory::createBySiteId($order->getSiteId());
        $mindboxOrder = new OrderMindbox($order, $settings);
        $customer = new Customer((int) $order->getField('USER_ID'));

        /** @var CalculateAuthorizedCart $calculateAuthorizedCart */
        $calculateAuthorizedCart = $this->serviceLocator->get('mindboxLoyalty.calculateAuthorizedCart');

        $orderData = array_filter([
            'ids' => $mindboxOrder->getIds(),
            'deliveryCost' => $mindboxOrder->getDeliveryCost(),
            'lines' => $mindboxOrder->getLines()->getData(),
            'customFields' => $mindboxOrder->getCustomFields(),
            'payments' => $mindboxOrder->getPayments(),
            'bonusPoints' => $mindboxOrder->getBonusPoints(),
            'coupons' => array_merge($mindboxOrder->getCoupons(), $mindboxOrder->getPromocodes()),
            'email' => $mindboxOrder->getEmail(),
            'mobilePhone' => $mindboxOrder->getMobilePhone(),
        ]);

        $customerData = array_filter([
            'ids' => $customer->getIds(),
            'email' => $customer->getEmail(),
            'mobilePhone' => $customer->getMobilePhone(),
        ]);

        $DTO = new \Mindbox\DTO\V3\Requests\PreorderRequestDTO();
        $DTO->setOrder($orderData);
        $DTO->setCustomer($customerData);

        return $calculateAuthorizedCart->execute($DTO);
    }

    public function calculateUnauthorizedOrder(Order $order): MindboxResponse
    {
        $settings = SettingsFactory::createBySiteId($order->getSiteId());

        $mindboxOrder = new OrderMindbox($order, $settings);

        $orderData = array_filter([
            'ids' => $mindboxOrder->getIds(),
            'deliveryCost' => $mindboxOrder->getDeliveryCost(),
            'lines' => $mindboxOrder->getLines()->getData(),
            'customFields' => $mindboxOrder->getCustomFields(),
            'payments' => $mindboxOrder->getPayments(),
            'coupons' => array_merge($mindboxOrder->getCoupons(), $mindboxOrder->getPromocodes()),
            'email' => $mindboxOrder->getEmail(),
            'mobilePhone' => $mindboxOrder->getMobilePhone(),
        ]);

        $DTO = new \Mindbox\DTO\V3\Requests\PreorderRequestDTO();
        $DTO->setOrder($orderData);

        /** @var CalculateUnauthorizedCart $calculateUnauthorizedCart */
        $calculateUnauthorizedCart = $this->serviceLocator->get('mindboxLoyalty.calculateUnauthorizedCart');

        return $calculateUnauthorizedCart->execute($DTO);
    }

    public function confirmDeliveryDiscount(Order $order): void
    {
        $deliveryId = 0;
        /** @var \Bitrix\Sale\Shipment $shipment */
        foreach ($order->getShipmentCollection() as $shipment) {
            if ($shipment->isSystem()) {
                continue;
            }

            $deliveryId = $shipment->getDeliveryId();
            break;
        }

        $fUserId = \Bitrix\Sale\Fuser::getIdByUserId((int) $order->getField('USER_ID'));

        $deliveryFilter = [
            'FUSER_ID' => $fUserId,
            'DELIVERY_ID' => $deliveryId,
            'ORDER_ID' => null
        ];

        if ($findRow = DeliveryDiscountTable::getRowByFilter($deliveryFilter)) {
            DeliveryDiscountTable::update((int) $findRow['ID'], [
                'ORDER_ID' => $order->getId()
            ]);
        }

        DeliveryDiscountTable::deleteByFilter($deliveryFilter);
    }

    public function resetDiscount(Order $order)
    {
        $basket = $order->getBasket();
        /** @var BasketItem $basketItem */
        foreach ($basket as $basketItem) {
            /** @var \Bitrix\Sale\BasketPropertiesCollectionBase $collection - Коллекция свойств */
            $collection = $basketItem->getPropertyCollection();
            $propertyValues = $collection->getPropertyValues();

            if (isset($propertyValues[PropertyCodeEnum::BASKET_PROPERTY_CODE])) {
                $propertyItem = $collection->getPropertyItemByValue($propertyValues[PropertyCodeEnum::BASKET_PROPERTY_CODE]);
                $propertyItem->delete();
            }
        }

        $propertyBonus = $order->getPropertyCollection()->getItemByOrderPropertyCode(PropertyCodeEnum::PROPERTIES_MINDBOX_BONUS);
        if ($propertyBonus instanceof \Bitrix\Sale\PropertyValue) {
            $propertyBonus->setValue("");
        }

        $propertyCoupon = $order->getPropertyCollection()->getItemByOrderPropertyCode(PropertyCodeEnum::PROPERTIES_MINDBOX_PROMOCODES);
        if ($propertyCoupon instanceof \Bitrix\Sale\PropertyValue) {
            $propertyCoupon->setValue([]);
        }

        if ((int) $order->getField('USER_ID') > 0) {
            $fUserId = \Bitrix\Sale\Fuser::getIdByUserId((int)$order->getField('USER_ID'));
        } else {
            $fUserId = \Bitrix\Sale\Fuser::getId();
        }

        $deliveryFilter = [
            'FUSER_ID' => $fUserId,
            'DELIVERY_ID' => $order->getField('DELIVERY_ID'),
            'ORDER_ID' => null
        ];

        DeliveryDiscountTable::deleteByFilter($deliveryFilter);
    }
}
