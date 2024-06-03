<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Events;

use Bitrix\Main\ObjectNotFoundException;
use Bitrix\Main\UserTable;
use Mindbox\Loyalty\Exceptions\ErrorCallOperationException;
use Mindbox\Loyalty\Exceptions\IntegrationLoyaltyException;
use Mindbox\Loyalty\Exceptions\ValidationErrorCallOperationException;
use Mindbox\Loyalty\Models\Customer;
use Mindbox\Loyalty\ORM\DeliveryDiscountTable;
use Mindbox\Loyalty\Services\CustomerService;
use Mindbox\Loyalty\Support\EmailChangeChecker;
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

                // Пользователь создается на сайте через СМС авторизацию, считаем телефон подтвержденным
                $session = \Bitrix\Main\Application::getInstance()->getSession();
                if (
                    $session->has('mindbox_need_confirm_phone')
                    && !$customerData->getIsMobilePhoneConfirmed()
                ) {
                    $logger->info('onAfterUserAdd confirm phone');

                    $session->remove('mindbox_need_confirm_phone');
                    $service->confirmMobilePhone($customer);
                }

                // Функционал подтверждения email
                if ($session->has('mindbox_need_confirm_email')) {
                    $session->remove('mindbox_need_confirm_email');
                    if (!$customerData->getIsEmailConfirmed()) {
                        $service->confirmEmail($customer);
                    }
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

            // На случай, если авторизация по телефону не через МБ
            $session = \Bitrix\Main\Application::getInstance()->getSession();
            if (
                $session->has('mindbox_need_confirm_phone')
                && !$customerData->getIsMobilePhoneConfirmed()
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
            $customer = new Customer((int) $arUser['ID']);
            $service = new CustomerService($settings);

            // Если при смене телефона происходит его подтверждение по СМС, не через МБ
            $session = \Bitrix\Main\Application::getInstance()->getSession();
            if ($session->has('mindbox_need_confirm_phone')) {
                $session->remove('mindbox_need_confirm_phone');
                $service->confirmMobilePhone($customer);
            }

            $service->edit($customer);
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

    public static function onSaleUserDelete($id)
    {
        if (class_exists('\\\Mindbox\\Loyalty\\ORM\\DeliveryDiscountTable')) {
            $iterator = DeliveryDiscountTable::getList([
                'filter' => ['=FUSER_ID' => $id, 'ORDER_ID' => null],
                'select' => ['ID'],
            ]);

            while ($row = $iterator->fetch()) {
                DeliveryDiscountTable::delete($row['ID']);
            }
        }
    }

    public static function onBeforeCheckedChangeEmail(&$arFields)
    {
        if (!LoyalityEvents::checkEnableEvent(LoyalityEvents::CHECK_CHANGE_USER_EMAIL)) {
            return;
        }

        if (!isset($arFields['EMAIL']) || empty($arFields['EMAIL'])) {
            return;
        }

        $userId = (int) $arFields['ID'];

        $userData = UserTable::getRow([
            'filter' => ['=ID' => $userId],
            'select' => ['ID', 'EMAIL'],
        ]);

        if ($userData['EMAIL']) {
            EmailChangeChecker::getInstance()->setEmail($userData['EMAIL']);
        }
    }

    public static function onCheckedChangeEmail(&$arFields)
    {
        if (!$arFields['RESULT']) {
            return;
        }

        if (!LoyalityEvents::checkEnableEvent(LoyalityEvents::CHECK_CHANGE_USER_EMAIL)) {
            return;
        }

        if (!isset($arFields['EMAIL']) || empty($arFields['EMAIL'])) {
            return;
        }

        if (!EmailChangeChecker::getInstance()->check($arFields['EMAIL'])) {
            return;
        }

        try {
            $userId = (int) $arFields['ID'];

            $customer = new Customer($userId);
            $settings = SettingsFactory::create();
            $service = new CustomerService($settings);

            $service->confirmEmail($customer);
        } catch (IntegrationLoyaltyException $e) {
            // @info Добавить логирование?
        }
    }

    public static function onUserLogout()
    {
        \Mindbox\Loyalty\Support\SessionStorage::getInstance()->clear();
    }
}