<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Install;

use Bitrix\Main\Loader;
use Mindbox\Loyalty\PropertyCodeEnum;

/**
 * Устанавливает группу свойств для указанного сайта
 */
class OrderGroupPropertyInstaller implements InstallerInterface
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

        foreach ($personTypeList as $personItem) {
            foreach ($propertiesGroupList as $propertyGroup) {
                if ($propertyGroup['PERSON_TYPE_ID'] == $personItem['ID']
                    && $propertyGroup['NAME'] !== PropertyCodeEnum::PROPERTIES_GROUP_NAME
                ) {
                    $this->addPropertyGroup(intval($personItem['ID']));
                }
            }
        }
    }

    public function down()
    {
        $propertiesGroupList = $this->getPropertiesGroupList();

        foreach ($propertiesGroupList as $groupItem) {
            \CSaleOrderPropsGroup::Delete($groupItem['ID']);
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

    /**
     * Добавление новой группы
     *
     * @param int $personType
     * @return false|int
     */
    private function addPropertyGroup(int $personType)
    {
        $return = \CSaleOrderPropsGroup::Add([
            'PERSON_TYPE_ID' => $personType,
            'NAME' => PropertyCodeEnum::PROPERTIES_GROUP_NAME
        ]);

        return $return;
    }
}