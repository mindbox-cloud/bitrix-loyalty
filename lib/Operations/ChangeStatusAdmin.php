<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Operations;

use Mindbox\Loyalty\Operations\ChangeStatus;

class ChangeStatusAdmin extends ChangeStatus
{
    protected function operation(): string
    {
        return 'ManualChangeStatus';
    }
}