<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Models;

use Bitrix\Sale\Order;
use Mindbox\Loyalty\ORM\TransactionTable;

class Transaction
{
    protected static $instance = null;

    private \SplObjectStorage $storage;

    protected function __construct()
    {
        $this->storage = new \SplObjectStorage();
    }

    protected function __clone() {}
    protected function __wakeup() {}

    public static function getInstance() {
        return self::$instance === null ? self::$instance = new static() : self::$instance;
    }

    public function get(Order $order): string
    {
        $this->collectGarbage();

        if (!isset($this->storage[\WeakReference::create($order)])) {
            $uniq = \Bitrix\Main\Security\Random::getString(32) . $order->getUserId() . $order->getSiteId();

            $this->storage[\WeakReference::create($order)] = $uniq;

            TransactionTable::setTmpId($this->storage[\WeakReference::create($order)], $order->getSiteId());
        }

        return $this->storage[\WeakReference::create($order)];
    }

    public function has(Order $order): bool
    {
        $this->collectGarbage();

        return isset($this->storage[\WeakReference::create($order)]);
    }


    public function close(Order $order): void
    {
        if ($this->has($order)) {
            TransactionTable::unset($this->get($order));

            unset($this->storage[\WeakReference::create($order)]);
        }
    }

    public function save(Order $order): void
    {
        $tmpOrderId = $this->get($order);
        $orderId = (int) $order->getId();

        TransactionTable::setOrderId($tmpOrderId, $orderId);
    }

    private function collectGarbage(): void
    {
        /** @var \WeakReference $weakReferenceItem */
        foreach ($this->storage as $weakReferenceItem) {
            if ($weakReferenceItem->get() === null) {
                $this->storage->detach($weakReferenceItem);
            }
        }
    }
}