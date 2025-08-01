<?php

declare(strict_types=1);

namespace Mindbox\Loyalty;

use Mindbox\Clients\AbstractMindboxClient;
use Mindbox\Clients\MindboxClientV3;
use Mindbox\Loyalty\Support\SettingsFactory;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;

class Api
{
    protected static ?Api $instance = null;
    protected AbstractMindboxClient $client;

    protected function __construct(string $siteId)
    {
        $settings = SettingsFactory::createBySiteId($siteId);

        if ($settings->isLoggingEnabled()) {
            $logger = new \Mindbox\Loggers\MindboxFileLogger(
                $settings->getLogPath(),
                LogLevel::DEBUG
            );
        } else {
            $logger = new NullLogger();
        }


        $apiDomainCustom = $settings->getApiDomainCustom();
        if ($apiDomainCustom && !empty($apiDomainCustomParts = Helper::parseDomainName($apiDomainCustom))) {
            $domain = $apiDomainCustomParts[0];
            $domainZone = $apiDomainCustomParts[1];
        } else {
            switch ($settings->getApiDomain()) {
                case 'maestro':
                    $domainZone = 'io';
                    $domain = 'api.maestra';
                    break;
                case 'api.s.mindbox':
                    $domainZone = 'ru';
                    $domain = 'api.s.mindbox';
                    break;
                default:
                    $domainZone = 'ru';
                    $domain = 'api.mindbox';
                    break;
            }
        }

        $this->client = new MindboxClientV3(
            endpointId: $settings->getEndpoint(),
            secretKey: $settings->getSecretKey(),
            httpClient: (new \Mindbox\HttpClients\HttpClientFactory())->createHttpClient($settings->getHttpTimeout(), $settings->getHttpClient()),
            logger: $logger,
            domainZone: $domainZone,
            domain: $domain
        );

        $this->client->addHeaders([
            'Mindbox-Integration' => 'PhpSDK-loyalty'
        ]);
    }

    public static function getInstance(string $siteId): static
    {
        return self::$instance === null ? self::$instance = new static($siteId) : self::$instance;
    }

    /**
     * @return AbstractMindboxClient
     */
    public function getClient(): AbstractMindboxClient
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