<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Services;

use Bitrix\Main\ObjectNotFoundException;
use Mindbox\DTO\V3\Requests\CustomerRequestDTO;
use Mindbox\DTO\V3\Requests\SubscriptionRequestDTO;
use Mindbox\Loyalty\Exceptions\ErrorCallOperationException;
use Mindbox\Loyalty\Exceptions\ValidationErrorCallOperationException;
use Mindbox\Loyalty\Operations\SubscribeCustomer;
use Mindbox\Loyalty\Support\Settings;

class SubscribeService
{
    protected \Bitrix\Main\DI\ServiceLocator $serviceLocator;
    protected Settings $settings;

    public function __construct(Settings $settings)
    {
        $this->serviceLocator = \Bitrix\Main\DI\ServiceLocator::getInstance();
        $this->settings = $settings;
    }
    public function subscribeEmail(string $email): bool
    {
        return $this->subscribe($email, true);
    }

    /**
     * @param string $email
     * @return bool
     * @throws ErrorCallOperationException
     * @throws ObjectNotFoundException
     * @throws ValidationErrorCallOperationException
     */
    public function unsubscribeEmail(string $email): bool
    {
        return $this->subscribe($email, false);
    }

    /**
     * @param string $email
     * @param bool $isSubscribed
     * @return bool
     * @throws ErrorCallOperationException
     * @throws ObjectNotFoundException
     * @throws ValidationErrorCallOperationException
     */
    protected function subscribe(string $email, bool $isSubscribed): bool
    {
        /** @var SubscribeCustomer $operation */
        $operation = $this->serviceLocator->get('mindboxLoyalty.subscribeCustomer');
        $operation->setSettings($this->settings);

        $dto = new CustomerRequestDTO([
            'email' => $email,
        ]);

        $dto->setSubscriptions([
            new SubscriptionRequestDTO([
                'pointOfContact' => 'Email',
                'isSubscribed' => $isSubscribed
            ]),
        ]);

        return $operation->execute($dto);
    }
}
