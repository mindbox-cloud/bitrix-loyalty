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
use Mindbox\Loyalty\Support\Settings;

class CustomerService
{
    protected \Bitrix\Main\DI\ServiceLocator $serviceLocator;
    protected Settings $settings;

    public function __construct(Settings $settings)
    {
        $this->serviceLocator = \Bitrix\Main\DI\ServiceLocator::getInstance();
        $this->settings = $settings;
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
        $operationRegisterCustomer->setSettings($this->settings);

        /** @var CheckCustomer $operationCheckCustomer */
        $operationCheckCustomer = $this->serviceLocator->get('mindboxLoyalty.checkCustomer');
        $operationRegisterCustomer->setSettings($this->settings);


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
        $operationRegisterCustomer->setSettings($this->settings);

        /** @var CheckCustomer $operationCheckCustomer */
        $operationCheckCustomer = $this->serviceLocator->get('mindboxLoyalty.checkCustomer');
        $operationCheckCustomer->setSettings($this->settings);

        /** @var AuthorizeCustomer $operationAuthorizeCustomer */
        $operationAuthorizeCustomer = $this->serviceLocator->get('mindboxLoyalty.authorizeCustomer');
        $operationAuthorizeCustomer->setSettings($this->settings);

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
        $operationRegisterCustomer->setSettings($this->settings);

        /** @var CheckCustomer $operationCheckCustomer */
        $operationCheckCustomer = $this->serviceLocator->get('mindboxLoyalty.checkCustomer');
        $operationCheckCustomer->setSettings($this->settings);

        /** @var EditCustomer $operationEditCustomer */
        $operationEditCustomer = $this->serviceLocator->get('mindboxLoyalty.editCustomer');
        $operationEditCustomer->setSettings($this->settings);

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
     * @throws ErrorCallOperationException
     * @throws ObjectNotFoundException
     */
    public function exists(Customer $customer): bool
    {
        /** @var CheckCustomer $operationCheckCustomer */
        $operationCheckCustomer = $this->serviceLocator->get('mindboxLoyalty.checkCustomer');
        $operationCheckCustomer->setSettings($this->settings);

        return $operationCheckCustomer->execute( new CustomerRequestDTO([
            'mobilePhone' => $customer->getMobilePhone(),
        ]));
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
        $operation->setSettings($this->settings);

        return $operation->execute($phone);
    }
}