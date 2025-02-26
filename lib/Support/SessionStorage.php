<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Support;

class SessionStorage
{
    private const GROUPS = 'MINDBOX';

    /** @var string - Количество списываемых бонусов за заказ */
    public const PAY_BONUSES = 'PAY_BONUSES';

    /** @var string - Финальная стоимость заказа в МБ */
    public const TOTAL_PRICE = 'TOTAL_PRICE';

    /** @var string Доступное количество бонусов для списания */
    public const ORDER_AVAILABLE_BONUSES = 'ORDER_AVAILABLE_BONUSES';

    /** @var string Общий бонусный баланс пользователя */
    public const BONUSES_BALANCE_AVAILABLE = 'BONUSES_BALANCE_AVAILABLE';

    /** @var string Количество бонусов начисляемые за заказ */
    public const ORDER_EARNED_BONUSES = 'ORDER_EARNED_BONUSES';

    public const PROMOCODE = 'PROMOCODE';

    public const PROMOCODE_VALUE = 'PROMOCODE_VALUE';
    public const PROMOCODE_ERROR = 'PROMOCODE_ERROR';
    public const MINDBOX_ORDER_ID = 'MINDBOX_ORDER_ID';
    public const OPERATION_TYPE = 'OPERATION_TYPE';

    /**
     * @var SessionStorage|null
     */
    protected static $instance = null;

    protected function __construct()
    {
        if (!isset($_SESSION[self::GROUPS])) {
            $this->clear();
        }
    }

    protected function __clone() {}
    protected function __wakeup() {}

    /**
     * @return static|null
     */
    public static function getInstance() {
        return self::$instance === null ? self::$instance = new static() : self::$instance;
    }

    /**
     * Устанавливает количество списываемых бонусов за заказ
     *
     * @param float $value
     * @return void
     */
    public function setPayBonuses(float $value): void
    {
        $_SESSION[self::GROUPS][self::PAY_BONUSES] = $value;
    }

    /**
     * Количество бонусов указываемые пользователем для списания
     *
     * @return float
     */
    public function getPayBonuses(): float
    {
        return (float) $_SESSION[self::GROUPS][self::PAY_BONUSES];
    }

    /**
     * @param float $value
     * @return void
     */
    public function setTotalPrice(float $value): void
    {
        $_SESSION[self::GROUPS][self::TOTAL_PRICE] = $value;
    }

    public function getTotalPrice(): float
    {
        return (float) $_SESSION[self::GROUPS][self::TOTAL_PRICE];
    }

    /**
     * Установка доступного количества бонусов для списания
     * @param float $value
     * @return void
     */
    public function setOrderAvailableBonuses(float $value): void
    {
        $_SESSION[self::GROUPS][self::ORDER_AVAILABLE_BONUSES] = $value;
    }

    /**
     * Доступные для списания бонусы
     *
     * @return float
     */
    public function getOrderAvailableBonuses(): float
    {
        return (float) $_SESSION[self::GROUPS][self::ORDER_AVAILABLE_BONUSES];
    }

    public function setBonusesBalanceAvailable(float $value): void
    {
        $_SESSION[self::GROUPS][self::BONUSES_BALANCE_AVAILABLE] = $value;
    }

    /**
     * Общий бонусный баланс пользователя
     *
     * @return int|mixed
     */
    public function getBonusesBalanceAvailable(): mixed
    {
        return (float) $_SESSION[self::GROUPS][self::BONUSES_BALANCE_AVAILABLE];
    }

    /**
     * @param float $value
     * @return void
     */
    public function setOrderEarnedBonuses(float $value): void
    {
        $_SESSION[self::GROUPS][self::ORDER_EARNED_BONUSES] = $value;
    }

    /**
     * Количество бонусов начисляемые за заказ
     *
     * @return float|int
     */
    public function getOrderEarnedBonuses(): float|int
    {
        return (float) $_SESSION[self::GROUPS][self::ORDER_EARNED_BONUSES] ?? 0;
    }

    public function setPromocode(string $promocode): void
    {
        if (!isset( $_SESSION[self::GROUPS][self::PROMOCODE])) {
            $_SESSION[self::GROUPS][self::PROMOCODE] = [];
        }

        $_SESSION[self::GROUPS][self::PROMOCODE][$promocode] = [
            'value' => $promocode,
            'apply' => false,
            'error' => ''
        ];
    }

    public function setPromocodeData(string $promocode, array $data): void
    {
        if (!isset($_SESSION[self::GROUPS][self::PROMOCODE])) {
            return;
        }

        if (!isset($_SESSION[self::GROUPS][self::PROMOCODE][$promocode])) {
            return;
        }

        foreach (['apply', 'error'] as $key) {
            if (isset($data[$key])) {
                $_SESSION[self::GROUPS][self::PROMOCODE][$promocode][$key] = $data[$key];
            }
        }
    }

    public function unsetPromocode(string $promocode): void
    {
        if (!isset($_SESSION[self::GROUPS][self::PROMOCODE])) {
            return;
        }

        if (!isset($_SESSION[self::GROUPS][self::PROMOCODE][$promocode])) {
            return;
        }

        unset($_SESSION[self::GROUPS][self::PROMOCODE][$promocode]);
    }

    public function getPromocode(): array
    {
        return (array) $_SESSION[self::GROUPS][self::PROMOCODE];
    }

    /**
     * @param string $value
     * @return void
     */
    public function setPromocodeValue(string $value): void
    {
        $_SESSION[self::GROUPS][self::PROMOCODE_VALUE] = $value;
    }

    /**
     * Применяемый промокод
     *
     * @return string
     */
    public function getPromocodeValue(): string
    {
        return $_SESSION[self::GROUPS][self::PROMOCODE_VALUE] ?? '';
    }

    /**
     * @param string $value
     * @return void
     */
    public function setPromocodeError(string $value): void
    {
        $_SESSION[self::GROUPS][self::PROMOCODE_ERROR] = $value;
    }

    /**
     * Применяемый промокод
     *
     * @return string
     */
    public function getPromocodeError(): string
    {
        return $_SESSION[self::GROUPS][self::PROMOCODE_ERROR] ?? '';
    }

    /**
     * @param string $value
     * @return void
     */
    public function setMindboxOrderId(string $value): void
    {
        $_SESSION[self::GROUPS][self::MINDBOX_ORDER_ID] = $value;
    }

    /**
     * ID заказа в МБ
     *
     * @return string|null
     */
    public function getMindboxOrderId(): ?string
    {
        return $_SESSION[self::GROUPS][self::MINDBOX_ORDER_ID];
    }

    /**
     * @param string $value
     * @return void
     */
    public function setOperationType(string $value): void
    {
        $_SESSION[self::GROUPS][self::OPERATION_TYPE] = $value;
    }

    /**
     * Тип операции
     *
     * @return string|null
     */
    public function getOperationType(): ?string
    {
        return $_SESSION[self::GROUPS][self::OPERATION_TYPE];
    }

    public function clearField(string $fieldName): void
    {
        if (isset($_SESSION[self::GROUPS][$fieldName])) {
            unset($_SESSION[self::GROUPS][$fieldName]);
        }
    }

    public function clear(): void
    {
        $_SESSION[self::GROUPS] = [];
    }

    public function isBonusesUsed(): bool
    {
        return isset($_SESSION[self::GROUPS][self::PAY_BONUSES]);
    }

    public function isPromocodeUsed(): bool
    {
        return isset($_SESSION[self::GROUPS][self::PROMOCODE_VALUE]);
    }
}