<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Operations;

use Mindbox\DTO\V3\Requests\CustomerRequestDTO;
use Mindbox\DTO\V3\Requests\SubscriptionRequestDTO;
use Mindbox\Exceptions\MindboxClientException;
use Mindbox\Loyalty\Exceptions\ErrorCallOperationException;
use Mindbox\Loyalty\Exceptions\ValidationErrorCallOperationException;

class SubscribeCustomer extends AbstractOperation
{
    /**
     * @param CustomerRequestDTO $dto
     * @return bool
     * @throws ErrorCallOperationException
     * @throws ValidationErrorCallOperationException
     */
    public function execute(CustomerRequestDTO $dto): bool
    {
        $operation = $this->getOperation();

        try {
            $client = $this->api();

            $response = $client->customer()
                ->subscribeCustomer(
                    customer: $dto,
                    operationName: $operation,
                )->sendRequest();

            if ($response->getResult()->getStatus() === 'Success') {
                return true;
            } elseif ($response->getResult()->getStatus() === 'ValidationError') {
                throw new ValidationErrorCallOperationException(
                    message: sprintf('The operation %s failed', $operation),
                    operationName: $operation,
                    validationMessage: $response->getResult()->getValidationMessages()
                );
            }

        } catch (MindboxClientException $e) {
            throw new ErrorCallOperationException(
                message: sprintf('The operation %s failed', $this->getOperation()),
                previous: $e,
                operationName: $operation
            );
        }

        return false;
    }
    protected function operation(): string
    {
        return 'SubscribeCustomer';
    }
}