<?php

use Bitrix\Main\Engine\ActionFilter\Csrf;
use Bitrix\Main\Engine\ActionFilter\HttpMethod;
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Errorable;
use Bitrix\Main\ErrorableImplementation;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\Date;
use Mindbox\DTO\V3\Responses\CustomerResponseDTO;
use Mindbox\Loyalty\Exceptions\ErrorCallOperationException;
use Mindbox\Loyalty\Models\Customer;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

class ProfileComplete extends CBitrixComponent implements Controllerable, Errorable
{
    use ErrorableImplementation;

    /**
     * Обязательные модули для загрузки
     *
     * @var string[]
     */
    protected static array $moduleLoaded = [
        'mindbox.loyalty'
    ];

    protected $actions = [
        'save'
    ];

    protected const PROFILE_KEYS = [
        'LAST_NAME',
        'NAME',
        'SECOND_NAME',
        'EMAIL',
        'PERSONAL_PHONE',
        'PERSONAL_GENDER',
        'PERSONAL_BIRTHDAY'
    ];

    public function __construct($component = null)
    {
        parent::__construct($component);
    }

    public function configureActions()
    {
        $actionConfig = [];
        foreach ($this->actions as $action) {
            $actionConfig[$action] = [
                'prefilters' => [
                    new Csrf(),
                    new HttpMethod([
                        HttpMethod::METHOD_POST
                    ])
                ]
            ];
        }

        return $actionConfig;
    }

    public function executeComponent()
    {
        if (!$this->checkModules()) {
            return;
        }

        global $USER, $APPLICATION;

        if (!$USER->IsAuthorized()) {
            ShowError(\Bitrix\Main\Localization\Loc::getMessage('PROFILE_ERROR_NO_AUTH'));
            return;
        }

        $this->arResult['USER_DATA'] = $this->getUserData();
        $APPLICATION->SetTitle(Loc::getMessage('PROFILE_TITLE'));

        $this->includeComponentTemplate();
    }

    public function saveAction()
    {
        $result = [
            'status' => 'error',
            'message' => ''
        ];
        $userData = array_filter($this->request->toArray(), function ($value, $key) {
            return in_array($key, static::PROFILE_KEYS);
        }, ARRAY_FILTER_USE_BOTH);

        global $USER;
        $cUser = new CUser();
        $res = $cUser->Update($USER->GetID(), $userData);

        if ($res) {
            $result['status'] = 'success';
            $result['message'] = Loc::getMessage('PROFILE_SUCCESS');
        } else {
            $result['message'] = $cUser->LAST_ERROR;
        }

        return $result;
    }

    protected function checkModules(): bool
    {
        foreach (self::$moduleLoaded as $module) {
            if (!Loader::includeModule($module)) {

                ShowError(sprintf('Module %s not loaded', $module));

                return false;
            }
        }

        return true;
    }

    protected function getUserData()
    {
        global $USER;
        $user = CUser::GetByID($USER->GetID())->GetNext();
        try {
            $settings = \Mindbox\Loyalty\Support\SettingsFactory::create();
            $serviceLocator = \Bitrix\Main\DI\ServiceLocator::getInstance();
            $customer = new Customer((int)$user['ID']);
            $operationCheckCustomer = $serviceLocator->get('mindboxLoyalty.getCustomer');
            $operationCheckCustomer->setSettings($settings);
            $customerData = $operationCheckCustomer->execute($customer->getShortenedDto());
            return $this->getProcessedCustomerData($customerData->getCustomer());
        } catch (ErrorCallOperationException $e) {
            //Если при получении данных получили ошибку, делаем редирект
            LocalRedirect($this->arParams['REDIRECT_PAGE']);
        }
    }

    protected function getProcessedCustomerData(CustomerResponseDTO $responseDTO)
    {
        $gender = match ($responseDTO->getSex()) {
            'male' => 'M',
            'female' => 'F',
            default => ''
        };

        return [
            'NAME' => $responseDTO->getFirstName(),
            'LAST_NAME' => $responseDTO->getLastName(),
            'SECOND_NAME' => $responseDTO->getMiddleName(),
            'EMAIL' => $responseDTO->getEmail(),
            'PERSONAL_PHONE' => $responseDTO->getMobilePhone(),
            'PERSONAL_GENDER' => $gender,
            'PERSONAL_BIRTHDAY' => $responseDTO->getBirthDate(),
        ];
    }
}