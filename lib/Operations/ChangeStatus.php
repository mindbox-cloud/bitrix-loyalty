<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Operations;

use Mindbox\DTO\ResultDTO;
use Mindbox\DTO\V3\Requests\OrderRequestDTO;
use Mindbox\DTO\V3\Requests\PreorderRequestDTO;
use Mindbox\Exceptions\MindboxClientException;
use Mindbox\Loyalty\Exceptions\ErrorCallOperationException;
use Mindbox\Responses\MindboxOrderResponse;

class ChangeStatus extends AbstractOperation
{
    public function execute(OrderRequestDTO $DTO): ResultDTO
    {
        $operation = $this->getOperation();

        try {
            $client = $this->api()->getClientV3();

            $client->setResponseType(MindboxOrderResponse::class);

            $response = $client->prepareRequest(
                'POST',
                $operation,
                $DTO,
                'create'
            )->sendRequest();

            return $response->getResult();
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
        return 'ChangeStatus';
    }
}