<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Operations;

use Mindbox\DTO\DTO;
use Mindbox\DTO\V3\Requests\CustomerRequestDTO;
use Mindbox\Exceptions\MindboxClientException;
use Mindbox\Loyalty\Exceptions\ErrorCallOperationException;
use Mindbox\MindboxResponse;

class GetCustomerLoyaltyLevel extends AbstractOperation
{
    /**
     * @param CustomerRequestDTO $customerRequestDTO
     * @param array<string> $segmentations
     * @return MindboxResponse
     * @throws ErrorCallOperationException
     */
    public function execute(CustomerRequestDTO $customerRequestDTO, array $segmentations): MindboxResponse
    {
        try {
            $operation = $this->getOperation();
            $client = $this->api();

            $client->prepareRequest(
                method: 'POST',
                operationName: $operation,
                body: new DTO(['customer' => $customerRequestDTO, 'segmentations' => $segmentations]),
                addDeviceUUID: false
            );

            return $client->sendRequest();
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
        return 'GetCustomerLoyaltyLevel';
    }
}
