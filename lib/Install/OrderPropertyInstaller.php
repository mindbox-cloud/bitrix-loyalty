<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Install;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Mindbox\Loyalty\PropertyCodeEnum;

class OrderPropertyInstaller implements InstallerInterface
{
    private string $siteId;
    public function __construct(string $siteId)
    {
        Loader::IncludeModule('sale');

        $this->siteId = $siteId;
    }

    public function up()
    {
        $personTypeList = $this->getSitePersonType();
        $propertiesGroupList = $this->getPropertiesGroupList();

        $personTypesIds = array_keys($personTypeList);
        $mindboxPropertiesCodes = $this->getMindboxProperiesCodes();
        $propertiesList  = $this->getPropertiesList();

        foreach ($personTypesIds as $personTypesId) {
            foreach ($mindboxPropertiesCodes as $propCode) {
                $needInstall = true;

                foreach ($propertiesList as $propertyItem) {
                    if ($propertyItem['PERSON_TYPE_ID'] == $personTypesId && $propertyItem['CODE'] === $propCode) {
                        $needInstall = false;
                    }
                }

                if ($needInstall) {
                    $addFields = [
                        'CODE' => $propCode,
                        'PERSON_TYPE_ID' => $personTypesId,
                    ];

                    $selectPropGroup = $propertiesGroupList[$personTypesId]['ID'];

                    if (!empty($selectPropGroup)) {
                        $addFields['PROPS_GROUP_ID'] = $selectPropGroup;
                    }

                    $this->addProperty($addFields);
                }
            }
        }
    }

    public function down()
    {
        $list = $this->getPropertiesList();

        foreach ($list as $item) {
            \CSaleOrderProps::Delete($item['ID']);
        }
    }

    private function getSitePersonType(): array
    {
        $return = [];

        $getPersonType = \CSalePersonType::GetList([], ['ACTIVE' => 'Y', 'LID' => $this->siteId]);

        while ($item = $getPersonType->Fetch()) {
            $return[$item['ID']] = $item;
        }

        return $return;
    }

    private function getPropertiesGroupList(): array
    {
        $return = [];
        $getGroups = \CSaleOrderPropsGroup::GetList([], [
            '=NAME' => PropertyCodeEnum::PROPERTIES_GROUP_NAME
        ]);

        while ($item = $getGroups->Fetch()) {
            $return[$item['PERSON_TYPE_ID']] = $item;
        }

        return $return;
    }

    private function getPropertiesList()
    {
        $return = [];

        $propsCodes = $this->getMindboxProperiesCodes();
        $getProperty = \CSaleOrderProps::GetList([], [
            'CODE' => $propsCodes,
        ]);

        while ($propData = $getProperty->Fetch()) {
            $return[$propData['ID']] = $propData;
        }

        return $return;
    }

    /**
     * Массив допустимых свойств при установке и удалении
     *
     * @return string[]
     */
    private function getMindboxProperiesCodes()
    {
        return [PropertyCodeEnum::PROPERTIES_MINDBOX_PROMO_CODE, PropertyCodeEnum::PROPERTIES_MINDBOX_BONUS, PropertyCodeEnum::PROPERTIES_MINDBOX_ORDER_ID];
    }

    private function addProperty($fields)
    {
        $addPropertyFields = $this->getInstallProperiesConfig($fields['CODE']);

        if ($addPropertyFields) {
            $addPropertyFields = array_merge($addPropertyFields, $fields);
        }

        \CSaleOrderProps::Add($addPropertyFields);
    }

    private function getInstallProperiesConfig($propertyCode)
    {
        $config = [
            PropertyCodeEnum::PROPERTIES_MINDBOX_PROMO_CODE => [
                'NAME' => Loc::getMessage('MINDBOX_LOYALTY_PROPERTY_PROMO_CODE_NAME'),
                'TYPE' => 'TEXT',
                'CODE' => PropertyCodeEnum::PROPERTIES_MINDBOX_PROMO_CODE,
                'REQUIED' => 'N',
                'UTIL' => 'Y'
            ],
            PropertyCodeEnum::PROPERTIES_MINDBOX_BONUS => [
                'NAME' => Loc::getMessage('MINDBOX_LOYALTY_PROPERTY_MINDBOX_BONUS_NAME'),
                'TYPE' => 'TEXT',
                'CODE' => PropertyCodeEnum::PROPERTIES_MINDBOX_BONUS,
                'REQUIED' => 'N',
                'UTIL' => 'Y'
            ],
            PropertyCodeEnum::PROPERTIES_MINDBOX_ORDER_ID => [
                'NAME' => Loc::getMessage('MINDBOX_LOYALTY_MINDBOX_ORDER_ID_NAME'),
                'TYPE' => 'TEXT',
                'CODE' => PropertyCodeEnum::PROPERTIES_MINDBOX_ORDER_ID,
                'REQUIED' => 'N',
                'UTIL' => 'Y'
            ],
        ];

        return $config[$propertyCode];
    }
}