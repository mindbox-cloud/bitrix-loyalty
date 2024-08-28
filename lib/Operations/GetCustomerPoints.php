<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Operations;

use Mindbox\DTO\DTO;
use Mindbox\DTO\V3\Requests\CustomerRequestDTO;
use Mindbox\Exceptions\MindboxClientException;
use Mindbox\Loyalty\Exceptions\ErrorCallOperationException;
use Mindbox\MindboxResponse;
use Mindbox\Responses\MindboxBalanceResponse;

class GetCustomerPoints extends AbstractOperation
{
    /**
     * @throws ErrorCallOperationException
     */
    public function execute(CustomerRequestDTO $dto): MindboxResponse
    {
        try {
            $operation = $this->getOperation();
            $client = $this->api();

            $client->setResponseType(MindboxBalanceResponse::class);

            $client->prepareRequest(
                method: 'POST',
                operationName: $operation,
                body: new DTO(['customer' => $dto]),
                isSync: true,
                addDeviceUUID: false
            );

            $response = $client->sendRequest();

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
        return 'GetCustomerPoints';
    }
}
