<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Support;

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
    public const TEMP_EXTERNAL_ORDER = 'temp_external_order';

    public const EXTERNAL_ORDER = 'external_order';
    public const WEBSITE_ORDER_FIELD = 'website_order_field';

    public const BALANCE_SYSTEM_NAME = 'balance_system_name';
    public const API_DOMAIN = 'api_domain';
    public const API_DOMAIN_CUSTOM = 'api_domain_custom';
    public const HTTP_CLIENT = 'http_client';
    public const TIMEOUT = 'timeout';
    public const IS_LOGGING = 'is_logging';
    public const LOG_PATH = 'log_path';
    public const LOG_LIFE_TIME = 'log_life_time';
    public const DISABLE_PROCESSING_USER_GROUPS = 'disable_processing_user_groups';

    public const USER_BITRIX_FIELDS = 'user_bitrix_fields';
    public const USER_MINDBOX_FIELDS = 'user_mindbox_fields';
    public const USER_FIELDS_MATCH = 'user_fields_match';
    public const USER_AUTO_SUBSCRIBE_POINTS = 'user_auto_subscribe_points';
    public const USER_LOGIN_IS_EMAIL = 'user_login_is_email';
    public const ORDER_BITRIX_FIELDS = 'order_bitrix_fields';
    public const ORDER_MINDBOX_FIELDS = 'order_mindbox_fields';
    public const ORDER_FIELDS_MATCH = 'order_fields_match';
    public const ORDER_BITRIX_STATUS = 'order_bitrix_status';
    public const ORDER_MINDBOX_STATUS = 'order_mindbox_status';
    public const ORDER_STATUS_MATCH = 'order_status_match';

    public const YML_FEED_ENABLED = 'yml_feed_enabled';
    public const YML_CATALOG_IBLOCK_ID = 'yml_catalog_iblock_id';
    public const YML_BASE_PRICE_ID = 'yml_base_price_id';
    public const YML_CATALOG_PROPERTIES = 'yml_catalog_properties';
    public const YML_OFFERS_PROPERTIES = 'yml_offers_properties';
    public const YML_ARTICLE_PROPERTY = 'yml_article_property';
    public const YML_BRAND_PROPERTY = 'yml_brand_property';
    public const YML_PROTOCOL = 'yml_protocol';
    public const YML_PATH = 'yml_path';
    public const YML_CHUNK_SIZE = 'yml_chunk_size';
    public const YML_SERVER_NAME = 'yml_server_name';
    public const LOYALTY_ENABLE_EVENTS = 'enable_events';

    public const USER_GROUP_DISABLED_EVENTS = 'user_group_disabled_events';
    public const USER_GROUP_DISABLED_EVENTS_GROUP_ID = 'user_group_disabled_events_group_id';
    public const USER_GROUP_DISABLED_EVENTS_EVENT_NAME = 'user_group_disabled_events_event_name';
    public const USER_GROUP_DISABLED_EVENTS_MATCH = 'user_group_disabled_events_match';

    public const FAVORITE_TYPE = 'favorite_type';
    public const FAVORITE_FIELD_NAME = 'favorite_field_name';
}
