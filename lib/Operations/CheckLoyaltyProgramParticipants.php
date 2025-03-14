<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Operations;

use Mindbox\DTO\V3\Requests\CustomerRequestDTO;
use Mindbox\Exceptions\MindboxClientException;
use Mindbox\Exceptions\MindboxUnavailableException;
use Mindbox\Loyalty\Exceptions\ErrorCallOperationException;
use Mindbox\Loyalty\Models\Customer;

class CheckLoyaltyProgramParticipants extends AbstractOperation
{
    /**
     * @throws ErrorCallOperationException
     */
    public function execute(CustomerRequestDTO $dto)
    {
        $operation = $this->getOperation();

        try {
            $client = $this->api();

            $request = $client->prepareRequest(
                method: 'POST',
                operationName: $operation,
                body: $dto,
                isSync: true,
                addDeviceUUID: false
            );

            $response = $request->sendRequest();

            if ($response->getResult()->getStatus() === 'Success') {
                return true;
            }

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

    protected function operation(): string
    {
        return 'CheckLoyaltyProgramParticipants';
    }
}