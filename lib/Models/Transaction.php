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

    public function get(Order $order)
    {
        $this->collectGarbage();

        if (!isset($this->storage[\WeakReference::create($order)])) {
            $this->storage[\WeakReference::create($order)] = self::GUID();

            TransactionTable::set($this->storage[\WeakReference::create($order)]);
        }

        return $this->storage[\WeakReference::create($order)];
    }

    public function has(Order $order): bool
    {
        $this->collectGarbage();

        return isset($this->storage[\WeakReference::create($order)]);
    }


    public function close(Order $order)
    {
        $this->collectGarbage();

        if (isset($this->storage[\WeakReference::create($order)])) {
            TransactionTable::unset($this->storage[\WeakReference::create($order)]);
            unset($this->storage[\WeakReference::create($order)]);
        }
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

    public static function GUID()
    {
        if (function_exists('com_create_guid') === true) {
            return strtolower(trim(\com_create_guid(), '{}'));
        }

        return strtolower(sprintf(
            '%04X%04X-%04X-%04X-%04X-%04X%04X%04X',
            mt_rand(0, 65535),
            mt_rand(0, 65535),
            mt_rand(0, 65535),
            mt_rand(16384, 20479),
            mt_rand(32768, 49151),
            mt_rand(0, 65535),
            mt_rand(0, 65535),
            mt_rand(0, 65535)
        ));
    }
}