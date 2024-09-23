<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Operations;

use Mindbox\DTO\V3\Requests\CustomerRequestDTO;
use Mindbox\Exceptions\MindboxClientException;
use Mindbox\Exceptions\MindboxUnavailableException;
use Mindbox\Helpers\CustomerHelper;
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

            $response = (new CustomerHelper($client))
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
        return 'AuthorizeCustomer';
    }
}