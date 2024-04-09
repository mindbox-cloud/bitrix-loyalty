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
    public function execute(CustomerRequestDTO $dto)
    {
        $operation = $this->getOperation();

        try {
            $client = $this->api();

            $request = $client->getClientV3()->prepareRequest(
                method: 'POST',
                operationName: $operation,
                body: $dto
            );

            $response = $request->sendRequest();

            echo '<pre>'; print_r($response); echo '</pre>';

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

    public function operation(): string
    {
        return 'CheckLoyaltyProgramParticipants';
    }
}