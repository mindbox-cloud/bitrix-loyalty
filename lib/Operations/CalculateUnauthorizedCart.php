<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Operations;

use Bitrix\Sale\Order;
use Mindbox\DTO\V3\Requests\PreorderRequestDTO;
use Mindbox\DTO\V3\Responses\OrderResponseDTO;
use Mindbox\Exceptions\MindboxClientException;
use Mindbox\Exceptions\MindboxUnavailableException;
use Mindbox\Helpers\OrderHelper;
use Mindbox\Loyalty\Exceptions\ErrorCallOperationException;
use Mindbox\Loyalty\Exceptions\ValidationErrorCallOperationException;
use Mindbox\Loyalty\ORM\OrderOperationTypeTable;
use Mindbox\MindboxResponse;

class CalculateUnauthorizedCart extends AbstractOperation
{
    /**
     * @throws ErrorCallOperationException
     */
    public function execute(PreorderRequestDTO $DTO): MindboxResponse
    {
        $operation = $this->getOperation();

        try {
            $client = $this->api();

            $response = (new OrderHelper($client))
                ->calculateUnauthorizedCart(
                    $DTO,
                    $operation
                )
                ->sendRequest();

            return $response;
        } catch (MindboxClientException $e) {
            throw new ErrorCallOperationException(
                message: sprintf('The operation %s failed', $this->getOperation()),
                previous: $e,
                operationName: $this->getOperation()
            );
        }
    }

    protected function operation(): string
    {
        return 'CalculateUnauthorizedOrder';
    }
}