<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Discount;

class BasketRuleAction extends \CSaleActionCtrlBasketGroup
{
    const INPUT_NAME = 'basket_discount';

    public static function getClassName()
    {
        return __CLASS__;
    }

    public static function GetControlID()
    {
        return self::INPUT_NAME;
    }

    public static function GetControlDescr()
    {
        return parent::GetControlDescr();
    }

    public static function GetAtoms()
    {
        return static::GetAtomsEx(false, false);
    }

    public static function getControlShow($arParams)
    {
        $arAtoms = static::getAtomsEx(false, false);

        $arResult = [
            'controlId' => static::GetControlID(),
            'group' => false,
            'label' => 'Скидка на корзину',
            'defaultText' => '',
            'showIn' => static::GetShowIn($arParams['SHOW_IN_GROUPS']),
            'control' => [
                'Скидка на корзину',
                $arAtoms[self::INPUT_NAME]
            ]
        ];

        return $arResult;
    }

    public static function getAtomsEx($strControlID = false, $boolEx = false)
    {
        $boolEx = true === $boolEx;

        $arAtomList = [
            self::INPUT_NAME => [
                'JS' => [
                    'id' => self::INPUT_NAME,
                    'name' => self::INPUT_NAME,
                    'type' => 'select',
                    "values" => [
                        self::INPUT_NAME => 'Скидка на корзину'
                    ],
                    'defaultText' => 'Скидка на корзину',
                    'defaultValue' => self::INPUT_NAME,

                ],
                'ATOM' => [
                    'ID' => self::INPUT_NAME,
                    'FIELD_TYPE' => 'string',
                    'FIELD_LENGTH' => 255,
                    'MULTIPLE' => 'N',
                    'VALIDATE' => ''
                ]
            ],
        ];

        if (!$boolEx) {
            foreach ($arAtomList as &$arOneAtom) {
                $arOneAtom = $arOneAtom['JS'];
            }

            if (isset($arOneAtom)) {
                unset($arOneAtom);
            }
        }

        return $arAtomList;
    }

    public static function generate($arOneCondition, $arParams, $arControl, $arSubs = false)
    {
        $str = 'if (\Bitrix\Main\Loader::includeModule("mindbox.loyalty")) { ';
        $str .= __CLASS__ . '::applyDiscount(' . $arParams['ORDER'] . ');';
        $str .= '}';

        return $str;
    }

    public static function applyDiscount(&$arOrder)
    {
        if ($arOrder['USER_ID']) {
            $arBasketId = [];
            foreach ($arOrder['BASKET_ITEMS'] as $basketItem) {
                $arBasketId[] = $basketItem['ID'];
            }
            unset($basketItem);

            if ($arBasketId === []) {
                return;
            }

            $iterator = BasketDiscountTable::query()
                ->whereIn('BASKET_ITEM_ID', $arBasketId)
                ->setSelect(['ID', 'BASKET_ITEM_ID', 'DISCOUNTED_PRICE'])
                ->exec();

            $discounts = [];
            while ($el = $iterator->fetch()) {
                $discounts[$el['BASKET_ITEM_ID']] = $el;
            }

            //Применяем скидку
            foreach ($arOrder['BASKET_ITEMS'] as &$basketItem) {
                $basketId = $basketItem['ID'];
                if (isset($discounts[$basketId])) {
                    if ($discounts[$basketId]['DISCOUNTED_PRICE'] >= 0) {
                        $discountPrice = $basketItem['PRICE'] - $discounts[$basketId]['DISCOUNTED_PRICE'];
                    }

                    $basketItem['DISCOUNT_PRICE'] = $basketItem['DISCOUNT_PRICE'] + $discountPrice;
                    $basketItem['PRICE'] = $discounts[$basketId]['DISCOUNTED_PRICE'];
                }
            }
            unset($basketItem);
        }
    }
}