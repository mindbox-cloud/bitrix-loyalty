<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Operations;

use Mindbox\DTO\DTO;
use Mindbox\DTO\V3\Requests\CustomerRequestDTO;
use Mindbox\Exceptions\MindboxClientException;
use Mindbox\Exceptions\MindboxUnavailableException;
use Mindbox\Loyalty\Exceptions\ErrorCallOperationException;

class CheckAuthenticationCode extends AbstractOperation
{
    /**
     * @throws ErrorCallOperationException
     */
    public function execute(string $phone, string $code): bool
    {
        $operation = $this->getOperation();

        try {
            $client = $this->api();

            $request = $client->customer()->checkAuthorizationCode(
                customer: new CustomerRequestDTO(['mobilePhone' => $phone]),
                authentificationCode: $code,
                operationName: $operation,
                addDeviceUUID: false
            );

            $response = $request->sendRequest();

            if ($response->getResult()->getStatus() === 'Success') {
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
        return 'CheckAuthenticationCode';
    }
}