<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Services;

use Mindbox\Loyalty\Models\Customer;
use Mindbox\Loyalty\Operations\AuthorizeCustomer;
use Mindbox\Loyalty\Operations\CheckCustomer;
use Mindbox\Loyalty\Operations\EditCustomer;
use Mindbox\Loyalty\Operations\RegisterCustomer;

class CustomerService
{
    protected \Bitrix\Main\DI\ServiceLocator $serviceLocator;

    public function __construct()
    {
        $this->serviceLocator = \Bitrix\Main\DI\ServiceLocator::getInstance();
    }

    public function register(Customer $customer)
    {
        if (!$this->serviceLocator->has('mindboxLoyalty.registerCustomer')) {
            // todo throw
        }

        /** @var RegisterCustomer $test */
        $test = $this->serviceLocator->get('mindboxLoyalty.registerCustomer');

        $test = CheckCustomer::make()->execute($customer);
        $exists = (new CheckCustomer())->execute($customer);

        if ($exists === null) {
            $register = (new RegisterCustomer())->execute($customer);
        }
    }

    public function authorize(Customer $customer)
    {
        $exists = (new CheckCustomer())->execute($customer);

        if ($exists === null) {
            $register = (new RegisterCustomer())->execute($customer);
        }

        if (!$authorize = (new AuthorizeCustomer())->execute($customer)) {
            // todo throw
        }

        return true;
    }

    public function edit(Customer $customer)
    {
        $exists = (new CheckCustomer())->execute($customer);

        if ($exists === null) {
            $register = (new RegisterCustomer())->execute($customer);
            return;
        }

        (new EditCustomer())->execute($customer);
    }
}