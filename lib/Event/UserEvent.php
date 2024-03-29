<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Event;


use Mindbox\Loyalty\Entity\User;

class UserEvent
{
    /**
     * Обработчик события OnAfterUserAdd Битрикс
     * @param array $arFields
     * @return void
     */
    public static function onAfterUserAdd(array &$arFields)
    {
        if ($arFields['ID'] > 0) {
            (new User())->add($arFields);
        }
    }
}