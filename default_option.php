<?php

$mindbox_loyalty_default_option = [
    'enabled_loyalty' => 'N',
    'test_mode' => 'Y',
    'endpoint' => '',
    'secret_key' => '',
    'website_prefix' => '',
    'brand' => '',
    'external_product' => '',
    'external_user' => '',
    'external_order' => '',
    'api_domain' => 'api.mindbox.ru',
    'http_client' => 'curl',
    'timeout' => 5,
    'is_logging' => 'Y',
    'log_path' => dirname(__FILE__) . DIRECTORY_SEPARATOR . 'logs',
    'log_life_time' => 7,
    'disable_processing' => 'N',
    'disable_user_groups' => '',
    'user_bitrix_fields' => '',
    'user_mindbox_fields' => '',
    'user_auto_subscribe' => 'N',
    'user_fields_match' => '',
    'yml_protocol' => 'Y',
    'yml_path' => DIRECTORY_SEPARATOR . 'upload' . DIRECTORY_SEPARATOR . 'mindbox.yml',
    'yml_chunk_size' => 1000,
];
