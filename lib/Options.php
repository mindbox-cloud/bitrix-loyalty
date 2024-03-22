<?php

declare(strict_types=1);

namespace Mindbox\Loyalty;

use Bitrix\Main\Localization\Loc;

final class Options
{
    /**
     * @return array
     */
    public static function getUserFields(): array
    {
        $dbFields = \CUserTypeEntity::GetList([], ['ENTITY_ID' => 'USER']);

        $userFields = [];
        while ($field = $dbFields->Fetch()) {
            $userFields[$field['FIELD_NAME']] = $field['FIELD_NAME'];
        }

        return $userFields;
    }

    public static function getAddOrderMatchButton(string $buttonClass = ''): string
    {
        return '<a class="module_button module_button_add '.$buttonClass.'" href="javascript:void(0)">'.Loc::getMessage("BUTTON_ADD").'</a>';
    }

    /**
     * @return string
     */
    public static function getUserMatchesTable(): string
    {
        $escapeTable = '</td></tr><tr><td colspan="2"><table class="table user-table">';
        $tableHead = '<tr class="tr title"><th class="th">'.Loc::getMessage("BITRIX_FIELDS").'</th><th class="th">'.Loc::getMessage("MINDBOX_FIELDS").'</th><th class="th-empty"></th></tr>';

        $result = $escapeTable.$tableHead;

        $bottomPadding = '</table></td></tr><tr><td>&nbsp;</td></tr>';
        $result .= $bottomPadding;

        return $result;
    }
}