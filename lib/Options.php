<?php

declare(strict_types=1);

namespace Mindbox\Loyalty;

use Bitrix\Catalog\GroupTable;
use Bitrix\Catalog\PriceTable;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

final class Options
{
    public static function getIblocks(): array
    {
        $iblockCatalogList = [];

        if (Loader::includeModule('catalog')) {
            $iter =  \Bitrix\Catalog\CatalogIblockTable::getList([
                'filter' => ['IBLOCK.ACTIVE' => 'Y', 'PRODUCT_IBLOCK_ID' => 0],
                'select' => ['IBLOCK_ID', 'IBLOCK_NAME' => 'IBLOCK.NAME']
            ]);

            while ($iblock = $iter->fetch()) {
                $iblockCatalogList[$iblock['IBLOCK_ID']] = $iblock['IBLOCK_NAME'] . ' [' . $iblock['IBLOCK_ID'] . ']';
            }
        }

        return $iblockCatalogList;
    }

    public static function getPrices(): array
    {
        $prices = [];

        if (Loader::includeModule('catalog')) {
            $iterPricec = GroupTable::getList([
                'select' => ['ID', 'NAME']
            ]);

            while ($price = $iterPricec->fetch()) {
                $prices[$price['ID']] = $price['NAME'] . ' [' . $price['ID'] . ']';
            }
        }

        return $prices;
    }

    public static function getOffersCatalogId(int $catalogId): int
    {
        $offerId = 0;

        if (Loader::includeModule('catalog')) {
            $iter = \Bitrix\Catalog\CatalogIblockTable::getList([
                'filter' => ['IBLOCK.ACTIVE' => 'Y', 'PRODUCT_IBLOCK_ID' => $catalogId],
                'select' => ['IBLOCK_ID']
            ]);

            if ($iblock = $iter->fetch()) {
                $offerId = (int) $iblock['IBLOCK_ID'];
            }
        }

        return $offerId;
    }

    public static function getIblockProperty(int $iblockId): array
    {
        $property = [];

        if (Loader::includeModule('iblock')) {
            $iblockProperties = \CIBlock::GetProperties($iblockId);

            while ($iblockProperty = $iblockProperties->Fetch()) {
                $property['PROPERTY_' . $iblockProperty['ID']] = $iblockProperty['NAME'];
            }
        }

        return $property;
    }

    public static function getUserFields(): array
    {
        $dbFields = \CUserTypeEntity::GetList([], ['ENTITY_ID' => 'USER']);

        $userFields = [];
        while ($field = $dbFields->Fetch()) {
            $userFields[$field['FIELD_NAME']] = $field['FIELD_NAME'];
        }

        return $userFields;
    }

    public static function getOrderFields(string $siteId): array
    {
        $orderFields = [];

        if (Loader::includeModule('sale')) {
            $registry = \Bitrix\Sale\Registry::getInstance(\Bitrix\Sale\Registry::REGISTRY_TYPE_ORDER);
            /** @var \Bitrix\Sale\PropertyBase $propertyClassName */
            $propertyClassName = $registry->getPropertyClassName();
            $personTypeClassName = $registry->getPersonTypeClassName();

            $iterPersonType = $personTypeClassName::getlist(['filter' => ['=ACTIVE' => 'Y', '=PERSON_TYPE_SITE.SITE_ID' => $siteId]]);
            $personTypes = [];
            while($personTypeItem = $iterPersonType->fetch()) {
                $personTypes[] = $personTypeItem["ID"];
            }

            $iterProperty = $propertyClassName::getList([
                'select' => [
                    'ID', 'PERSON_TYPE_ID', 'NAME', 'CODE',
                ],
                'filter' => [
                    '=PERSON_TYPE_ID' => $personTypes,
                    '=ACTIVE' => 'Y',
                ],
                'order' => ['PERSON_TYPE_ID' => 'ASC', 'SORT' => 'ASC'],
            ]);

            while ($props = $iterProperty->fetch()) {
                $orderFields[$props['ID']] = $props['NAME'] . ' [' . $props['CODE'] . ' ' . $props['ID'] . ']';
            }
        }

        return $orderFields;
    }

    public static function getOrderStatuses(): array
    {
        $statusList = [
            'CANCEL' => Loc::getMessage('CANCEL_ORDER_LABEL'),
            'DELETE' => Loc::getMessage('DELETE_ORDER_LABEL'),
            'PAY' => Loc::getMessage('PAY_ORDER_LABEL'),
        ];

        if (Loader::includeModule('sale')) {
            $statusList = array_merge($statusList, \Bitrix\Sale\OrderStatus::getAllStatusesNames());
        }

        return $statusList;
    }

    public static function getUserGroups()
    {
        $arGroup = [];

        $iterator = \Bitrix\Main\GroupTable::getList([
            'filter' => ['ACTIVE' => 'Y'],
            'select' => ['ID', 'NAME']
        ]);

        while ($group = $iterator->fetch()) {
            $arGroup[$group['ID']] = $group['NAME'] . ' [' . $group['ID'] . ']';
        }

        return $arGroup;
    }

    public static function getAddOrderMatchButton(string $buttonClass = ''): string
    {
        return '<a class="module_button module_button_add '.$buttonClass.'" href="javascript:void(0)">'.Loc::getMessage("BUTTON_ADD").'</a>';
    }

    public static function getMatchesTable(string $className): string
    {
        $escapeTable = '</td></tr><tr><td colspan="2"><table class="table ' . $className . '">';
        $tableHead = '<tr class="tr title"><th class="th">'.Loc::getMessage("BITRIX_FIELDS").'</th><th class="th">'.Loc::getMessage("MINDBOX_FIELDS").'</th><th class="th-empty"></th></tr>';

        $result = $escapeTable . $tableHead;

        $bottomPadding = '</table></td></tr><tr><td>&nbsp;</td></tr>';
        $result .= $bottomPadding;

        return $result;
    }
}
