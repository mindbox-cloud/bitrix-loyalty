<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Services;

use Bitrix\Main\Context;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\UserTable;
use Bitrix\Sale\BasketItem;
use Bitrix\Sale\Order;
use Mindbox\DTO\V3\Responses\OrderResponseDTO;
use Mindbox\Loyalty\Exceptions\ResponseErrorExceprion;
use Mindbox\Loyalty\Operations\CalculateCartAdmin;
use Mindbox\Loyalty\Operations\CalculateUnauthorizedCart;
use Mindbox\Loyalty\ORM\BasketDiscountTable;
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
            $type = OrderOperationTypeTable::getOrderType((string) $order->getField('ACCOUNT_NUMBER'));

            if ($type === OrderOperationTypeTable::OPERATION_TYPE_AUTH) {
                $response = $this->calculateAuthorizedOrder($order);
            } else {
                $response = $this->calculateUnauthorizedOrder($order);
            }
        }

        if ($response->isError()) {
            throw new ResponseErrorExceprion();
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
        $this->sessionStorage->setPromocodeError('');
        if (isset($orderData['couponsInfo']) && is_array($orderData['couponsInfo'])) {
            $couponsInfo = current($orderData['couponsInfo']);
            if ($couponsInfo['coupon']['status'] === 'NotFound') {
                $setCouponError = Loc::getMessage('MINDBOX_LOYALTY_COUPON_NOT_FOUND');
            } elseif ($couponsInfo['coupon']['status'] === 'CanNotBeUsedForCurrentOrder') {
                $setCouponError = Loc::getMessage('MINDBOX_LOYALTY_COUPON_CAN_NOT_BE_USER');
            } elseif ($couponsInfo['coupon']['status'] === 'Used') {
                $setCouponError = Loc::getMessage('MINDBOX_LOYALTY_COUPON_USED');
            }

            if ($setCouponError !== null) {
                $this->sessionStorage->setPromocodeError($setCouponError);
            }

            unset($couponsInfo, $setCouponError);
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

        /** @var CalculateCartAdmin $calculateCartAdmin */
        $calculateCartAdmin = $this->serviceLocator->get('mindboxLoyalty.calculateCartAdmin');

        $customerDTO = new \Mindbox\DTO\V3\Requests\CustomerRequestDTO();
        $customerDTO->setIds($customer->getIds());

        $orderData = $mindboxOrder->getData();
        unset($orderData['totalPrice']);

        $DTO = new \Mindbox\DTO\V3\Requests\PreorderRequestDTO();
        $DTO->setOrder($orderData);
        $DTO->setCustomer($customerDTO);

        return $calculateCartAdmin->execute($DTO);
    }

    public function calculateAuthorizedOrder(Order $order): MindboxResponse
    {
        $settings = SettingsFactory::createBySiteId($order->getSiteId());
        $mindboxOrder = new OrderMindbox($order, $settings);
        $customer = new Customer((int) $order->getField('USER_ID'));

        /** @var CalculateAuthorizedCart $calculateAuthorizedCart */
        $calculateAuthorizedCart = $this->serviceLocator->get('mindboxLoyalty.calculateAuthorizedCart');

        if ($this->sessionStorage->isPromocodeUsed()) {
            $mindboxOrder->setCoupons($this->sessionStorage->getPromocodeValue());
        }

        if ($this->sessionStorage->isBonusesUsed()) {
            $mindboxOrder->setBonuses($this->sessionStorage->getPayBonuses());
        }

        $customerDTO = new \Mindbox\DTO\V3\Requests\CustomerRequestDTO();
        $customerDTO->setIds($customer->getIds());

        $orderData = $mindboxOrder->getData();
        unset($orderData['totalPrice']);

        $DTO = new \Mindbox\DTO\V3\Requests\PreorderRequestDTO();
        $DTO->setOrder($orderData);
        $DTO->setCustomer($customerDTO);

        return $calculateAuthorizedCart->execute($DTO);
    }

    public function calculateUnauthorizedOrder(Order $order): MindboxResponse
    {
        $settings = SettingsFactory::createBySiteId($order->getSiteId());

        $mindboxOrder = new OrderMindbox($order, $settings);

        /** @var CalculateUnauthorizedCart $calculateUnauthorizedCart */
        $calculateUnauthorizedCart = $this->serviceLocator->get('mindboxLoyalty.calculateUnauthorizedCart');

        $mindboxOrder->setCoupons($this->sessionStorage->getPromocodeValue());

        $orderData = $mindboxOrder->getData();
        unset($orderData['totalPrice']);

        $DTO = new \Mindbox\DTO\V3\Requests\PreorderRequestDTO();
        $DTO->setOrder($orderData);

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
            $lineIds[] = $basketItem->getId();
        }

        $iterator = BasketDiscountTable::getList([
            'filter' => ['BASKET_ITEM_ID' => $lineIds],
            'select' => ['ID']
        ]);

        while ($line = $iterator->fetch()) {
            BasketDiscountTable::delete($line['ID']);
        }

        $propertyBonus = $order->getPropertyCollection()->getItemByOrderPropertyCode(PropertyCodeEnum::PROPERTIES_MINDBOX_BONUS);
        if ($propertyBonus instanceof \Bitrix\Sale\PropertyValue) {
            $propertyBonus->setValue("");
        }

        $propertyCoupon = $order->getPropertyCollection()->getItemByOrderPropertyCode(PropertyCodeEnum::PROPERTIES_MINDBOX_PROMO_CODE);
        if ($propertyCoupon instanceof \Bitrix\Sale\PropertyValue) {
            $propertyCoupon->setValue("");
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
