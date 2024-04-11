<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Event;


use Bitrix\Main\Diag\Debug;
use Bitrix\Main\ObjectNotFoundException;
use Mindbox\Loyalty\Entity\User;
use Mindbox\Loyalty\Exceptions\ErrorCallOperationException;
use Mindbox\Loyalty\Exceptions\ValidationErrorCallOperationException;
use Mindbox\Loyalty\Models\Customer;
use Mindbox\Loyalty\Services\CustomerService;
use Mindbox\Loyalty\Support\LoyalityEvents;
use Mindbox\Loyalty\Support\SettingsFactory;
use Psr\Log\LogLevel;

class CustomerEvent
{
    public static function onAfterUserAdd(array &$arFields)
    {
        if (!LoyalityEvents::checkEnableEvent(LoyalityEvents::REGISTRATION)) {
            return true;
        }

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

        $logger = new \Mindbox\Loggers\MindboxFileLogger(
            $settings->getLogPath(),
            LogLevel::DEBUG
        );
        $logger->error('onAfterUserAuthorize', $arUser);
        $logger->info('id', [$arUser['user_fields']['ID']]);

        if (!LoyalityEvents::checkEnableEvent(LoyalityEvents::AUTH)) {
            return true;
        }

        try {
            $service = new CustomerService();
            $service->authorize(new Customer((int)$arUser['user_fields']['ID']));
            $logger->info('успех');
        } catch (ObjectNotFoundException $e) {
            $logger->emergency('ObjectNotFoundException', ['exception' => $e]);
        } catch (ErrorCallOperationException $e) {
            $logger->emergency('ErrorCallOperationException', ['exception' => $e]);
        } catch (ValidationErrorCallOperationException $e) {
            $logger->emergency('ValidationErrorCallOperationException', ['exception' => $e]);
        } catch (\Throwable $throwable) {
            $logger->emergency('Throwable', ['exception' => $throwable]);
        }
    }

    public static function onAfterUserUpdate($arUser)
    {
        if (!LoyalityEvents::checkEnableEvent(LoyalityEvents::EDIT_USER)) {
            return true;
        }
        
        try {
            $service = new CustomerService();
            $service->edit(new Customer($arUser['ID']));
        } catch (ObjectNotFoundException $e) {
        } catch (ErrorCallOperationException $e) {
        } catch (ValidationErrorCallOperationException $e) {
        }
    }
}