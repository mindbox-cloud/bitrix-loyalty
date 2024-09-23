<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Discount;

use Bitrix\Main\Loader;
use Bitrix\Sale\Fuser;

class DeliveryDiscountActions extends \Bitrix\Sale\Discount\Actions
{
    /**
     * @param array $order
     * @param array $action
     */
    public static function applyToDelivery(array &$arOrder, array $action)
    {
        global $USER;

        if (!Loader::includeModule('mindbox.loyalty')) {
            return;
        }

        if ((int) $arOrder['DELIVERY_ID'] <= 0) {
            return;
        }

        $filter = [];
        $filter['DELIVERY_ID'] = (int) $arOrder['DELIVERY_ID'];

        if ((int) $arOrder['ID'] > 0) {
            $filter['ORDER_ID'] = (int) $arOrder['ID'];
        } else {
            if ($USER->IsAuthorized() && (int) $arOrder['USER_ID'] > 0) {
                $fuserId = Fuser::getIdByUserId((int) $arOrder['USER_ID']);
            } elseif ($USER->IsAuthorized() && (int) $USER->GetID() > 0) {
                $fuserId = Fuser::getIdByUserId((int) $USER->GetID());
            } else {
                $fuserId = Fuser::getId();
            }

            if (empty($fuserId)) {
                return;
            }

            $filter['ORDER_ID'] = null;
            $filter['FUSER_ID'] = $fuserId;
        }

        $discount = \Mindbox\Loyalty\ORM\DeliveryDiscountTable::getList([
            'filter' => $filter,
            'select' => ['DISCOUNTED_PRICE'],
            'limit' => 1
        ])->fetch();

        if (!$discount) {
            return;
        }

        $value = (float) $discount['DISCOUNTED_PRICE'];

        $action['VALUE'] = -1 * ($arOrder['PRICE_DELIVERY'] - $value);
        $action['UNIT'] = self::VALUE_TYPE_FIX;
        $action['CURRENCY'] = static::getCurrency();

        parent::applyToDelivery($arOrder, $action);
    }
}