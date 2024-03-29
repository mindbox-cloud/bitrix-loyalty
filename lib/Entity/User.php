<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Entity;

use Bitrix\Main\Diag\Debug;
use Bitrix\Main\Diag\FileLogger;
use Bitrix\Main\Diag\SysLogger;
use Bitrix\Main\UserTable;
use Mindbox\Loyalty\Helper;
use Mindbox\Loyalty\Util\Logger;

class User
{
    protected $mindbox;

    public function __construct()
    {
        \Bitrix\Main\Loader::includeModule('mindbox.loyalty');

        $loggerDir = \COption::GetOptionString(
            module_id: 'mindbox.loyalty',
            name: \Mindbox\Loyalty\Settings\SettingsEnum::LOG_PATH,
            site: SITE_ID
        );
        $logger = new \Mindbox\Loggers\MindboxFileLogger($loggerDir);

        $endPoint = \COption::GetOptionString(
            module_id: 'mindbox.loyalty',
            name: \Mindbox\Loyalty\Settings\SettingsEnum::ENDPOINT,
            site: SITE_ID
        );
        $secret = \COption::GetOptionString(
            module_id: 'mindbox.loyalty',
            name: \Mindbox\Loyalty\Settings\SettingsEnum::SECRET_KEY,
            site: SITE_ID
        );


        $this->mindbox = new \Mindbox\Mindbox([
            'endpointId' => $endPoint,
            'secretKey' => $secret,
            'domainZone' => 'ru',
            'domain' => 'api.mindbox.ru'
        ], $logger);
    }

    /**
     * Отправка пользоватлея в mindbox
     * @param array $userFields
     * @return void
     * @throws \Bitrix\Main\LoaderException
     */
    public function add(array $userFields)
    {
        $clientInfoQuery = $this->prepareUserFields($userFields);

        if ($this->checkExist($clientInfoQuery['email'], $clientInfoQuery['mobilePhone'])) {
            return;
        }

        $customer = new \Mindbox\DTO\V3\Requests\CustomerRequestDTO($clientInfoQuery);

        $prefix = \COption::GetOptionString(
            module_id: 'mindbox.loyalty',
            name: \Mindbox\Loyalty\Settings\SettingsEnum::WEBSITE_PREFIX,
            site: SITE_ID
        );

        try {
            $response = $this->mindbox->customer()
                ->register($customer, $prefix . '.' . 'RegisterCustomer')
                ->sendRequest();

            Debug::dumpToFile([
                'user_register' => [
                    'result' => $response->getResult(),
                    'code' => $response->getHttpCode(),
                    'body' => $response->getBody(),
                    'headers' => $response->getHeaders(),
                ]
            ]);
        } catch (\Mindbox\Exceptions\MindboxClientException $e) {
            Debug::dumpToFile(['egister_user_error' => $e->getMessage()]);
            return;
        }
    }

    /**
     * Данные для отправки
     * @param array $userFields
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    protected function prepareUserFields(array $userFields)
    {
        $gender = 0;

        if ($userFields['PERSONAL_GENDER'] === 'M') {
            $gender = 'male';
        } elseif ($userFields['PERSONAL_GENDER'] === 'F') {
            $gender = 'female';
        }
        $arUserData = [
            'email' => $userFields['EMAIL'],
            'lastName' => $userFields['LAST_NAME'],
            'middleName' => $userFields['SECOND_NAME'],
            'firstName' => $userFields['NAME'],
            'mobilePhone' => Helper::formatPhone($userFields['PHONE_NUMBER']),
            'birthDate' => self::getUserBirthDay((int)$userFields['ID']),
            'sex' => $gender,
        ];

        $arUserData = array_filter($arUserData);

        return $arUserData;
    }

    /**
     * Метод возвращает дату рождения пользователя в необходимом формате
     * @param int $userId
     * @return \DateTime|void
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function getUserBirthDay(int $userId)
    {
        $findUser = UserTable::getList([
            'filter' => ['=ID' => $userId, '!PERSONAL_BIRTHDAY' => false],
            'select' => ['PERSONAL_BIRTHDAY'],
            'limit' => 1
        ])->fetch();

        if ($findUser) {
            return new \DateTime($findUser['PERSONAL_BIRTHDAY']->toString());
        }
    }

    /**
     * Проверка наличия пользователя
     * @param string $email
     * @param string $mobilePhone
     * @return bool
     * @throws \Bitrix\Main\LoaderException
     */
    protected function checkExist(string $email, string $mobilePhone)
    {
        $clientInfoQuery = [
            'email' => $email,
            'mobilePhone' => $mobilePhone
        ];

        $customer = new \Mindbox\DTO\V3\Requests\CustomerRequestDTO($clientInfoQuery);

        $prefix = \COption::GetOptionString(
            module_id: 'mindbox.loyalty',
            name: \Mindbox\Loyalty\Settings\SettingsEnum::WEBSITE_PREFIX,
            site: SITE_ID
        );

        try {
            $response = $this->mindbox->customer()
                ->register($customer, $prefix . '.' . 'CheckCustomer')
                ->sendRequest();
            if ($response->getResult()->getField('customer')['processingStatus'] === 'Found') {
                return true;
            } else {
                return false;
            }
        } catch (\Mindbox\Exceptions\MindboxClientException $e) {
            Logger::channel('mindbox')->error($e->getMessage(), $clientInfoQuery);
            return false;
        }
    }
}