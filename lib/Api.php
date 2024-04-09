<?php

declare(strict_types=1);

namespace Mindbox\Loyalty;

use Mindbox\Loyalty\Support\Settings;
use Mindbox\Loyalty\Support\SettingsFactory;
use Mindbox\Mindbox;

class Api
{
    protected static ?Api $instance = null;
    protected Mindbox $client;

    protected function __construct()
    {
        $settings = SettingsFactory::create();

        $logger = new \Mindbox\Loggers\MindboxFileLogger(
            $settings->getLogPath()
        );

        $this->client = new Mindbox([
            'endpointId' => $settings->getEndpoint(),
            'secretKey' => $settings->getSecretKey(),
            'domainZone' => 'ru',
            'domain' => $settings->getApiDomain(),
            'timeout' => $settings->getHttpTimeout()
        ], $logger);
    }

    public static function getInstance(): static
    {
        return self::$instance === null ? self::$instance = new static() : self::$instance;
    }

    /**
     * @return Mindbox
     */
    public function getClient(): Mindbox
    {
        return $this->client;
    }

    protected function __clone()
    {
    }

    protected function __wakeup()
    {
    }
}