<?php

declare(strict_types=1);

namespace Mindbox\Loyalty;

use Mindbox\Loyalty\Support\Settings;
use Mindbox\Mindbox;

class Api
{
    protected static ?Api $instance = null;
    protected Mindbox $client;

    protected function __construct()
    {
        $logger = new \Mindbox\Loggers\MindboxFileLogger(
            Settings::getInstance()->getLogPath()
        );

        $this->client = new Mindbox([
            'endpointId' => Settings::getInstance()->getEndpoint(),
            'secretKey' => Settings::getInstance()->getSecretKey(),
            'domainZone' => 'ru',
            'domain' => Settings::getInstance()->getApiDomain(),
            'timeout' => Settings::getInstance()->getHttpTimeout()
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