<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Operations;

use Mindbox\DTO\DTO;
use Mindbox\Exceptions\MindboxClientException;
use Mindbox\Loyalty\Exceptions\ErrorCallOperationException;
use Mindbox\MindboxRequest;
use Mindbox\MindboxResponse;

final class SetFavourite extends AbstractOperation
{
    private ?MindboxRequest $request = null;
    private ?MindboxResponse $response = null;

    /**
     * @param DTO $dto
     * @return void
     * @throws ErrorCallOperationException
     */
    public function execute(DTO $dto)
    {
        try {
            $operation = $this->getOperation();
            $client = $this->api();

            $client->setResponseType(MindboxResponse::class);

            $this->request = $client->prepareRequest(
                method: 'POST',
                operationName: $operation,
                body: $dto,
                isSync: false,
                addDeviceUUID: true
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
    }

    protected function operation(): string
    {
        return 'SetFavourite';
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