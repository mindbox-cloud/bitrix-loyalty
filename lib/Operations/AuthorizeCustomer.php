<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Operations;

use Mindbox\DTO\V3\Requests\CustomerRequestDTO;
use Mindbox\Loyalty\Models\Customer;

class AuthorizeCustomer extends AbstractOperation
{
    public function execute(Customer $customer): bool
    {
        try {
            $client = $this->api();

            $response = $client->customer()
                ->authorize(
                    $customer->getDto(),
                    $this->getOperation()
                )
                ->sendRequest();

            $responseBody = $response->getBody();

            if ($responseBody['status'] === 'Success') {
                return true;
            }

        } catch (\Mindbox\Exceptions\MindboxClientException $e) {
            // todo походу в операциях будем досылать не выполненные
        }

        return false;
    }

    public function operation(): string
    {
        return 'AuthorizeCustomer';
    }
}