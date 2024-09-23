<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Operations;

use Mindbox\DTO\DTO;
use Mindbox\DTO\V3\CustomerIdentityDTO;
use Mindbox\DTO\V3\Requests\CustomerRequestDTO;
use Mindbox\DTO\V3\Requests\ProductListItemRequestCollection;
use Mindbox\DTO\V3\Requests\ProductRequestDTO;
use Mindbox\Exceptions\MindboxClientException;
use Mindbox\Helpers\CustomerHelper;
use Mindbox\Helpers\ProductListHelper;
use Mindbox\Loyalty\Exceptions\ErrorCallOperationException;
use Mindbox\Loyalty\Models\Customer;

class EditProductList extends AbstractOperation
{
    /**
     * @param ?CustomerIdentityDTO $customer
     * @param ProductListItemRequestCollection $linesCollection
     * @return void
     * @throws ErrorCallOperationException
     */
    public function execute(array $payload): void
    {
        try {
            $client = $this->api();

            $request = $client->prepareRequest(
                method: 'POST',
                operationName: $this->getOperation(),
                body: new DTO($payload),
                isSync: true,
                addDeviceUUID: true
            );

            $request->sendRequest();

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
        return 'EditFavourite';
    }
}