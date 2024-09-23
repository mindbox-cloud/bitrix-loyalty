<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Operations;

use Mindbox\DTO\DTO;

class ClearFavourite extends AbstractOperation
{
    public function execute(DTO $DTO): void
    {
        $client = $this->api();

        $request = $client->prepareRequest(
            method: 'POST',
            operationName: $this->getOperation(),
            body: $DTO,
            isSync: false,
            addDeviceUUID: false
        );

        $request->sendRequest();
    }

    protected function operation(): string
    {
        return 'ClearFavourite';
    }

}