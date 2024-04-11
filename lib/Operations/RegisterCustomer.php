<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Operations;

use Mindbox\DTO\V3\Requests\CustomerRequestDTO;
use Mindbox\DTO\V3\Responses\CustomerResponseDTO;
use Mindbox\Exceptions\MindboxClientException;
use Mindbox\Exceptions\MindboxUnavailableException;
use Mindbox\Loyalty\Exceptions\ErrorCallOperationException;
use Mindbox\Loyalty\Exceptions\ValidationErrorCallOperationException;

class RegisterCustomer extends AbstractOperation
{
    /**
     * @throws ErrorCallOperationException
     * @throws ValidationErrorCallOperationException
     */
    public function execute(CustomerRequestDTO $dto): ?CustomerResponseDTO
    {
        $operation = $this->getOperation();

        try {
            $client = $this->api();

            $response = $client->customer()
                ->register(
                    $dto,
                    $operation
                )
                ->sendRequest();

            $result = $response->getResult();

            if ($result->getStatus() === 'ValidationError') {
                throw new ValidationErrorCallOperationException(
                    message: sprintf('The operation %s failed', $operation),
                    operationName: $operation,
                    validationMessage: $result->getValidationMessages()
                );
            } elseif ($result->getStatus() === 'Success') {
                return $result->getCustomer();
            }
        } catch (MindboxUnavailableException $e) {
            // todo тут нужно будет делать ретрай отправки на очереди
        } catch (MindboxClientException $e) {
            // todo log this or log service?
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
        return 'RegisterCustomer';
    }
}