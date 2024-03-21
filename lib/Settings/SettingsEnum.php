<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Settings;

class SettingsEnum
{
    public const ENABLED_LOYALTY = 'enabled_loyalty';
    public const TEST_MODE = 'test_mode';
    public const ENDPOINT = 'endpoint';
    public const SECRET_KEY = 'secret_key';
    public const WEBSITE_PREFIX = 'website_prefix';
    public const BRAND = 'brand';
    public const EXTERNAL_PRODUCT = 'external_product';
    public const EXTERNAL_USER = 'external_user';
    public const EXTERNAL_ORDER = 'external_order';
    public const API_DOMAIN = 'api_domain';
    public const HTTP_CLIENT = 'http_client';
    public const TIMEOUT = 'timeout';
    public const IS_LOGGING = 'is_logging';
    public const LOG_PATH = 'log_path';
    public const LOG_LIFE_TIME = 'log_life_time';
    public const DISABLE_PROCESSING = 'disable_processing';
    public const DISABLE_PROCESSING_GROUPS = 'disable_user_groups';
    public const USER_BITRIX_FIELDS = 'user_bitrix_fields';
    public const USER_MINDBOX_FIELDS = 'user_mindbox_fields';
    public const USER_FIELDS_MATCH = 'user_fields_match';
}