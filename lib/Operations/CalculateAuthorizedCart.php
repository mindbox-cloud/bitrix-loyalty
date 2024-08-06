<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Operations;

use Mindbox\DTO\V3\Requests\PreorderRequestDTO;
use Mindbox\DTO\V3\Responses\OrderResponseDTO;
use Mindbox\Exceptions\MindboxClientException;
use Mindbox\Helpers\OrderHelper;
use Mindbox\Loyalty\Exceptions\ErrorCallOperationException;
use Mindbox\Loyalty\ORM\OrderOperationTypeTable;
use Mindbox\MindboxResponse;

class CalculateAuthorizedCart extends AbstractOperation
{
    public function execute(PreorderRequestDTO $DTO): MindboxResponse
    {
        $operation = $this->getOperation();

        try {
            $client = $this->api();

            $response = (new OrderHelper($client))
                ->calculateAuthorizedCart(
                    $DTO,
                    $operation
                )
                ->sendRequest();

            return $response;
        } catch (MindboxClientException $e) {
            // todo log this or log service?
            throw new ErrorCallOperationException(
                message: sprintf('The operation %s failed', $this->getOperation()),
                previous: $e,
                operationName: $this->getOperation()
            );
        }
    }

    protected function operation(): string
    {
        return 'CalculateAuthorizedOrder';
    }
}