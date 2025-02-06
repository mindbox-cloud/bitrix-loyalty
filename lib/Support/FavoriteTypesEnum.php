<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Support;

use Bitrix\Main\Localization\Loc;

class FavoriteTypesEnum
{
    public static function getTypes(): array
    {
        return [
            'basket' => Loc::getMessage('MINDBOX_LOYALTY_FAVORITE_TYPE_BASKET'),
            'comma' => Loc::getMessage('MINDBOX_LOYALTY_FAVORITE_TYPE_COMMA'),
            'pipe' => Loc::getMessage('MINDBOX_LOYALTY_FAVORITE_TYPE_PIPE'),
            'semicolon' => Loc::getMessage('MINDBOX_LOYALTY_FAVORITE_TYPE_SEMICOLON'),
            'serialize_array' => Loc::getMessage('MINDBOX_LOYALTY_FAVORITE_TYPE_SERIALIZE'),
            'iblock_elements' => Loc::getMessage('MINDBOX_LOYALTY_FAVORITE_TYPE_IBLOCK_ELEMENTS'),
        ];
    }

    public const FAVORITE_TYPE_BASKET = 'basket';
}