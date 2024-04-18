<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Operations;

use Mindbox\DTO\V3\Requests\PreorderRequestDTO;
use Mindbox\Exceptions\MindboxClientException;
use Mindbox\Exceptions\MindboxUnavailableException;
use Mindbox\Loyalty\Exceptions\ErrorCallOperationException;
use Mindbox\MindboxResponse;
use Mindbox\Responses\MindboxOrderResponse;

class CreateAuthorizedOrder extends AbstractOperation
{
    public function execute(PreorderRequestDTO $DTO, ?string $transactionId): MindboxResponse
    {
        $operation = $this->getOperation();

        try {
            $client = $this->api()->getClientV3();

            $client->setResponseType(MindboxOrderResponse::class);

            $response = $client->prepareRequest(
                'POST',
                $operation,
                $DTO,
                'create',
                array_filter(['transactionId' => $transactionId])
            )->sendRequest();

            return $response;
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
}