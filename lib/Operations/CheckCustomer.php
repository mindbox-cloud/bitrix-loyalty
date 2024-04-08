<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Operations;

use Mindbox\DTO\V3\Requests\CustomerRequestDTO;
use Mindbox\Loyalty\Api;
use Mindbox\Loyalty\Models\Customer;

class CheckCustomer extends AbstractOperation
{
    public function execute(Customer $customer): ?array
    {
        try {
            $client = $this->api();

            $dto = new CustomerRequestDTO([
                'mobilePhone' => $customer->getMobilePhone(),
                'email' => $customer->getEmail()
            ]);

            $response = $client->customer()
                ->checkCustomer(
                    $dto,
                    $this->getOperation()
                )
                ->sendRequest();

            $responseBody = $response->getBody();

            if ($responseBody['status'] === 'Success') {
                return $responseBody;
            }

        } catch (\Mindbox\Exceptions\MindboxClientException $e) {
            // todo походу в операциях будем досылать не выполненные
        }

        return null;
    }

    public function operation(): string
    {
        return 'CheckCustomer';
    }

    public function customOperation(): mixed
    {
        return 'BitrixCheckCustomer';
    }
}