<?php

namespace Mindbox\Loyalty\Operations;

use Mindbox\DTO\ResultDTO;
use Mindbox\DTO\V3\Requests\CustomerRequestDTO;
use Mindbox\Exceptions\MindboxClientException;
use Mindbox\Helpers\CustomerHelper;
use Mindbox\HttpClients\HttpClientRawResponse;
use Mindbox\Loyalty\Exceptions\ErrorCallOperationException;
use Mindbox\MindboxRequest;
use Mindbox\MindboxResponse;
use Mindbox\Responses\MindboxCustomerResponse;

class GetCustomer extends AbstractOperation
{
    private ?MindboxRequest $request = null;
    private ?MindboxResponse $response = null;

    /**
     * @throws ErrorCallOperationException
     */
    public function execute(CustomerRequestDTO $dto): ResultDTO
    {
        try {
            $client = $this->api();

            $customerCLient = (new CustomerHelper($client))->checkCustomer(
                customer: $dto,
                operationName: $this->getOperation(),
                addDeviceUUID: false
            );
            $this->request = $customerCLient->getRequest();
            $this->response = $customerCLient->sendRequest();

            return $this->response->getResult();
        } catch (MindboxClientException $e) {
            throw new ErrorCallOperationException(
                message: sprintf('The operation %s failed', $this->getOperation()),
                previous: $e,
                operationName: $this->getOperation()
            );
        }
    }

    protected function operation(): string
    {
        return 'CheckCustomer';
    }

    public function getRequest(): ?MindboxRequest
    {
        return $this->request;
    }

    public function getResponse(): ?MindboxResponse
    {
        return $this->response;
    }
}