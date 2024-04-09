<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Operations;

use Mindbox\Loyalty\Api;
use Mindbox\Loyalty\Support\Settings;
use Mindbox\Loyalty\Support\SettingsFactory;

abstract class AbstractOperation implements OperationInterface
{
    protected function api(): \Mindbox\Mindbox
    {
        return Api::getInstance()->getClient();
    }

    protected function getOperation(): string
    {
        if ($this->customOperation()) {
            return $this->customOperation();
        }

        $settings = SettingsFactory::create();

        return $settings->getWebsitePrefix() . '.' . $this->operation();
    }

    public static function make(): static
    {
        return new static();
    }

    protected function customOperation(): mixed
    {
        return null;
    }
}