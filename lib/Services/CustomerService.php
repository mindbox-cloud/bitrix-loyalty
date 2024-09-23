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
use Mindbox\Loyalty\Operations\CheckMobilePhoneCode;
use Mindbox\Loyalty\Operations\ConfirmEmail;
use Mindbox\Loyalty\Operations\ConfirmMobilePhone;
use Mindbox\Loyalty\Operations\EditCustomer;
use Mindbox\Loyalty\Operations\GetCustomerPoints;
use Mindbox\Loyalty\Operations\SendMobilePhoneCodeToEdit;
use Mindbox\Loyalty\Operations\SyncCustomer;
use Mindbox\Loyalty\Operations\SendMobilePhoneCode;
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
     * @throws ErrorCallOperationException
     * @throws ObjectNotFoundException
     */
    public function authorize(Customer $customer): bool
    {
        /** @var AuthorizeCustomer $operationAuthorizeCustomer */
        $operationAuthorizeCustomer = $this->serviceLocator->get('mindboxLoyalty.authorizeCustomer');
        $operationAuthorizeCustomer->setSettings($this->settings);

        return $operationAuthorizeCustomer->execute($customer->getDto());
    }

    /**
     * @throws ErrorCallOperationException
     * @throws ValidationErrorCallOperationException
     * @throws ObjectNotFoundException
     */
    public function edit(Customer $customer): bool
    {
        /** @var EditCustomer $operationEditCustomer */
        $operationEditCustomer = $this->serviceLocator->get('mindboxLoyalty.editCustomer');
        $operationEditCustomer->setSettings($this->settings);

        return $operationEditCustomer->execute($customer->getDto());
    }

    /**
     * @todo эту пока оставляю, думаю пригодиться
     * @throws ErrorCallOperationException
     * @throws ObjectNotFoundException
     */
    public function exists(Customer $customer): bool
    {
        /** @var CheckCustomer $operationCheckCustomer */
        $operationCheckCustomer = $this->serviceLocator->get('mindboxLoyalty.checkCustomer');
        $operationCheckCustomer->setSettings($this->settings);

        $requestData = [];

        if (!empty($customer->getMobilePhone())) {
            $requestData['mobilePhone'] = $customer->getMobilePhone();
        } elseif ($customer->getEmail()) {
            $requestData['email'] = $customer->getEmail();
        } else {
            $requestData['ids'] = $customer->getIds();
        }

        return $operationCheckCustomer->execute(new CustomerRequestDTO($requestData));
    }

    /**
     * @throws ObjectNotFoundException
     * @throws ErrorCallOperationException
     */
    public function sync(Customer $customer): ?\Mindbox\DTO\V3\Responses\CustomerResponseDTO
    {
        /** @var SyncCustomer $operation */
        $operation = $this->serviceLocator->get('mindboxLoyalty.syncCustomer');
        $operation->setSettings($this->settings);

        return $operation->execute($customer->getDto());
    }

    /**
     * @throws ObjectNotFoundException
     * @throws ErrorCallOperationException
     */
    public function checkMobilePhoneCode(string $phone, string $code): bool
    {
        /** @var CheckMobilePhoneCode $operation */
        $operation = $this->serviceLocator->get('mindboxLoyalty.checkMobilePhoneCode');
        $operation->setSettings($this->settings);

        $result = $operation->execute($phone, $code);

        if ($result) {
            // todo delay event
            $session = \Bitrix\Main\Application::getInstance()->getSession();
            $session->set('mindbox_need_confirm_phone', true);
        }

        return $result;
    }

    /**
     * @throws ErrorCallOperationException
     * @throws ObjectNotFoundException
     */
    public function confirmMobilePhone(Customer $customer): bool
    {
        /** @var ConfirmMobilePhone $operation */
        $operation = $this->serviceLocator->get('mindboxLoyalty.confirmMobilePhone');
        $operation->setSettings($this->settings);

        return $operation->execute(new CustomerRequestDTO([
            'mobilePhone' => $customer->getMobilePhone(),
            'ids' => $customer->getIds()
        ]));
    }

    /**
     * @throws ErrorCallOperationException
     * @throws ObjectNotFoundException
     */
    public function confirmEmail(Customer $customer): bool
    {
        /** @var ConfirmEmail $operation */
        $operation = $this->serviceLocator->get('mindboxLoyalty.confirmEmail');
        $operation->setSettings($this->settings);

        return $operation->execute(new CustomerRequestDTO([
            'email' => $customer->getEmail(),
            'ids' => $customer->getIds()
        ]));
    }

    /**
     * @throws ErrorCallOperationException
     * @throws ObjectNotFoundException
     */
    public function autoConfirmMobilePhone(Customer $customer): void
    {
        $customerInfo = $this->sync($customer);

        if ($customerInfo && !$customerInfo->getIsMobilePhoneConfirmed()) {
            $this->confirmMobilePhone($customer);
        }
    }


    /**
     * @throws ErrorCallOperationException
     * @throws ObjectNotFoundException
     */
    public function confirmMobilePhoneByUserId(int $userId): bool
    {
        return $this->confirmMobilePhone(new Customer($userId));
    }

    /**
     * @param string $phone
     * @return bool
     * @throws ErrorCallOperationException
     * @throws ObjectNotFoundException
     * @throws ValidationErrorCallOperationException
     */
    public function sendMobilePhoneCode(string $phone): bool
    {
        /** @var SendMobilePhoneCode $operation */
        $operation = $this->serviceLocator->get('mindboxLoyalty.sendMobilePhoneCode');
        $operation->setSettings($this->settings);

        return $operation->execute($phone);
    }

    /**
     * @param CustomerRequestDTO $customerDTO
     * @return bool
     * @throws ErrorCallOperationException
     * @throws ObjectNotFoundException
     * @throws ValidationErrorCallOperationException
     */
    public function sendMobilePhoneCodeToEdit(CustomerRequestDTO $customerDTO): bool
    {
        /** @var SendMobilePhoneCodeToEdit $operation */
        $operation = $this->serviceLocator->get('mindboxLoyalty.sendMobilePhoneCodeToEdit');
        $operation->setSettings($this->settings);

        return $operation->execute($customerDTO);
    }

    /**
     * @param Customer $customer
     * @param string|null $balanceSystemName
     * @return int
     * @throws ErrorCallOperationException
     * @throws ObjectNotFoundException
     * @throws \Psr\Container\NotFoundExceptionInterface
     */

    public function getAvailableBonuses(Customer $customer, ?string $balanceSystemName = null): int
    {
        /** @var GetCustomerPoints $operation */
        $operation = $this->serviceLocator->get('mindboxLoyalty.getCustomerPoints');
        $operation->setSettings($this->settings);

        if (!$balanceSystemName) {
            $balanceSystemName = $this->settings->getBalanceSystemName();
        }

        $response = $operation->execute(new CustomerRequestDTO([
            'ids' => $customer->getIds()
        ]));

        if ($response->getResult()->getStatus() !== 'Success') {
            return 0;
        }

        $availableBonuses = 0;
        $balanceCollection = $response->getResult()->getBalances();

        /** @var \Mindbox\DTO\V3\Responses\BalanceResponseDTO $item */
        foreach ($balanceCollection as $item) {
            if ($balanceSystemName) {
                if ($balanceSystemName === $item->getField('systemName')) {
                    $availableBonuses += $item->getField('available');
                    break;
                }
            } else {
                $availableBonuses += $item->getField('available');
            }
        }

        return (int)$availableBonuses;
    }
}
