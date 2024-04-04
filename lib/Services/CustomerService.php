<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Services;

use Mindbox\Loyalty\Models\Customer;
use Mindbox\Loyalty\Operations\CheckCustomer;
use Mindbox\Loyalty\Operations\RegisterCustomer;

class CustomerService
{
    public function register(Customer $customer)
    {
        $exists = (new CheckCustomer())->execute($customer);

        if ($exists === null) {
            $register = (new RegisterCustomer())->execute($customer);
        }
    }
}