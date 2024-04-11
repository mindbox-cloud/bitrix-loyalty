<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Operations;

use Mindbox\DTO\V3\Requests\CustomerRequestDTO;
use Mindbox\Exceptions\MindboxClientException;
use Mindbox\Loyalty\Exceptions\ErrorCallOperationException;

class CheckCustomer extends AbstractOperation
{
    /**
     * @param CustomerRequestDTO $dto
     * @return bool
     * @throws ErrorCallOperationException
     */
    public function execute(CustomerRequestDTO $dto): bool
    {
        try {
            $client = $this->api();

            $response = $client->customer()
                ->checkCustomer(
                    customer: $dto,
                    operationName: $this->getOperation(),
                    addDeviceUUID: false
                )->sendRequest();
            // todo добавить исключение для ошибок валидации
            if (
                $response->getResult()->getStatus() === 'Success' &&
                $response->getResult()->getCustomer()->getProcessingStatus() === 'Found')
            {
                return true;
            }
        } catch (MindboxClientException $e) {
            throw new ErrorCallOperationException(
                message: sprintf('The operation %s failed', $this->getOperation()),
                previous: $e,
                operationName: $this->getOperation()
            );
        }

        return false;
    }

    protected function operation(): string
    {
        return 'CheckCustomer';
    }

    public function customOperation(): mixed
    {
        //return 'BitrixCheckCustomer';
        return null;
    }
}