<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Services;

use Bitrix\Main\Localization\Loc;
use Mindbox\DTO\V3\Requests\CustomerRequestDTO;
use Mindbox\DTO\V3\Requests\PageRequestDTO;
use Mindbox\Loyalty\Exceptions\ErrorCallOperationException;
use Mindbox\Loyalty\Models\Customer;
use Mindbox\Loyalty\Operations\GetCustomerBalanceHistory;
use Mindbox\Loyalty\Support\SettingsFactory;

final class BonusService
{
    public static function getBonusHistory(int $userId, int $pageSize, int $page): array
    {
        $customer = new Customer($userId);

        $customerDTO = new CustomerRequestDTO([
            'ids' => $customer->getIds()
        ]);

        $pageDTO = new PageRequestDTO();
        $pageDTO->setItemsPerPage($pageSize);
        $pageDTO->setPageNumber($page);

        /** @var GetCustomerBalanceHistory $operation */
        $operation = \Bitrix\Main\DI\ServiceLocator::getInstance()->get('mindboxLoyalty.getCustomerBalanceHistory');

        try {
            $response = $operation->execute($customerDTO, $pageDTO);
        } catch (ErrorCallOperationException $e) {
            return [];
        }

        $result = $response->getResult();

        if ($result->getStatus() !== 'Success') {
            return [];
        }

        if (!$result->getCustomerActions()) {
            return [];
        }

        $settings = SettingsFactory::create();
        $balanceSystemName = $settings->getBalanceSystemName();

        $bitrixOrderIdKey = $settings->getExternalOrderId();
        if ($bitrixOrderIdKey) {
            $bitrixOrderIdKey = lcfirst($bitrixOrderIdKey) . 'Id';
        }

        $history = [];
        foreach ($result->getCustomerActions() as $action) {
            foreach ($action->getCustomerBalanceChanges() as $customerBalanceChanges) {
                if (!$customerBalanceChanges->getField('isAvailable')) {
                    continue;
                }

                if (!empty($balanceSystemName) && $customerBalanceChanges->getField('balanceType')['name'] !== $balanceSystemName) {
                    continue;
                }

                $comment = $customerBalanceChanges->getField('comment');
                $isPositive = (int)$customerBalanceChanges->getField('changeAmount') > 0;
                if (empty($comment)) {
                    $type = $customerBalanceChanges->getField('balanceChangeKind')->getField('systemName');
                    $orderData = $action->getOrder();

                    $comment = '';
                    $orderId = false;

                    if ($bitrixOrderIdKey && !empty($orderData) && is_object($orderData)) {
                        $orderIds = $orderData->getField('ids');

                        if (array_key_exists($bitrixOrderIdKey, $orderIds)) {
                            $orderId = str_replace('test-', '', $orderIds[$bitrixOrderIdKey]);
                        }
                    }

                    if ($type === 'RetailOrderBonus') {
                        if ($isPositive) {
                            $comment = Loc::getMessage('MINDBOX_LOYALTY_BONUS_ADD_COMMENT');
                        } else {
                            $comment = Loc::getMessage('MINDBOX_LOYALTY_BONUS_OFF_COMMENT');
                        }
                    } elseif ($type === 'RetailOrderPayment') {
                        if ($isPositive) {
                            $comment = Loc::getMessage('MINDBOX_LOYALTY_BONUS_PAY_COMMENT');
                        } else {
                            $comment = Loc::getMessage('MINDBOX_LOYALTY_BONUS_OFF_COMMENT');
                        }
                    }

                    if (!empty($comment) && $orderId) {
                        $comment .= Loc::getMessage('MINDBOX_LOYALTY_BONUS_ORDER_LABEL') . $orderId;
                    }
                }
                $start = '';
                if ($action->getDateTimeUtc()) {
                    $start = $action->getDateTimeUtc();
                } elseif ($customerBalanceChanges->getField('availableFromDateTimeUtc')) {
                    $start = $customerBalanceChanges->getField('availableFromDateTimeUtc');
                }
                $history[] = [
                    'start' => $start,
                    'size' => $customerBalanceChanges->getChangeAmount(),
                    'name' => $comment,
                    'end' => $isPositive ? $customerBalanceChanges->getExpirationDateTimeUtc() : ''
                ];
            }
        }

        return $history;
    }
}