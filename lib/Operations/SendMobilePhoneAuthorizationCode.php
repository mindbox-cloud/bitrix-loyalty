<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Operations;

use Mindbox\DTO\V3\Requests\CustomerRequestDTO;
use Mindbox\Exceptions\MindboxClientException;
use Mindbox\Exceptions\MindboxUnavailableException;
use Mindbox\Loyalty\Exceptions\ErrorCallOperationException;
use Mindbox\Loyalty\Exceptions\ValidationErrorCallOperationException;

class SendMobilePhoneAuthorizationCode extends AbstractOperation
{
    /**
     * @throws ErrorCallOperationException|ValidationErrorCallOperationException
     */
    public function execute(string $phone): bool
    {
        $operation = $this->getOperation();

        try {
            $client = $this->api();

            $dto = new CustomerRequestDTO([
                'mobilePhone' => $phone,
            ]);

            $response = $client->customer()
                ->sendAuthorizationCode(
                    customer: $dto,
                    operationName: $operation,
                    addDeviceUUID: false
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
        return 'SendMobileAuthentificationCode';
    }
}