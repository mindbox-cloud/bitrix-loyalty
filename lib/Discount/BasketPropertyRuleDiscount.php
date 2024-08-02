<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Discount;

use Bitrix\Main\Localization\Loc;

class BasketPropertyRuleDiscount extends \CSaleActionCtrlBasketGroup
{
    const INPUT_NAME = 'basket_property_discount';
    public static function GetClassName()
    {
        return __CLASS__;
    }

    public static function GetControlID()
    {
        return 'BasketPropertyRuleDiscount';
    }

    public static function GetControlDescr()
    {
        $description = parent::GetControlDescr();
        $description['EXECUTE_MODULE'] = 'sale';

        return $description;
    }

    public static function GetAtoms()
    {
        return static::GetAtomsEx(false, false);
    }

    /**
     * @param $arParams
     * @return array
     */
    public static function GetControlShow($arParams)
    {
        $arAtoms = static::getAtomsEx(false, false);

        $arResult = [
            'controlId' => static::GetControlID(),
            'group' => false,
            'label' => 'Mindbox: Скидка из свойства элемента корзины',
            'defaultText' => '',
            'showIn' => static::GetShowIn($arParams['SHOW_IN_GROUPS']),
            'control' => [
                'Скидка',
                $arAtoms[self::INPUT_NAME]
            ]
        ];

        return $arResult;
    }

    /**
     *
     * @param $strControlID
     * @param $boolEx
     * @return \array[][]|array[]
     */
    public static function GetAtomsEx($strControlID = false, $boolEx = false)
    {
        $boolEx = true === $boolEx;

        $arAtomList = [
            self::INPUT_NAME => [
                'JS' => [
                    'id' => self::INPUT_NAME,
                    'name' => self::INPUT_NAME,
                    'type' => 'select',
                    "values" => [
                        self::INPUT_NAME => 'Mindbox: Скидка из свойства элемента корзины'
                    ],
                    'defaultText' => 'Скидка из свойства элемента корзины',
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

    public static function Generate($arOneCondition, $arParams, $arControl, $arSubs = false)
    {
        $str = 'if (\Bitrix\Main\Loader::includeModule("mindbox.loyalty")) { ';
        $str .= '\\'. __NAMESPACE__ . '\\BasketPropertyRuleDiscountAction::applyToBasket(' . $arParams['ORDER'] . ');';
        $str .= '}';

        return $str;
    }
}