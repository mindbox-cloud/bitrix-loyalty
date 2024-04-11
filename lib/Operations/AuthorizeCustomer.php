<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Operations;

use Mindbox\DTO\V3\Requests\CustomerRequestDTO;
use Mindbox\Exceptions\MindboxClientException;
use Mindbox\Exceptions\MindboxUnavailableException;
use Mindbox\Loyalty\Exceptions\ErrorCallOperationException;
use Mindbox\Loyalty\Models\Customer;

class AuthorizeCustomer extends AbstractOperation
{
    /**
     * @throws ErrorCallOperationException
     */
    public function execute(CustomerRequestDTO $dto): bool
    {
        try {
            $client = $this->api();

            $response = $client->customer()
                ->authorize(
                    customer: $dto,
                    operationName: $this->getOperation(),
                    addDeviceUUID: false,
                    isSync: true
                )
                ->sendRequest();

            $result = $response->getResult();

            if ($result->getStatus() === 'Success') {
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

    protected function operation(): string
    {
        return 'AuthorizeCustomer';
    }
}