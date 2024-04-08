<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Operations;

use Mindbox\Exceptions\MindboxClientException;
use Mindbox\Exceptions\MindboxUnavailableException;
use Mindbox\Loyalty\Exceptions\ErrorCallOperationException;
use Mindbox\Loyalty\Exceptions\ValidationErrorCallOperationException;
use Mindbox\Loyalty\Models\Customer;

class EditCustomer extends AbstractOperation
{
    /**
     * @throws ErrorCallOperationException
     * @throws ValidationErrorCallOperationException
     */
    public function execute(Customer $customer): bool
    {
        $operation = $this->getOperation();

        try {
            $client = $this->api();

            $response = $client->customer()
                ->edit(
                    customer: $customer->getDto(),
                    operationName: $operation,
                    addDeviceUUID: false
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
                return true;
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

        return false;
    }

    public function operation(): string
    {
        return 'EditCustomer';
    }
}