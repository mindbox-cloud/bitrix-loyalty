<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Services;

use Bitrix\Main\Context;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\UserTable;
use Bitrix\Sale\BasketItem;
use Bitrix\Sale\Order;
use Mindbox\DTO\V3\Responses\OrderResponseDTO;
use Mindbox\Loyalty\ORM\BasketDiscountTable;
use Mindbox\Loyalty\Helper;
use Mindbox\Loyalty\Models\Customer;
use Mindbox\Loyalty\Models\OrderMindbox;
use Mindbox\Loyalty\Operations\CalculateAuthorizedCart;
use Mindbox\Loyalty\Support\SessionStorage;
use Mindbox\Loyalty\Support\SettingsFactory;

class CalculateService
{
    /** @var int Расхождение в секундах */
    private static $userRegisterDelta = 30;
    protected \Bitrix\Main\DI\ServiceLocator $serviceLocator;

    public function __construct()
    {
        $this->serviceLocator = \Bitrix\Main\DI\ServiceLocator::getInstance();
    }

    public function calculateOrder(Order $order)
    {
        if (Context::getCurrent()->getRequest()->isAdminSection()) {
            $orderResponseDTO = $this->calculateOrderAdmin($order);
        } elseif (Helper::isUserUnAuthorized((int) $order->getField('USER_ID'))) {
            $orderResponseDTO = $this->calculateUnauthorizedOrder($order);
        } else {
            $orderResponseDTO = $this->calculateAuthorizedOrder($order);
        }

        $orderData = $orderResponseDTO->getFieldsAsArray();

        // расчетная стоимость заказа из МБ
        $mbTotalPrice = $orderData['totalPrice'] ?? $order->getPrice();
        SessionStorage::getInstance()->setTotalPrice(floatval($mbTotalPrice));

        // проверяем списанные бонусы, чтобы не списывали больше доступного
        if (isset($orderData['totalBonusPointsInfo'])) {
            $totalBonusPointInfo = $orderData['totalBonusPointsInfo'];
            if (!empty($totalBonusPointInfo) && $totalBonusPointInfo['availableAmountForCurrentOrder'] < SessionStorage::getInstance()->getPayBonuses()) {
                // Списываю за заказ, больше чем доступно
                SessionStorage::getInstance()->setPayBonuses(intval($totalBonusPointInfo['availableAmountForCurrentOrder']));
            }

            // Доступно для заказа
            SessionStorage::getInstance()->setOrderAvailableBonuses(intval($totalBonusPointInfo['availableAmountForCurrentOrder']));
            // Всего бонусов
            SessionStorage::getInstance()->setBonusesBalanceAvailable(intval($totalBonusPointInfo['balance']['available']));
            unset($totalBonusPointInfo);
        }

        // проверяем начисление бонусов за заказ
        if (isset($orderData['bonusPointsChanges']) && is_array($orderData['bonusPointsChanges'])) {
            $bonusPointsChanges = current($orderData['bonusPointsChanges']);
            // Будет начислено за заказ
            $earnedAmount = $bonusPointsChanges['earnedAmount'] ?? 0;
            SessionStorage::getInstance()->setOrderEarnedBonuses(intval($earnedAmount));

            unset($bonusPointsChanges, $earnedAmount);
        }

        // проверяем, применение промокода
        SessionStorage::getInstance()->setPromocodeError('');
        if (isset($orderData['couponsInfo']) && is_array($orderData['couponsInfo'])) {
            $couponsInfo = current($orderData['couponsInfo']);
            if ($couponsInfo['coupon']['status'] === 'NotFound') {
                $setCouponError = 'Промокод не найден';
            } elseif ($couponsInfo['coupon']['status'] === 'CanNotBeUsedForCurrentOrder') {
                $setCouponError = 'Нельзя применить данный промокод';
            } elseif ($couponsInfo['coupon']['status'] === 'Used') {
                $setCouponError = 'Промокод был использован ранее';
            }

            if ($setCouponError !== null) {
                SessionStorage::getInstance()->setPromocodeError($setCouponError);
            }

            unset($couponsInfo, $setCouponError);
        }

        $mindboxBasket = [];
        foreach ($orderData['lines'] as $line) {
            $lineId = (int) $line['lineId'];
            $discountedPrice = (float) $line['discountedPriceOfLine'];
            $quantity = (float) $line['quantity'];

            // todo необходимо реализовать скидку в МБ, которое бы нарушало данное условие
            if (!isset($mindboxBasket[$lineId]) && $lineId > 0) {
                $mindboxPrice = $discountedPrice / $quantity;

                $mindboxBasket[$lineId] = [
                    'price' => $mindboxPrice,
                    'quantity' => $quantity,
                ];

                BasketDiscountTable::set($lineId, $mindboxPrice);
            }
        }

        unset($mindboxBasket, $line, $lineId, $discountedPrice, $quantity, $mindboxPrice);
    }

    public function calculateOrderAdmin(Order $order): OrderResponseDTO
    {
        $settings = SettingsFactory::createBySiteId($order->getSiteId());

        $mindboxOrder = new OrderMindbox($order, $settings);
        $customer = new Customer($order->getUserId());

        /** @var CalculateAuthorizedCart $calculateAuthorizedCart */
        $calculateAuthorizedCart = $this->serviceLocator->get('mindboxLoyalty.calculateAuthorizedCartAdmin');

        $customerDTO = new \Mindbox\DTO\V3\Requests\CustomerRequestDTO();
        $customerDTO->setIds($customer->getIds());

        $orderData = $mindboxOrder->getData();

        $DTO = new \Mindbox\DTO\V3\Requests\PreorderRequestDTO();
        $DTO->setOrder($orderData);
        $DTO->setCustomer($customerDTO);

        return $calculateAuthorizedCart->execute($DTO);
    }

    public function calculateAuthorizedOrder(Order $order): OrderResponseDTO
    {
        $settings = SettingsFactory::createBySiteId($order->getSiteId());
        $mindboxOrder = new OrderMindbox($order, $settings);
        $customer = new Customer((int) $order->getField('USER_ID'));

        /** @var CalculateAuthorizedCart $calculateAuthorizedCart */
        $calculateAuthorizedCart = $this->serviceLocator->get('mindboxLoyalty.calculateAuthorizedCart');

        $mindboxOrder->setBonuses(SessionStorage::getInstance()->getPayBonuses());
        $mindboxOrder->setCoupons(SessionStorage::getInstance()->getPromocodeValue());

        $customerDTO = new \Mindbox\DTO\V3\Requests\CustomerRequestDTO();
        $customerDTO->setIds($customer->getIds());

        $orderData = $mindboxOrder->getData();

        $DTO = new \Mindbox\DTO\V3\Requests\PreorderRequestDTO();
        $DTO->setOrder($orderData);
        $DTO->setCustomer($customerDTO);

        return $calculateAuthorizedCart->execute($DTO);
    }

    public function calculateUnauthorizedOrder(Order $order): OrderResponseDTO
    {
        $settings = SettingsFactory::createBySiteId($order->getSiteId());

        $mindboxOrder = new OrderMindbox($order, $settings);

        /** @var CalculateAuthorizedCart $calculateAuthorizedCart */
        $calculateAuthorizedCart = $this->serviceLocator->get('mindboxLoyalty.calculateUnauthorizedCart');

        $mindboxOrder->setCoupons(SessionStorage::getInstance()->getPromocodeValue());


        $orderData = $mindboxOrder->getData();

        $DTO = new \Mindbox\DTO\V3\Requests\PreorderRequestDTO();
        $DTO->setOrder($orderData);

        return $calculateAuthorizedCart->execute($DTO);
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
    }
}