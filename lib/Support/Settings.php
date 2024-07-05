<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Support;

final class Settings
{
    /** @var Settings[] */
    protected static $instance = [];
    protected string $siteId;

    protected array $settings = [
        SettingsEnum::ENABLED_LOYALTY => null,
        SettingsEnum::TEST_MODE => null,
        SettingsEnum::ENDPOINT => null,
        SettingsEnum::SECRET_KEY => null,
        SettingsEnum::WEBSITE_PREFIX => null,
        SettingsEnum::BRAND => null,
        SettingsEnum::EXTERNAL_PRODUCT => null,
        SettingsEnum::EXTERNAL_USER => null,
        SettingsEnum::TEMP_EXTERNAL_ORDER => null,
        SettingsEnum::EXTERNAL_ORDER => null,
        SettingsEnum::BALANCE_SYSTEM_NAME => null,
        SettingsEnum::API_DOMAIN => null,
        SettingsEnum::HTTP_CLIENT => null,
        SettingsEnum::TIMEOUT => null,
        SettingsEnum::IS_LOGGING => null,
        SettingsEnum::LOG_PATH => null,
        SettingsEnum::LOG_LIFE_TIME => null,
        SettingsEnum::DISABLE_PROCESSING_USER_GROUPS => null,
        SettingsEnum::USER_BITRIX_FIELDS => null,
        SettingsEnum::USER_MINDBOX_FIELDS => null,
        SettingsEnum::USER_FIELDS_MATCH => null,
        SettingsEnum::USER_AUTO_SUBSCRIBE_POINTS => null,
        SettingsEnum::USER_LOGIN_IS_EMAIL => null,
        SettingsEnum::ORDER_STATUS_MATCH => null,
        SettingsEnum::LOYALTY_ENABLE_EVENTS => null,
        SettingsEnum::YML_FEED_ENABLED => null,
        SettingsEnum::YML_CATALOG_IBLOCK_ID => null,
        SettingsEnum::YML_BASE_PRICE_ID => null,
        SettingsEnum::YML_CATALOG_PROPERTIES => null,
        SettingsEnum::YML_OFFERS_PROPERTIES => null,
        SettingsEnum::YML_PROTOCOL => null,
        SettingsEnum::YML_PATH => null,
        SettingsEnum::YML_CHUNK_SIZE => null,
        SettingsEnum::YML_SERVER_NAME => null,
    ];

    protected function __construct(string $siteId)
    {
        $this->siteId = $siteId;

        $this->fillDefaultOperationsSettings();

        foreach ($this->settings as $settingCode => $value) {
            $this->settings[$settingCode] = \Bitrix\Main\Config\Option::get(
                moduleId: $this->getModuleId(),
                name: $settingCode,
                siteId: $this->siteId
            );
        }
    }

    protected function fillDefaultOperationsSettings(): void
    {
        foreach (DefaultOperations::getMap() as $defaultOperationName) {
            $this->settings[$defaultOperationName] = null;
        }
    }

    public function getModuleId(): string
    {
        return 'mindbox.loyalty';
    }

    public function enabledLoyalty(): bool
    {
        return $this->settings[SettingsEnum::ENABLED_LOYALTY] === 'Y';
    }

    public function isTestMode(): bool
    {
        return $this->settings[SettingsEnum::TEST_MODE] === 'Y';
    }

    public function getEndpoint(): ?string
    {
        return $this->settings[SettingsEnum::ENDPOINT];
    }

    public function getSecretKey(): ?string
    {
        return $this->settings[SettingsEnum::SECRET_KEY];
    }

    public function getWebsitePrefix(): ?string
    {
        return $this->settings[SettingsEnum::WEBSITE_PREFIX];
    }

    public function getBrand(): ?string
    {
        return $this->settings[SettingsEnum::BRAND];
    }

    public function getApiDomain(): ?string
    {
        return $this->settings[SettingsEnum::API_DOMAIN];
    }

    public function getHttpClient(): ?string
    {
        return $this->settings[SettingsEnum::HTTP_CLIENT];
    }

    public function getHttpTimeout(): ?int
    {
        return (int) $this->settings[SettingsEnum::TIMEOUT];
    }

    public function getExternalProductId(): ?string
    {
        return $this->settings[SettingsEnum::EXTERNAL_PRODUCT];
    }

    public function getExternalUserId(): ?string
    {
        return $this->settings[SettingsEnum::EXTERNAL_USER];
    }

    public function getTmpOrderId(): ?string
    {
        return $this->settings[SettingsEnum::TEMP_EXTERNAL_ORDER];
    }

    public function getBalanceSystemName(): ?string
    {
        return $this->settings[SettingsEnum::BALANCE_SYSTEM_NAME];
    }

    public function getExternalOrderId(): ?string
    {
        return $this->settings[SettingsEnum::EXTERNAL_ORDER];
    }

    /**
     * @return array<string, string>
     */
    public function getUserFieldsMatch(): array
    {
        $result = [];

        $fields = $this->settings[SettingsEnum::USER_FIELDS_MATCH];

        if (!empty($fields)) {
            $decode = \json_decode($fields, true);

            if (\json_last_error() === \JSON_ERROR_NONE) {
                $result = $decode;
            }
        }

        return $result;
    }

    public function getAutoSubscribePoints(): array
    {
        return $this->getArrayOptionValue(SettingsEnum::USER_AUTO_SUBSCRIBE_POINTS) ?? [];
    }

    public function getOrderFieldsMatch(): array
    {
        $result = [];

        $fields = $this->settings[SettingsEnum::ORDER_FIELDS_MATCH];

        if (!empty($fields)) {
            $decode = \json_decode($fields, true);

            if (\json_last_error() === \JSON_ERROR_NONE) {
                $result = $decode;
            }
        }

        return $result;
    }

    public function getOrderStatusFieldsMatch(): array
    {
        $result = [];

        $fields = $this->settings[SettingsEnum::ORDER_STATUS_MATCH];

        if (!empty($fields)) {
            $decode = \json_decode($fields, true);

            if (\json_last_error() === \JSON_ERROR_NONE) {
                $result = $decode;
            }
        }

        return $result;
    }

    public function getEnableEvents(): array
    {
        return $this->getArrayOptionValue(SettingsEnum::LOYALTY_ENABLE_EVENTS) ?? [];
    }

    public function getLogPath(): ?string
    {
        return $this->settings[SettingsEnum::LOG_PATH];
    }

    public function getBasePriceId(): ?string
    {
        return $this->settings[SettingsEnum::YML_BASE_PRICE_ID];
    }

    public function enabledFeed(): bool
    {
        return $this->settings[SettingsEnum::YML_FEED_ENABLED] === 'Y';
    }

    public function getFeedCatalogId(): ?int
    {
        return $this->settings[SettingsEnum::YML_CATALOG_IBLOCK_ID]
            ? (int)$this->settings[SettingsEnum::YML_CATALOG_IBLOCK_ID]
            : null;
    }

    public function getFeedBasePriceId(): ?int
    {
        return $this->settings[SettingsEnum::YML_BASE_PRICE_ID]
            ? (int)$this->settings[SettingsEnum::YML_BASE_PRICE_ID]
            : null;
    }

    public function getFeedCatalogProperties(): ?array
    {
        return $this->getArrayOptionValue(SettingsEnum::YML_CATALOG_PROPERTIES);
    }

    public function getFeedOffersProperties(): ?array
    {
        return $this->getArrayOptionValue(SettingsEnum::YML_OFFERS_PROPERTIES);
    }

    public function isFeedHttps(): bool
    {
        return $this->settings[SettingsEnum::YML_PROTOCOL] === 'Y';
    }

    public function getFeedPath(): ?string
    {
        return $this->settings[SettingsEnum::YML_PATH];
    }

    public function getFeedChunkSize(): ?int
    {
        return $this->settings[SettingsEnum::YML_CHUNK_SIZE]
            ? (int)$this->settings[SettingsEnum::YML_CHUNK_SIZE]
            : null;
    }

    protected function getArrayOptionValue(string $settingCode): ?array
    {
        $currentOption = $this->settings[$settingCode];

        if (!$currentOption || !is_string($currentOption)) {
            return null;
        }

        return explode(',', $currentOption);
    }

    public function getFeedServerName(): ?string
    {
        return $this->settings[SettingsEnum::YML_SERVER_NAME];
    }

    public static function getInstance(string $siteId): static
    {
        return self::$instance[$siteId] === null ? self::$instance[$siteId] = new static($siteId) : self::$instance[$siteId];
    }

    public function getCustomOperation(string $operationName): ?string
    {
        return $this->settings[$operationName];
    }

    public function getInternalGroups(): ?array
    {
        return $this->getArrayOptionValue(SettingsEnum::DISABLE_PROCESSING_USER_GROUPS);
    }

    public function getSiteId(): string
    {
        return $this->siteId;
    }

    public function getLoginIsEmailEnabled(): bool
    {
        return $this->settings[SettingsEnum::USER_LOGIN_IS_EMAIL] === 'Y';
    }

    protected function __clone()
    {
    }

    protected function __wakeup()
    {
    }
}
