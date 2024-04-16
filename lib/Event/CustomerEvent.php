<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Event;


use Bitrix\Main\ObjectNotFoundException;
use Mindbox\Loyalty\Entity\User;
use Mindbox\Loyalty\Exceptions\ErrorCallOperationException;
use Mindbox\Loyalty\Exceptions\ValidationErrorCallOperationException;
use Mindbox\Loyalty\Models\Customer;
use Mindbox\Loyalty\Services\CustomerService;
use Mindbox\Loyalty\Support\SettingsFactory;

class CustomerEvent
{
    public static function onAfterUserAdd(array &$arFields)
    {
        if (!empty($arFields['ID'])) {
            try {
                $service = new CustomerService();
                $service->register(new Customer($arFields['ID']));
            } catch (ObjectNotFoundException $e) {
            } catch (ErrorCallOperationException $e) {
            } catch (ValidationErrorCallOperationException $e) {
            }
        }
    }

    public static function onAfterUserAuthorize($arUser)
    {
        $settings = SettingsFactory::create();

        try {
            $service = new CustomerService();
            $service->authorize(new Customer((int)$arUser['user_fields']['ID']));
        } catch (ObjectNotFoundException $e) {
        } catch (ErrorCallOperationException $e) {
        } catch (ValidationErrorCallOperationException $e) {
        } catch (\Throwable $throwable) {
        }
    }

    public static function onAfterUserUpdate($arUser)
    {
        try {
            $service = new CustomerService();
            $service->edit(new Customer($arUser['ID']));
        } catch (ObjectNotFoundException $e) {
        } catch (ErrorCallOperationException $e) {
        } catch (ValidationErrorCallOperationException $e) {
        }
    }
}