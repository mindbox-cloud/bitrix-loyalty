<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Support;

class FavoriteTypesEnum
{
    public const FAVORITE_TYPE_LIST = [
        'basket' => 'Отложенная корзина(по умолчанию)',
        'comma' => '1,2,3,4,5 в пользовательском поле',
        'pipe' => '1|2|3|4|5 в пользовательском поле',
        'semicolon' => '1;2;3;4;5 в пользовательском поле',
        'serialize_array' => 'Сериализованный массим в пользовательском поле',
    ];
}