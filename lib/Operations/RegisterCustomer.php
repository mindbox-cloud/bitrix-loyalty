<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Operations;

use Mindbox\Loyalty\Api;
use Mindbox\Loyalty\Models\Customer;
use Mindbox\Loyalty\Util\Logger;

class RegisterCustomer extends AbstractOperation
{
    public function execute(Customer $customer)
    {
        try {
            $client = $this->api();

            $response = $client->customer()
                ->register(
                    $customer->getDto(),
                    $this->getOperation()
                )
                ->sendRequest();

            $responseBody = $response->getBody();


        } catch (\Mindbox\Exceptions\MindboxClientException $e) {

            var_dump($e->getMessage());
        }
    }

    public function operation(): string
    {
        return 'RegisterCustomer';
    }
}