<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Operations;

use Mindbox\DTO\V3\Requests\OrderRequestDTO;
use Mindbox\MindboxRequest;
use Mindbox\MindboxResponse;
use Mindbox\Responses\MindboxOrderResponse;

class SaveOfflineOrder extends AbstractOperation
{
    private ?MindboxRequest $request = null;
    private ?MindboxResponse $response = null;

    /**
     * @param OrderRequestDTO $DTO
     * @return void
     * @throws \Mindbox\Exceptions\MindboxBadRequestException
     * @throws \Mindbox\Exceptions\MindboxClientException
     * @throws \Mindbox\Exceptions\MindboxConflictException
     * @throws \Mindbox\Exceptions\MindboxForbiddenException
     * @throws \Mindbox\Exceptions\MindboxNotFoundException
     * @throws \Mindbox\Exceptions\MindboxTooManyRequestsException
     * @throws \Mindbox\Exceptions\MindboxUnauthorizedException
     * @throws \Mindbox\Exceptions\MindboxUnavailableException
     */
    public function execute(OrderRequestDTO $DTO): void
    {
        $operation = $this->getOperation();

        $client = $this->api();

        $client->setResponseType(MindboxOrderResponse::class);

        $this->request = $client->prepareRequest(
            'POST',
            $operation,
            $DTO,
            'create',
            [],
            false,
            false
        )->getRequest();

        $this->response = $client->sendRequest();
    }

    public function getRequest(): ?MindboxRequest
    {
        return $this->request;
    }

    public function getResponse(): ?MindboxResponse
    {
        return $this->response;
    }

    protected function operation(): string
    {
        return 'SaveOfflineOrder';
    }
}