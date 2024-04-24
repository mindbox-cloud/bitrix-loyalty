<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Events;

use Bitrix\Main\ObjectNotFoundException;
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
            $settings = SettingsFactory::create();

            $logger = new \Mindbox\Loggers\MindboxFileLogger(
                $settings->getLogPath(),
                LogLevel::INFO
            );

            $logger->info('onAfterUserAdd', $arFields);

            try {
                $customer = new Customer($arFields['ID']);

                $service = new CustomerService($settings);
                $customerData = $service->sync($customer);

                $session = \Bitrix\Main\Application::getInstance()->getSession();

                if (
                    !$customerData->getIsMobilePhoneConfirmed()
                    && $session->has('mindbox_need_confirm_phone')
                ) {
                    $logger->info('onAfterUserAdd confirm phone');

                    $session->remove('mindbox_need_confirm_phone');

                    $service->confirmMobilePhone($customer);
                }

            } catch (ObjectNotFoundException $e) {
                $logger->error('ObjectNotFoundException', ['exception' => $e]);
            } catch (ErrorCallOperationException $e) {
                $logger->error('ErrorCallOperationException', ['exception' => $e]);
            } catch (ValidationErrorCallOperationException $e) {
                $logger->error('ValidationErrorCallOperationException', ['exception' => $e]);
            } catch (\Throwable $throwable) {
                $logger->error('Throwable', ['exception' => $throwable]);
            }
        }
    }

    public static function onAfterUserAuthorize($arUser)
    {

        if (!LoyalityEvents::checkEnableEvent(LoyalityEvents::AUTH)) {
            return true;
        }

        $settings = SettingsFactory::create();

        $logger = new \Mindbox\Loggers\MindboxFileLogger(
            $settings->getLogPath(),
            LogLevel::INFO
        );

        $logger->info('onAfterUserAuthorize', $arUser);
        $logger->info('id', [$arUser['user_fields']['ID']]);

        try {
            $customer = new Customer((int)$arUser['user_fields']['ID']);

            $service = new CustomerService($settings);
            $customerData = $service->sync($customer);

            $service->authorize($customer);

            $session = \Bitrix\Main\Application::getInstance()->getSession();

            if (!$customerData->getIsMobilePhoneConfirmed()
                && $session->has('mindbox_need_confirm_phone')
            ) {
                $logger->info('onAfterUserAuthorize confirm phone');

                $session->remove('mindbox_need_confirm_phone');

                $service->confirmMobilePhone($customer);
            }

            $logger->info('success');
        } catch (ObjectNotFoundException $e) {
            $logger->error('ObjectNotFoundException', ['exception' => $e]);
        } catch (ErrorCallOperationException $e) {
            $logger->error('ErrorCallOperationException', ['exception' => $e]);
        } catch (ValidationErrorCallOperationException $e) {
            $logger->error('ValidationErrorCallOperationException', ['exception' => $e]);
        } catch (\Throwable $throwable) {
            $logger->error('Throwable', ['exception' => $throwable]);
        }
    }

    public static function onAfterUserUpdate($arUser)
    {
        if (!LoyalityEvents::checkEnableEvent(LoyalityEvents::EDIT_USER)) {
            return true;
        }

        $settings = SettingsFactory::create();

        $logger = new \Mindbox\Loggers\MindboxFileLogger(
            $settings->getLogPath(),
            LogLevel::INFO
        );

        $logger->info('onAfterUserUpdate', $arUser);
        $logger->info('id', [$arUser['user_fields']['ID']]);

        try {
            $service = new CustomerService($settings);
            $service->edit(new Customer($arUser['ID']));
        } catch (ObjectNotFoundException $e) {
            $logger->error('ObjectNotFoundException', ['exception' => $e]);
        } catch (ErrorCallOperationException $e) {
            $logger->error('ErrorCallOperationException', ['exception' => $e]);
        } catch (ValidationErrorCallOperationException $e) {
            $logger->error('ValidationErrorCallOperationException', ['exception' => $e]);
        } catch (\Throwable $throwable) {
            $logger->error('Throwable', ['exception' => $throwable]);
        }
    }
}