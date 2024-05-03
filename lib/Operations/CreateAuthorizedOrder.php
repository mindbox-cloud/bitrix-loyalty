<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Operations;

use Mindbox\DTO\V3\Requests\PreorderRequestDTO;
use Mindbox\Exceptions\MindboxClientException;
use Mindbox\Exceptions\MindboxUnavailableException;
use Mindbox\Loyalty\Exceptions\ErrorCallOperationException;
use Mindbox\Loyalty\ORM\OrderOperationTypeTable;
use Mindbox\MindboxRequest;
use Mindbox\MindboxResponse;
use Mindbox\Responses\MindboxOrderResponse;

class CreateAuthorizedOrder extends AbstractOperation
{
    private ?MindboxRequest $request = null;
    private ?MindboxResponse $response = null;


    public function execute(PreorderRequestDTO $DTO, ?string $transactionId): void
    {
        $operation = $this->getOperation();

        try {
            $client = $this->api();

            $client->setResponseType(MindboxOrderResponse::class);

            $this->request = $client->prepareRequest(
                'POST',
                $operation,
                $DTO,
                'create',
                array_filter(['transactionId' => $transactionId])
            )->getRequest();

            $this->response = $client->sendRequest();
        } catch (MindboxUnavailableException $e) {
            throw new MindboxUnavailableException($e->getMessage());
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
        return 'CreateAuthorizedOrder';
    }

    public function getRequest(): MindboxRequest
    {
        return $this->request;
    }

    public function getResponse(): ?MindboxResponse
    {
        return $this->response;
    }

    public function getType(): string
    {
        return OrderOperationTypeTable::OPERATION_TYPE_AUTH;
    }
}