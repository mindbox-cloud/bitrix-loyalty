<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Support;

use Bitrix\Main\Application;
use Bitrix\Main\Session\Session;

class CallBlocking
{
    private const BLOCKING_TIME = 60;
    private const BLOCKING_KEY = 'PROCESSING_LOCK';

    private static ?CallBlocking $instance = null;
    private ?Session $session;

    protected function __construct()
    {
        $this->session = self::getSession();
    }

    public static function getInstance(): CallBlocking
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function lock(): void
    {
        if ($this->session === null) {
            return;
        }

        $this->session[self::BLOCKING_KEY] = time() + self::BLOCKING_TIME;
    }

    public function isLocked(): bool
    {
        if ($this->session === null) {
            return false;
        }

        if (!isset($this->session[self::BLOCKING_KEY])) {
            return false;
        }

        if ($this->session[self::BLOCKING_KEY] < time()) {
            $this->unlock();
            return false;
        }

        return true;
    }

    public function unlock(): void
    {
        if ($this->session === null) {
            return;
        }

        if (isset($this->session[self::BLOCKING_KEY])) {
            unset($this->session[self::BLOCKING_KEY]);
        }
    }

    /**
     * Session object.
     *
     * If session is not accessible, returns null.
     *
     * @return Session|null
     */
    protected static function getSession(): ?Session
    {
        /** @var Session $session */
        $session = Application::getInstance()->getSession();
        if (!$session->isAccessible()) {
            return null;
        }

        return $session;
    }
}