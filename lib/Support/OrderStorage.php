<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Support;

class OrderStorage
{
    private static string $ordersId = '';

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

    public static function add(string $id): void
    {
        self::$ordersId = $id;
    }

    public static function remove(): void
    {
        self::$ordersId = '';
    }

    public static function isNew(): bool
    {
        return self::$ordersId === '';
    }

    public static function exists(string $id): bool
    {
        return self::$ordersId === $id;
    }

    public function clear(): void
    {
        self::$ordersId = '';
    }
}