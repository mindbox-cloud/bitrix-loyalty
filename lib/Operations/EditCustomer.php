<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Operations;

use Mindbox\Loyalty\Entity\UserEdit;
use Mindbox\Loyalty\Models\Customer;

class EditCustomer extends AbstractOperation
{
    public function execute(Customer $customer)
    {
        try {
            $client = $this->api();

            $response = $client->customer()
                ->edit(
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
        return 'EditCustomer';
    }
}