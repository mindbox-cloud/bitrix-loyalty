<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Support;

final class Settings
{
    protected static ?Settings $instance = null;
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
        SettingsEnum::EXTERNAL_ORDER => null,
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
    ];

    protected function __construct(string $siteId)
    {
        $this->siteId = $siteId;

        foreach ($this->settings as $settingCode => $value) {
            $this->settings[$settingCode] = \Bitrix\Main\Config\Option::get(
                moduleId: 'mindbox.loyalty',
                name: $settingCode,
                siteId: $this->siteId
            );
        }
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
        return (int)$this->settings[SettingsEnum::TIMEOUT];
    }

    public function getExternalUserId(): ?string
    {
        return $this->settings[SettingsEnum::EXTERNAL_USER];
    }

    /**
     * @return array<string, string>
     */
    public function getUserFieldsMatch(): array
    {
        $result = [];

        $fields = $this->settings[SettingsEnum::USER_FIELDS_MATCH];

        if (!empty($fields)) {
            $decode = json_decode($fields, true);

            if (\json_last_error() === \JSON_ERROR_NONE) {
                $result = $decode;
            }

        }

        return $result;
    }

    public function getLogPath(): ?string
    {
        return $this->settings[SettingsEnum::LOG_PATH];
    }

    public static function getInstance(string $siteId): static
    {
        return self::$instance === null ? self::$instance = new static($siteId) : self::$instance;
    }

    protected function __clone()
    {
    }

    protected function __wakeup()
    {
    }


}