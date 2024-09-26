<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Discount;

use Bitrix\Main\Localization\Loc;

class DeliveryRuleAction extends \CSaleActionCtrlDelivery
{
    const INPUT_NAME = 'delivery_discount';

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
        $description = parent::GetControlDescr();
        $description['EXECUTE_MODULE'] = 'sale';

        return $description;
    }

    public static function GetAtoms()
    {
        return static::GetAtomsEx(false, false);
    }

    public static function GetControlShow($arParams)
    {
        $arAtoms = static::GetAtomsEx(false, false);

        $arResult = [
            'controlId' => static::GetControlID(),
            'group' => false,
            'label' => Loc::getMessage('MINDBOX_LOYALTY_DELIVERY_DISCOUNT_RULE_LABEL'),
            'defaultText' => '',
            'showIn' => static::GetShowIn($arParams['SHOW_IN_GROUPS']),
            'control' => [
                Loc::getMessage('MINDBOX_LOYALTY_DELIVERY_DISCOUNT_RULE_CONTROL'),
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
                        self::INPUT_NAME => Loc::getMessage('MINDBOX_LOYALTY_DELIVERY_DISCOUNT_RULE_VALUES')
                    ],
                    'defaultText' => Loc::getMessage('MINDBOX_LOYALTY_DELIVERY_DISCOUNT_RULE_DEFAULT'),
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
        $options = [
            self::INPUT_NAME => $arOneCondition[self::INPUT_NAME],
        ];

        $mxResult = self::startGenerate()
            . '\\'. __NAMESPACE__ . '\\DeliveryDiscountActions::applyToDelivery('
            . $arParams['ORDER'] . ', '
            . var_export($options, true)
            . ');'
            . self::endGenerate();

        return $mxResult;
    }

    private static function startGenerate()
    {
        return 'if (\Bitrix\Main\Loader::includeModule("mindbox.loyalty")) { ';
    }

    private static function endGenerate()
    {
        return '}';
    }
}