<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Services;

use Bitrix\Main\ObjectNotFoundException;
use Mindbox\DTO\V3\Requests\CustomerRequestDTO;
use Mindbox\Loyalty\Exceptions\ErrorCallOperationException;
use Mindbox\Loyalty\Exceptions\ValidationErrorCallOperationException;
use Mindbox\Loyalty\Models\Customer;
use Mindbox\Loyalty\Operations\AuthorizeCustomer;
use Mindbox\Loyalty\Operations\CheckCustomer;
use Mindbox\Loyalty\Operations\EditCustomer;
use Mindbox\Loyalty\Operations\RegisterCustomer;
use Mindbox\Loyalty\Operations\SendMobilePhoneAuthorizationCode;

class CustomerService
{
    protected \Bitrix\Main\DI\ServiceLocator $serviceLocator;

    public function __construct()
    {
        $this->serviceLocator = \Bitrix\Main\DI\ServiceLocator::getInstance();
    }

    /**
     * @throws ObjectNotFoundException
     * @throws ErrorCallOperationException
     * @throws ValidationErrorCallOperationException
     */
    public function register(Customer $customer): bool
    {
        /** @var RegisterCustomer $operationRegisterCustomer */
        $operationRegisterCustomer = $this->serviceLocator->get('mindboxLoyalty.registerCustomer');

        /** @var CheckCustomer $operationCheckCustomer */
        $operationCheckCustomer = $this->serviceLocator->get('mindboxLoyalty.checkCustomer');


        $exists = $operationCheckCustomer->execute(
            new CustomerRequestDTO([
                'mobilePhone' => $customer->getMobilePhone(),
            ])
        );

        if (!$exists) {
            if ($operationRegisterCustomer->execute($customer->getDto())) {
                return true;
            }
        }

        return false;
    }

    /**
     * @throws ErrorCallOperationException
     * @throws ValidationErrorCallOperationException
     * @throws ObjectNotFoundException
     */
    public function authorize(Customer $customer): bool
    {
        /** @var RegisterCustomer $operationRegisterCustomer */
        $operationRegisterCustomer = $this->serviceLocator->get('mindboxLoyalty.registerCustomer');

        /** @var CheckCustomer $operationCheckCustomer */
        $operationCheckCustomer = $this->serviceLocator->get('mindboxLoyalty.checkCustomer');

        /** @var AuthorizeCustomer $operationAuthorizeCustomer */
        $operationAuthorizeCustomer = $this->serviceLocator->get('mindboxLoyalty.authorizeCustomer');

        $exists = $operationCheckCustomer->execute(
            new CustomerRequestDTO([
                'mobilePhone' => $customer->getMobilePhone(),
            ])
        );

        if (!$exists) {
            $operationRegisterCustomer->execute($customer->getDto());
        }

        if (!$operationAuthorizeCustomer->execute($customer->getDto())) {
            return false;
        }

        return true;
    }

    /**
     * @throws ErrorCallOperationException
     * @throws ValidationErrorCallOperationException
     * @throws ObjectNotFoundException
     */
    public function edit(Customer $customer): bool
    {
        /** @var RegisterCustomer $operationRegisterCustomer */
        $operationRegisterCustomer = $this->serviceLocator->get('mindboxLoyalty.registerCustomer');

        /** @var CheckCustomer $operationCheckCustomer */
        $operationCheckCustomer = $this->serviceLocator->get('mindboxLoyalty.checkCustomer');

        /** @var EditCustomer $operationEditCustomer */
        $operationEditCustomer = $this->serviceLocator->get('mindboxLoyalty.editCustomer');

        $exists = $operationCheckCustomer->execute(
            new CustomerRequestDTO([
                'mobilePhone' => $customer->getMobilePhone(),
            ])
        );

        if (!$exists) {
            $operationRegisterCustomer->execute($customer->getDto());
            return true;
        }

        if ($operationEditCustomer->execute($customer->getDto())) {
            return true;
        }

        return false;
    }

    /**
     * @param string $phone
     * @return bool
     * @throws ErrorCallOperationException
     * @throws ObjectNotFoundException
     * @throws ValidationErrorCallOperationException
     */
    public function sendAuthorizeCode(string $phone): bool
    {
        /** @var SendMobilePhoneAuthorizationCode $operation */
        $operation = $this->serviceLocator->get('mindboxLoyalty.sendMobilePhoneAuthorizationCode');

        return $operation->execute($phone);
    }
}