<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Support;

class EmailChangeChecker
{
    protected static ?EmailChangeChecker $instance =  null;

    private bool $isEmailChange = false;
    private ?string $email = null;
    public function __construct()
    {
    }

    public static function getInstance(): EmailChangeChecker
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function check(string $email): bool
    {
        return $this->isEmailChange = $this->email !== $email;
    }

    public function isEmailChange(): bool
    {
        return $this->isEmailChange;
    }
}
