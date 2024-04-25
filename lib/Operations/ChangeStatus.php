<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Operations;

use Mindbox\DTO\ResultDTO;
use Mindbox\DTO\V3\Requests\OrderRequestDTO;
use Mindbox\DTO\V3\Requests\PreorderRequestDTO;
use Mindbox\Exceptions\MindboxClientException;
use Mindbox\Loyalty\Exceptions\ErrorCallOperationException;
use Mindbox\MindboxResponse;
use Mindbox\Responses\MindboxOrderResponse;

class ChangeStatus extends AbstractOperation
{
    public function execute(OrderRequestDTO $DTO): MindboxResponse
    {
        $operation = $this->getOperation();

        $client = $this->api()->getClientV3();

        $client->setResponseType(MindboxOrderResponse::class);

        $response = $client->prepareRequest(
            'POST',
            $operation,
            $DTO,
            'create',
            [],
            false,
            false
        )->sendRequest();

        return $response;
    }

    protected function operation(): string
    {
        return 'ChangeStatus';
    }
}
