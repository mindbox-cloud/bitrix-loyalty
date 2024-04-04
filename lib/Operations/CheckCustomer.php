<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Operations;

use Mindbox\DTO\V3\Requests\CustomerRequestDTO;
use Mindbox\Loyalty\Api;
use Mindbox\Loyalty\Models\Customer;

class CheckCustomer extends AbstractOperation
{
    public function execute(Customer $customer)
    {
        return null;
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

            echo '<pre>'; print_r($responseBody); echo '<pre>';


        } catch (\Mindbox\Exceptions\MindboxClientException $e) {
            echo '<pre>'; print_r($e->getMessage()); echo '<pre>';
        }
    }

    public function operation(): string
    {
        return 'CheckCustomer';
    }
}