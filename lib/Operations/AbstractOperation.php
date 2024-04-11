<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Operations;

use Mindbox\Loyalty\Api;
use Mindbox\Loyalty\Support\Settings;
use Mindbox\Loyalty\Support\SettingsFactory;

abstract class AbstractOperation
{
    private ?Settings $settings = null;

    protected function api(): \Mindbox\Mindbox
    {
        return Api::getInstance()->getClient();
    }

    public function getOperation(): string
    {
        if ($this->customOperation()) {
            return $this->customOperation();
        }

        return $this->getSettings()->getWebsitePrefix() . '.' . $this->operation();
    }

    public static function make(): static
    {
        return new static();
    }

    protected function customOperation(): ?string
    {
        return $this->getSettings()->getCustomOperation($this->operation());
    }

    abstract protected function operation(): string;

    public function setSettings(Settings $settings): static
    {
        $this->settings = $settings;

        return $this;
    }

    public function getSettings(): Settings
    {
        if ($this->settings === null) {
            $this->settings = SettingsFactory::create();
        }

        return $this->settings;
    }
}