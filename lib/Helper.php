<?php

declare(strict_types=1);

namespace Mindbox\Loyalty;

use Bitrix\Main\Localization\Loc;
use Mindbox\DTO\DTO;
use Bitrix\Main\Config\Option;

class Helper
{
    public static function formatPhone($phone)
    {
        return str_replace([' ', '(', ')', '-', '+'], "", $phone);
    }
}