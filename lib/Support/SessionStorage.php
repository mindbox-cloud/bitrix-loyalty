<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Support;

class SessionStorage
{
    private const GROUPS = 'MINDBOX';

    /** @var string - Количество списываемых бонусов за заказ */
    private const PAY_BONUSES = 'PAY_BONUSES';

    /** @var string - Финальная стоимость заказа в МБ */
    private const TOTAL_PRICE = 'TOTAL_PRICE';

    /** @var string Доступное количество бонусов для списания */
    private const ORDER_AVAILABLE_BONUSES = 'ORDER_AVAILABLE_BONUSES';

    /** @var string Общий бонусный баланс пользователя */
    private const BONUSES_BALANCE_AVAILABLE = 'BONUSES_BALANCE_AVAILABLE';

    /** @var string Количество бонусов начисляемые за заказ */
    private const ORDER_EARNED_BONUSES = 'ORDER_EARNED_BONUSES';
    private const PROMOCODE_VALUE = 'PROMOCODE_VALUE';
    private const PROMOCODE_ERROR = 'PROMOCODE_ERROR';
    private const MINDBOX_ORDER_ID = 'MINDBOX_ORDER_ID';

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
    public function setPayBonuses(float $value)
    {
        $_SESSION[self::GROUPS][self::PAY_BONUSES] = $value;
    }

    /**
     * Количество бонусов указываемые пользователем для списания
     *
     * @return float
     */
    public function getPayBonuses()
    {
        return (float) $_SESSION[self::GROUPS][self::PAY_BONUSES];
    }

    /**
     * @param float $value
     * @return void
     */
    public function setTotalPrice(float $value)
    {
        $_SESSION[self::GROUPS][self::TOTAL_PRICE] = $value;
    }

    public function getTotalPrice()
    {
        return (float) $_SESSION[self::GROUPS][self::TOTAL_PRICE];
    }

    /**
     * Установка доступного количества бонусов для списания
     * @param float $value
     * @return void
     */
    public function setOrderAvailableBonuses(float $value)
    {
        $_SESSION[self::GROUPS][self::ORDER_AVAILABLE_BONUSES] = $value;
    }

    /**
     * Доступные для списания бонусы
     *
     * @return float
     */
    public function getOrderAvailableBonuses()
    {
        return (float) $_SESSION[self::GROUPS][self::ORDER_AVAILABLE_BONUSES];
    }

    public function setBonusesBalanceAvailable(float $value)
    {
        $_SESSION[self::GROUPS][self::BONUSES_BALANCE_AVAILABLE] = $value;
    }

    /**
     * Общий бонусный баланс пользователя
     *
     * @return int|mixed
     */
    public function getBonusesBalanceAvailable()
    {
        return (float) $_SESSION[self::GROUPS][self::BONUSES_BALANCE_AVAILABLE];
    }

    /**
     * @param float $value
     * @return void
     */
    public function setOrderEarnedBonuses(float $value)
    {
        $_SESSION[self::GROUPS][self::ORDER_EARNED_BONUSES] = $value;
    }

    /**
     * Количество бонусов начисляемые за заказ
     *
     * @return float
     */
    public function getOrderEarnedBonuses()
    {
        return (float) $_SESSION[self::GROUPS][self::ORDER_EARNED_BONUSES] ?? 0;
    }

    /**
     * @param string $value
     * @return void
     */
    public function setPromocodeValue(string $value)
    {
        $_SESSION[self::GROUPS][self::PROMOCODE_VALUE] = $value;
    }

    /**
     * Применяемый промокод
     *
     * @return string
     */
    public function getPromocodeValue()
    {
        return $_SESSION[self::GROUPS][self::PROMOCODE_VALUE] ?? '';
    }

    /**
     * @param string $value
     * @return void
     */
    public function setPromocodeError(string $value)
    {
        $_SESSION[self::GROUPS][self::PROMOCODE_ERROR] = $value;
    }

    /**
     * Применяемый промокод
     *
     * @return string
     */
    public function getPromocodeError()
    {
        return $_SESSION[self::GROUPS][self::PROMOCODE_ERROR] ?? '';
    }

    /**
     * @param string $value
     * @return void
     */
    public function setMindboxOrderId(string $value)
    {
        $_SESSION[self::GROUPS][self::MINDBOX_ORDER_ID] = $value;
    }

    /**
     * ID заказа в МБ
     *
     * @return string|null
     */
    public function getMindboxOrderId()
    {
        return $_SESSION[self::GROUPS][self::MINDBOX_ORDER_ID];
    }

    public function clear()
    {
        $_SESSION[self::GROUPS] = [];
    }
}