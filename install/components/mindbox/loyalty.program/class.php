<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\ErrorableImplementation;
use Bitrix\Main\Loader;
use Mindbox\Loyalty\Models\Customer;
use Mindbox\Loyalty\Services\BonusService;
use Mindbox\Loyalty\Services\LoyaltyService;

class LoyaltyProgramm extends CBitrixComponent implements \Bitrix\Main\Engine\Contract\Controllerable
{
    use ErrorableImplementation;

    private static $moduleLoaded = [
        'mindbox.loyalty',
    ];

    private $userId;
    private $customerInfo;

    private static array $listInMonth = [
        'Январе', 'Феврале', 'Марте', 'Апреле', 'Мае', 'Июне', 'Июле', 'Августе', 'Сентябре', 'Октябре', 'Ноябре', 'Декабре'
    ];

    private const PAGE_SIZE_DEFAULT = 20;


    public function __construct($component = null)
    {
        global $USER;
        parent::__construct($component);
        $this->userId = (int)$USER->getId();
    }

    public function configureActions()
    {
        return ['page' => ['prefilters' => []]];
    }

    public function executeComponent()
    {
        global $USER;

        if (!Loader::includeModule('mindbox.loyalty')) {
            ShowError('Module mindbox.loyalty not loaded');
            return true;
        }
        if (!$USER->IsAuthorized()) {
            ShowError('Необходимо авторизоваться');
            return true;
        }

        $this->prepareParams();

        $this->setCustomerInfo();

        $this->arResult['history'] = $this->getHistory();
        $this->arResult['bonuses'] = $this->getBalance();
        $this->arResult['loyalty'] = $this->getLoyalty();

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
                'name' => $item['name'],
                "sum" => $item['size'],
                "sum_format" => $this->getFormatPrice((int)$item['size']),
                "date" => date($this->arParams['HISTORY_DATE_FORMAT'], strtotime($item['start'])),
                "text" => $item['name'],
                'is_positive' => (int)$item['size'] >= 0,
                'size_format' => $this->getFormatPrice((int)$item['size']),
                'size' => $item['size'],
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
        $arBalance = $this->customerInfo->getResult()->getBalances()?->getFieldsAsArray()[0];
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

        $result['month'] = self::$listInMonth[date('n', strtotime('now +1 month'))];

        return $result;
    }

    private function getFormatPrice(int $price)
    {
        return \CCurrencyLang::CurrencyFormat($price, $this->arParams['CURRENCY_ID']);
    }

    private function prepareParams(): void
    {
        $maxCount = min(count($this->arParams['SEGMETS_LOYALTY']), count($this->arParams['LEVEL_NAMES_LOYALTY']), count($this->arParams['LEVEL_PRICES_LOYALTY']));

        $this->arParams['SEGMETS_LOYALTY'] = array_slice(array_filter($this->arParams['SEGMETS_LOYALTY'], fn($item) => $item !== ''), 0, $maxCount);
        $this->arParams['~SEGMETS_LOYALTY'] = array_slice(array_filter($this->arParams['~SEGMETS_LOYALTY'], fn($item) => $item !== ''), 0, $maxCount);
        $this->arParams['LEVEL_NAMES_LOYALTY'] = array_slice(array_filter($this->arParams['LEVEL_NAMES_LOYALTY'], fn($item) => $item !== ''), 0, $maxCount);
        $this->arParams['~LEVEL_NAMES_LOYALTY'] = array_slice(array_filter($this->arParams['~LEVEL_NAMES_LOYALTY'], fn($item) => $item !== ''), 0, $maxCount);
        $this->arParams['SEGMENTS'] = array_combine($this->arParams['SEGMETS_LOYALTY'], $this->arParams['LEVEL_NAMES_LOYALTY']);
        $this->arParams['HISTORY_PAGE_SIZE'] = (int)$this->arParams['HISTORY_PAGE_SIZE'] > 0 ? (int)$this->arParams['HISTORY_PAGE_SIZE'] : self::PAGE_SIZE_DEFAULT;
    }

    public function pageAction($page)
    {
        $page = (int)$page;
        $size = $this->arParams['HISTORY_PAGE_SIZE'] ?? 20;

        try {
            $history = $this->getHistory($page);
            $showMore = count($history) === intval($size);

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
}