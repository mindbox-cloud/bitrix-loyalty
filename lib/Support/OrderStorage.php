<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Support;

class OrderStorage
{
    private static int $ordersId = -1;

    /**
     * @var OrderStorage|null
     */
    protected static $instance = null;

    protected function __construct()
    {
    }

    protected function __clone()
    {
    }

    protected function __wakeup()
    {
    }

    /**
     * @return static|null
     */
    public static function getInstance()
    {
        return self::$instance === null ? self::$instance = new static() : self::$instance;
    }

    public static function add(int $id): void
    {
        self::$ordersId = $id;
    }

    public static function remove(): void
    {
        self::$ordersId = 0;
    }

    public static function isNew(int $id): bool
    {
        return self::$ordersId === 0 || self::$ordersId !== $id;
    }

    public static function exists(int $id): bool
    {
        return self::$ordersId === $id;
    }

    public function clear(): void
    {
        self::$ordersId = 0;
    }
}