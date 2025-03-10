<?php

namespace Mindbox\Loyalty\Operations;

use Mindbox\DTO\ResultDTO;
use Mindbox\DTO\V3\Requests\CustomerRequestDTO;
use Mindbox\Exceptions\MindboxClientException;
use Mindbox\Helpers\CustomerHelper;
use Mindbox\Loyalty\Exceptions\ErrorCallOperationException;

class GetCustomer extends AbstractOperation
{

    /**
     * @throws ErrorCallOperationException
     */
    public function execute(CustomerRequestDTO $dto): ResultDTO
    {
        try {
            $client = $this->api();

            $response = (new CustomerHelper($client))
                ->checkCustomer(
                    customer: $dto,
                    operationName: $this->getOperation(),
                    addDeviceUUID: false
                )->sendRequest();

            return $response->getResult();
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
        return 'CheckCustomer';
    }
}