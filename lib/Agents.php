<?php

declare(strict_types=1);

namespace Mindbox\Loyalty;

use Mindbox\Loyalty\ORM\TransactionTable;
use Mindbox\Loyalty\Services\OrderService;

class Agents
{
    public static function sendQueueOperation()
    {
        \Mindbox\Loyalty\ORM\QueueTable::execute();

        return '\\' . __METHOD__ . '();';
    }

    public static function cancelBrokenOrder()
    {
        $iterator = TransactionTable::getList([
            'filter' => ['ORDER_ID' => null],
            'select' => ['*'],
            'order' => ['ID' => 'ASC']
        ]);

        $service = new OrderService();
        while ($row = $iterator->fetch()) {
            $result = $service->cancelBrokenOrder($row['TEMP_ORDER_ID'], $row['SITE_ID']);

            if ($result) {
                TransactionTable::delete($row['ID']);
            }
        }

        return '\\' . __METHOD__ . '();';
    }
}