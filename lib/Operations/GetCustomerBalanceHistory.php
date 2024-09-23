<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Operations;

use Mindbox\DTO\DTO;
use Mindbox\DTO\V3\Requests\CustomerRequestDTO;
use Mindbox\DTO\V3\Requests\PageRequestDTO;
use Mindbox\DTO\V3\Responses\BalanceChangeKindResponseDTO;
use Mindbox\Exceptions\MindboxClientException;
use Mindbox\Loyalty\Exceptions\ErrorCallOperationException;
use Mindbox\MindboxResponse;
use Mindbox\Responses\MindboxBalanceResponse;

class GetCustomerBalanceHistory extends AbstractOperation
{
    /**
     * @throws ErrorCallOperationException
     */
    public function execute(CustomerRequestDTO $customerRequestDTO, PageRequestDTO $pageRequestDTO): MindboxResponse
    {
        try {
            $operation = $this->getOperation();
            $client = $this->api();

            $client->prepareRequest(
                method: 'POST',
                operationName: $operation,
                body: new DTO(['customer' => $customerRequestDTO, 'page' => $pageRequestDTO]),
                addDeviceUUID: false
            );

            return $client->sendRequest();
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
        return 'GetCustomerBalanceHistory';
    }
}
