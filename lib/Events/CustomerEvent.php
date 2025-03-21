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
use Mindbox\Loyalty\Services\SubscribeService;
use Mindbox\Loyalty\Support\EmailChangeChecker;
use Mindbox\Loyalty\Support\FeatureManager;
use Mindbox\Loyalty\Support\LoyalityEvents;
use Mindbox\Loyalty\Support\SettingsFactory;
use Psr\Log\LogLevel;

class CustomerEvent
{
    public static function onAfterUserAdd(array &$arFields)
    {
        if (empty($arFields['ID'])) {
            return true;
        }

        \Mindbox\Loyalty\Support\FeatureManager::setHitUserRegister();

        $userId = (int)$arFields['ID'];
        $userGroupArray = \Bitrix\Main\UserTable::getUserGroupIds($userId);

        $settings = SettingsFactory::create();

        try {
            $session = \Bitrix\Main\Application::getInstance()->getSession();
            $customer = new Customer($userId);

            SubscribeService::setSubscriptionsToCustomer($customer, $settings);

            $service = new CustomerService($settings);

            // Регистрация пользователя
            if (
                LoyalityEvents::checkEnableEvent(LoyalityEvents::REGISTRATION)
                && LoyalityEvents::checkEnableEventsForUserGroup(LoyalityEvents::REGISTRATION, $userGroupArray, $settings)
            ) {
                $service->sync($customer);

                // Пользователь создается на сайте через СМС авторизацию, считаем телефон подтвержденным
                // Проставляем телефон в МБ статус Подтвержден
                if ($session->has('mindbox_need_confirm_phone')) {
                    $session->remove('mindbox_need_confirm_phone');
                    $service->confirmMobilePhone($customer);
                }
            }

            // Подтверждение email пользователя
            if (
                $customer->getEmail()
                && LoyalityEvents::checkEnableEvent(LoyalityEvents::CHECK_CHANGE_USER_EMAIL)
                && LoyalityEvents::checkEnableEventsForUserGroup(LoyalityEvents::CHECK_CHANGE_USER_EMAIL, $userGroupArray, $settings)
                && $service->confirmEmail($customer)
            ) {
                // todo Перенести все коды в отдельный enum класс
                $session->set('mindbox_send_confirm_email', 'Y');
            }
        } catch (IntegrationLoyaltyException $exception) {
        }
    }

    public static function onAfterUserAuthorize($arUser)
    {
        \Mindbox\Loyalty\Support\FeatureManager::setHitUserLogin();

        if (!LoyalityEvents::checkEnableEvent(LoyalityEvents::AUTH)) {
            return true;
        }
        $settings = SettingsFactory::create();

        $userGroupArray = \Bitrix\Main\UserTable::getUserGroupIds((int)$arUser['user_fields']['ID']);
        if (!LoyalityEvents::checkEnableEventsForUserGroup(LoyalityEvents::AUTH, $userGroupArray, $settings)) {
            return true;
        }

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

                $session->remove('mindbox_need_confirm_phone');

                $service->confirmMobilePhone($customer);
            }
        } catch (IntegrationLoyaltyException $exception) {

        }
    }

    public static function onAfterUserUpdate($arUser)
    {
        if (!LoyalityEvents::checkEnableEvent(LoyalityEvents::EDIT_USER)) {
            return true;
        }

        $settings = SettingsFactory::create();

        $userGroupArray = \Bitrix\Main\UserTable::getUserGroupIds((int) $arUser['ID']);
        if (!LoyalityEvents::checkEnableEventsForUserGroup(LoyalityEvents::EDIT_USER, $userGroupArray, $settings)) {
            return true;
        }

        try {
            $customer = new Customer((int) $arUser['ID']);
            $service = new CustomerService($settings);

            // Если при смене телефона происходит его подтверждение по СМС, не через МБ
            $session = \Bitrix\Main\Application::getInstance()->getSession();
            if ($session->has('mindbox_need_confirm_phone')) {
                $session->remove('mindbox_need_confirm_phone');
                $service->confirmMobilePhone($customer);
            }

            $subscriptionPoints = FeatureManager::getAutoSubscribePoints();
            $unsubscriptionPoints = FeatureManager::getUnsubscribePoints();

            $brand = $settings->getBrand();
            if ($brand) {
                foreach ($subscriptionPoints as $autoSubscribePoint) {
                    $customer->setSubscribe($brand, $autoSubscribePoint, true);
                }
                foreach ($unsubscriptionPoints as $autoSubscribePoint) {
                    $customer->setSubscribe($brand, $autoSubscribePoint, false);
                }
            }

            $service->edit($customer);
        } catch (IntegrationLoyaltyException $exception) {
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
        $userGroupArray = \Bitrix\Main\UserTable::getUserGroupIds($userId);
        if (!LoyalityEvents::checkEnableEventsForUserGroup(LoyalityEvents::CHECK_CHANGE_USER_EMAIL, $userGroupArray)) {
            return true;
        }

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

        $settings = SettingsFactory::create();

        $userId = (int) $arFields['ID'];
        $userGroupArray = \Bitrix\Main\UserTable::getUserGroupIds($userId);
        if (!LoyalityEvents::checkEnableEventsForUserGroup(LoyalityEvents::CHECK_CHANGE_USER_EMAIL, $userGroupArray, $settings)) {
            return true;
        }

        if (!EmailChangeChecker::getInstance()->check($arFields['EMAIL'])) {
            return;
        }

        $isSend = false;

        try {
            $userId = (int) $arFields['ID'];

            $customer = new Customer($userId);
            $service = new CustomerService($settings);

            $isSend = $service->confirmEmail($customer);
        } catch (IntegrationLoyaltyException $exception) {

        }

        if ($isSend) {
            \Bitrix\Main\Application::getInstance()->getSession()->set('mindbox_send_confirm_email_change', 'Y');
        }
    }

    public static function onUserLogout()
    {
        \Mindbox\Loyalty\Support\SessionStorage::getInstance()->clear();
    }

    public static function setUserLoginByEmail(&$arFields)
    {
        $settings = SettingsFactory::create();

        if ($settings->getLoginIsEmailEnabled()) {
            if ($arFields['EMAIL']) {
                $arFields['LOGIN'] = $arFields['EMAIL'];
            }
        }
    }
}