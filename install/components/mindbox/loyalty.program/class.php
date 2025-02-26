<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\ErrorableImplementation;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Mindbox\Loyalty\Models\Customer;
use Mindbox\Loyalty\Services\BonusService;
use Mindbox\Loyalty\Services\LoyaltyService;

Loc::loadMessages(__FILE__);

class LoyaltyProgram extends CBitrixComponent implements \Bitrix\Main\Engine\Contract\Controllerable
{
    use ErrorableImplementation;

    private static $moduleLoaded = [
        'mindbox.loyalty',
    ];

    private $userId;
    private $customerInfo;

    private static array $listInMonth = [];

    private const PAGE_SIZE_DEFAULT = 20;


    public function __construct($component = null)
    {
        global $USER;
        parent::__construct($component);
        self::setListInMonth();
        $this->userId = (int)$USER->getId();
    }

    public function configureActions()
    {
        return ['page' => ['prefilters' => []]];
    }

    public function executeComponent()
    {
        global $USER, $APPLICATION;

        if (!Loader::includeModule('mindbox.loyalty')) {
            ShowError('Module mindbox.loyalty not loaded');
            return true;
        }
        if (!$USER->IsAuthorized()) {
            $APPLICATION->AuthForm("", false, false, "N", false);
            return true;
        }

        $this->prepareParams();

        $this->setCustomerInfo();

        if ($this->arParams['HISTORY_ENABLE'] === 'Y') {
            $this->arResult['history'] = $this->getHistory();
        }
        $this->arResult['bonuses'] = $this->getBalance();
        if ($this->arParams['LOYALTY_ENABLE'] === 'Y') {
            $this->arResult['loyalty'] = $this->getLoyalty();
        }

        return $this->includeComponentTemplate();
    }

    private function getLoyalty(): array
    {
        return [
            'current_level' => $this->getCurrentLoyalty(),
            'next_level' => $this->getNextLevelInfo(),
            'purchases' => $this->getPurchases(),
        ];
    }

    private function getHistory(int $page = 1): array
    {
        $history = BonusService::getBonusHistory($this->userId, (int)$this->arParams['HISTORY_PAGE_SIZE'], $page);
        $result = [];

        foreach ($history as $item) {
            $result[] = [
                'start' => date($this->arParams['HISTORY_DATE_FORMAT'], strtotime($item['start'])),
                'name' => $item['name'],
                'end' => $item['end'],
                'is_positive' => (int)$item['size'] >= 0,
                'size' => $item['size'],
                'size_format' => $this->getFormatPrice((int)$item['size']),
            ];
        }
        return $result;
    }

    private function getCurrentLoyalty(): array
    {
        if (empty($this->arParams['SEGMENTS'])) {
            return [];
        }
        $result = [];
        $service = new LoyaltyService($this->arParams['SEGMENTS']);
        $result['name'] = $service->getCurrentSegmentLoyalty($this->userId);

        foreach ($this->arParams['LEVEL_NAMES_LOYALTY'] as $key => $item) {
            if ($result['name'] === $item) {
                $result['level'] = $key;
            }
        }
        if (!isset($result['level']) || $result['level'] < 0) {
            $result['level'] = count($this->arParams['LEVEL_NAMES_LOYALTY']);
        }
        return $result;
    }

    private function getBalance(): array
    {
        if (!$this->customerInfo) {
            return [];
        }

        $balanceFields = $this->customerInfo->getResult()->getBalances()?->getFieldsAsArray();
        $arBalance = is_array($balanceFields) ? reset($balanceFields) : [];
        return [
            'available' => $arBalance['available'],
            'available_format' => $this->getFormatPrice((int)$arBalance['available']),
            'blocked' => $arBalance['blocked'],
            'blocked_format' => $this->getFormatPrice((int)$arBalance['blocked']),
        ];
    }

    private function setCustomerInfo(): void
    {
        try {
            $customer = new Customer($this->userId);
            $this->customerInfo = \Mindbox\Loyalty\Operations\GetCustomerInfo::make()->execute($customer->getDto());
        } catch (\Mindbox\Loyalty\Exceptions\ErrorCallOperationException $e) {
        }
    }

    private function getPurchases(): array
    {
        if (!$this->customerInfo) {
            return [];
        }

        $totalPaidAmount = (int)floor($this->customerInfo->getResult()->getFieldsAsArray()['retailOrderStatistics']['totalPaidAmount']);
        return [
            'total' => $totalPaidAmount,
            'total_format' => $this->getFormatPrice($totalPaidAmount),
        ];
    }

    private function getNextLevelInfo(): array
    {
        if (empty($this->arParams['SEGMENTS'])) {
            return [];
        }
        if (!$this->customerInfo) {
            return [];
        }

        $pricePaid = (int)floor($this->customerInfo->getResult()->getFieldsAsArray()['retailOrderStatistics']['totalPaidAmount']);
        $result = [];
        $keyLevel = 0;
        foreach ($this->arParams['LEVEL_NAMES_LOYALTY'] as $key => $item) {
            if ($this->arResult['loyalty']['current_level']['name'] === $item) {
                $keyLevel = $key;
                break;
            }
        }
        if ($pricePaid >= end($this->arParams['LEVEL_PRICES_LOYALTY'])) {
            $result['total'] = 0;
            $result['total_format'] = $this->getFormatPrice(0);
            $result['name'] = '';
        } else {
            $result['total'] = $this->arParams['LEVEL_PRICES_LOYALTY'][$keyLevel + 1] - $pricePaid;
            $result['total_format'] = $this->getFormatPrice((int)($this->arParams['LEVEL_PRICES_LOYALTY'][$keyLevel + 1] - $pricePaid));
            $result['name'] = $this->arParams['LEVEL_NAMES_LOYALTY'][$keyLevel + 1];
        }

        $result['month'] = self::getNextMonth();

        return $result;
    }

    private function getFormatPrice(int $price)
    {
        Loader::includeModule('currency');
        return \CCurrencyLang::CurrencyFormat($price, $this->arParams['CURRENCY_ID']);
    }

    private function prepareParams(): void
    {
        if (!is_array($this->arParams['SEGMETS_LOYALTY']) || !is_array($this->arParams['LEVEL_NAMES_LOYALTY']) || !is_array($this->arParams['LEVEL_PRICES_LOYALTY'])) {
            $this->arParams['SEGMETS_LOYALTY'] = [];
            $this->arParams['LEVEL_NAMES_LOYALTY'] = [];
            $this->arParams['SEGMENTS'] = [];
        } else {
            $minCount = min(count($this->arParams['SEGMETS_LOYALTY']), count($this->arParams['LEVEL_NAMES_LOYALTY']), count($this->arParams['LEVEL_PRICES_LOYALTY']));

            $this->arParams['SEGMETS_LOYALTY'] = array_slice(array_filter($this->arParams['SEGMETS_LOYALTY'], fn($item) => $item !== ''), 0, $minCount);
            $this->arParams['LEVEL_NAMES_LOYALTY'] = array_slice(array_filter($this->arParams['LEVEL_NAMES_LOYALTY'], fn($item) => $item !== ''), 0, $minCount);
            $this->arParams['SEGMENTS'] = array_combine($this->arParams['SEGMETS_LOYALTY'], $this->arParams['LEVEL_NAMES_LOYALTY']);
        }
        $this->arParams['HISTORY_PAGE_SIZE'] = (int)$this->arParams['HISTORY_PAGE_SIZE'] > 0 ? (int)$this->arParams['HISTORY_PAGE_SIZE'] : self::PAGE_SIZE_DEFAULT;
    }

    public function pageAction($signedParameters, $page)
    {
        $page = (int)$page;
        $signer = new \Bitrix\Main\Component\ParameterSigner();
        $this->arParams = $signer->unsignParameters($this->getName(), $signedParameters);
        $size = $this->arParams['HISTORY_PAGE_SIZE'] ?? 20;

        try {
            $history = $this->getHistory($page);
            $showMore = count($history) !== 0;

            return [
                'type' => 'success',
                'page' => $page,
                'history' => $history,
                'more' => $showMore
            ];
        } catch (Exception $e) {
            return [
                'type' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }

    private static function getNextMonth(): string
    {
        return self::$listInMonth[date('n', strtotime('now +1 month'))];
    }

    private function setListInMonth()
    {
        self::$listInMonth = explode(',', \Bitrix\Main\Localization\Loc::getMessage('LIST_IN_MONTH'));
    }

    protected function listKeysSignedParameters(): array
    {
        return ['HISTORY_PAGE_SIZE', 'HISTORY_DATE_FORMAT'];
    }
}