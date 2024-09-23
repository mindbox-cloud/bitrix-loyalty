<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Operations;

use Mindbox\DTO\DTO;
use Mindbox\DTO\V3\Requests\CustomerRequestDTO;
use Mindbox\Exceptions\MindboxClientException;
use Mindbox\Loyalty\Exceptions\ErrorCallOperationException;
use Mindbox\MindboxRequest;
use Mindbox\MindboxResponse;
use Mindbox\Responses\MindboxCustomerProcessingStatusResponse;

class ConfirmEmail extends AbstractOperation
{
    private ?MindboxRequest $request = null;
    private ?MindboxResponse $response = null;

    /**
     * @throws ErrorCallOperationException
     */
    public function execute(CustomerRequestDTO $dto): bool
    {
        try {
            $operation = $this->getOperation();
            $client = $this->api();

            $client->setResponseType(MindboxCustomerProcessingStatusResponse::class);

            $this->request = $client->prepareRequest(
                method: 'POST',
                operationName: $operation,
                body: new DTO(['customer' => $dto]),
                isSync: false,
                addDeviceUUID: false
            )->getRequest();

            $this->response = $client->sendRequest();

            if ($this->response->getResult()->getStatus() === 'Success') {
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
        return 'ConfirmEmail';
    }
}