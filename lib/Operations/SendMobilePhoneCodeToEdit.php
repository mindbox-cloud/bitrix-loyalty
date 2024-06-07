<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Operations;

use Mindbox\DTO\V3\OperationDTO;
use Mindbox\DTO\V3\Requests\CustomerRequestDTO;
use Mindbox\Exceptions\MindboxClientException;
use Mindbox\Exceptions\MindboxUnavailableException;
use Mindbox\Helpers\CustomerHelper;
use Mindbox\Loyalty\Exceptions\ErrorCallOperationException;
use Mindbox\Loyalty\Exceptions\ValidationErrorCallOperationException;
use Mindbox\Loyalty\Operations\AbstractOperation;
use Mindbox\MindboxRequest;
use Mindbox\MindboxResponse;
use Mindbox\Responses\MindboxCustomerProcessingStatusResponse;
use Mindbox\Responses\MindboxOrderResponse;

class SendMobilePhoneCodeToEdit extends AbstractOperation
{
    private ?MindboxRequest $request = null;
    private ?MindboxResponse $response = null;

    public function execute(CustomerRequestDTO $customerDTO)
    {
        try {
            $operation = $this->getOperation();
            $client = $this->api();

            $client->setResponseType(MindboxCustomerProcessingStatusResponse::class);
            $DTO = new OperationDTO();
            $DTO->setCustomer($customerDTO);

            $this->request = $client->prepareRequest(
                method: 'POST',
                operationName: $operation,
                body: $DTO,
                isSync: false,
                addDeviceUUID: false
            )->getRequest();

            $this->response = $client->sendRequest();
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
        return 'SendMobilePhoneCodeToEdit';
    }

    public function getRequest(): MindboxRequest
    {
        return $this->request;
    }

    public function getResponse(): ?MindboxResponse
    {
        return $this->response;
    }

}