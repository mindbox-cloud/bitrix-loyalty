<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Operations;

use Bitrix\Sale\Order;
use Mindbox\DTO\V3\Requests\PreorderRequestDTO;
use Mindbox\DTO\V3\Responses\OrderResponseDTO;
use Mindbox\Exceptions\MindboxClientException;
use Mindbox\Exceptions\MindboxUnavailableException;
use Mindbox\Helpers\OrderHelper;
use Mindbox\Loyalty\Exceptions\ErrorCallOperationException;
use Mindbox\Loyalty\Exceptions\ValidationErrorCallOperationException;
use Mindbox\Loyalty\ORM\OrderOperationTypeTable;
use Mindbox\MindboxRequest;
use Mindbox\MindboxResponse;
use Mindbox\Responses\MindboxOrderResponse;

class CalculateUnauthorizedCart extends AbstractOperation
{
    private ?MindboxRequest $request = null;
    private ?MindboxResponse $response = null;

    /**
     * @throws ErrorCallOperationException
     */
    public function execute(PreorderRequestDTO $DTO): MindboxResponse
    {
        try {
            $operation = $this->getOperation();
            $client = $this->api();

            $client->setResponseType(MindboxOrderResponse::class);

            $this->request = $client->prepareRequest(
                method: 'POST',
                operationName:$operation,
                body: $DTO,
                addDeviceUUID: true
            )->getRequest();

            $this->response = $client->sendRequest();

            return $this->response;
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
        return 'CalculateUnauthorizedOrder';
    }
}