<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Operations;

use Mindbox\DTO\DTO;
use Mindbox\DTO\V3\Requests\CustomerRequestDTO;
use Mindbox\Exceptions\MindboxClientException;
use Mindbox\Loyalty\Exceptions\ErrorCallOperationException;

class ConfirmMobilePhone extends AbstractOperation
{
    /**
     * @throws ErrorCallOperationException
     */
    public function execute(CustomerRequestDTO $dto): bool
    {
        $operation = $this->getOperation();

        try {
            $client = $this->api();

            $request = $client->getClientV3()->prepareRequest(
                    method: 'POST',
                    operationName: $operation,
                    body: new DTO(['customer' => $dto]),
                    isSync: true,
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
        return 'ConfirmMobilePhone';
    }
}