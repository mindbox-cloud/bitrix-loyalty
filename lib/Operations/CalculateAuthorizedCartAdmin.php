<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Operations;

use Mindbox\DTO\V3\Requests\PreorderRequestDTO;
use Mindbox\DTO\V3\Responses\OrderResponseDTO;
use Mindbox\Exceptions\MindboxClientException;
use Mindbox\Loyalty\Exceptions\ErrorCallOperationException;

class CalculateAuthorizedCartAdmin extends CalculateAuthorizedCart
{
    protected function operation(): string
    {
        return 'CalculateAuthorizedCartAdmin';
    }
}