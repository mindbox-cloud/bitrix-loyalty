<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Operations;

use Mindbox\DTO\V3\Requests\CustomerRequestDTO;
use Mindbox\DTO\V3\Responses\CustomerResponseDTO;
use Mindbox\Exceptions\MindboxClientException;
use Mindbox\Helpers\CustomerHelper;
use Mindbox\Loyalty\Exceptions\ErrorCallOperationException;

class GetCustomerInfo extends AbstractOperation
{
    /**
     * @param CustomerRequestDTO $dto
     * @return ?CustomerResponseDTO
     * @throws ErrorCallOperationException
     */
    public function execute(CustomerRequestDTO $dto): ?CustomerResponseDTO
    {
        try {
            $client = $this->api();

            $response = (new CustomerHelper($client))
                ->checkCustomer(
                    customer: $dto,
                    operationName: $this->getOperation(),
                    addDeviceUUID: false
                )->sendRequest();

            if (
                $response->getResult()->getStatus() === 'Success')
            {
                return $response->getResult()->getCustomer();
            }
        } catch (MindboxClientException $e) {
            throw new ErrorCallOperationException(
                message: sprintf('The operation %s failed', $this->getOperation()),
                previous: $e,
                operationName: $this->getOperation()
            );
        }

        return null;
    }

    protected function operation(): string
    {
        return 'GetCustomerInfo';
    }
}