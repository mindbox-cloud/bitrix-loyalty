<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Discount;

use Mindbox\Loyalty\PropertyCodeEnum;

class BasketPropertyRuleDiscountAction extends \Bitrix\Sale\Discount\Actions
{
    /**
     * Basket action.
     *
     * @param array &$order order data.
     * @param array $action discount params
     * @param callable $filter Filter for basket items.
     * @return void
     */
    public static function applyToBasket(array &$order, array $action = [], $filter = null)
    {
        foreach ($order['BASKET_ITEMS'] as $basketCode => $basketRow) {
            if (!isset($basketRow['PROPERTIES'])) continue;
            if (!isset($basketRow['PROPERTIES'][PropertyCodeEnum::BASKET_PROPERTY_CODE])) continue;

            $value = (float) $basketRow['PROPERTIES'][PropertyCodeEnum::BASKET_PROPERTY_CODE]['VALUE'];

            list($discountValue, $price) = self::calculateDiscountPrice(
                $value,
                self::VALUE_TYPE_CLOSEOUT,
                $basketRow,
                0,
                false
            );

            if ($price >= 0) {
                self::fillDiscountPrice($basketRow, $price, -$discountValue);

                $order['BASKET_ITEMS'][$basketCode] = $basketRow;
            }
        }
    }
}