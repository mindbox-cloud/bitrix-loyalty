<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Support;

class OrderStorage
{
    private static string $ordersId = '';

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
    public static function get(): string
    {
        return self::$ordersId;
    }
}