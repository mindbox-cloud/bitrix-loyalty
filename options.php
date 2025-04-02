<?php
/** @global CUser $USER */
/** @global CMain $APPLICATION */
/** @global CMain $APPLICATION */
/** @global string $mid */
/** @const SITE_SERVER_NAME */

use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Mindbox\Loyalty\Support\SettingsEnum;

defined('B_PROLOG_INCLUDED') and (B_PROLOG_INCLUDED === true) or die();
defined('MINDBOX_LOYALTY_ADMIN_MODULE_NAME') or define('MINDBOX_LOYALTY_ADMIN_MODULE_NAME', 'mindbox.loyalty');

Loader::includeModule(MINDBOX_LOYALTY_ADMIN_MODULE_NAME);
Loader::includeModule('sale');
Loc::loadLanguageFile(__FILE__);

if (!$USER->isAdmin()) {
    $APPLICATION->authForm('Nope');
}

$request = \Bitrix\Main\Context::getCurrent()->getRequest();

if ($request->isPost() && $request->get('save') && check_bitrix_sessid()) {
    $notSaveOption = [
        'USER_BITRIX_FIELDS',
        'USER_MINDBOX_FIELDS',
        'ORDER_BITRIX_STATUS',
        'ORDER_MINDBOX_STATUS',
    ];

    $queryObject = \Bitrix\Main\SiteTable::getList([
        'select' => ['LID', 'NAME'],
        'filter' => [],
        'order' => ['SORT' => 'ASC'],
    ]);
    $listSite = [];

    while ($site = $queryObject->fetch()) {
        $listSite[] = $site['LID'];
        \Bitrix\Main\Config\Option::delete(MINDBOX_LOYALTY_ADMIN_MODULE_NAME, ['site_id' => $site['LID']]);
    }

    foreach ($request->getPostList() as $key => $option) {
        if (strpos($key, 'MINDBOX_LOYALTY_') === false) {
            continue;
        }

        list($key, $site) = explode('__', $key);

        if (!in_array($site, $listSite)) {
            continue;
        }

        $key = str_replace('MINDBOX_LOYALTY_', '', $key);

        if (in_array($key, $notSaveOption)) {
            continue;
        }

        if (is_array($option)) {
            $option = implode(',', $option);
        }

        if (empty($option)) {
            \Bitrix\Main\Config\Option::delete(MINDBOX_LOYALTY_ADMIN_MODULE_NAME, ['name' => $key, 'site_id' => $site]);
        } else {
            Option::set(MINDBOX_LOYALTY_ADMIN_MODULE_NAME, $key, $option, $site);
        }
    }

    if (!empty($_REQUEST["back_url_settings"]) && empty($_REQUEST["Apply"])) {
        LocalRedirect($_REQUEST["back_url_settings"]);
    } else {
        LocalRedirect("/bitrix/admin/settings.php?lang=" . LANGUAGE_ID . "&mid=" . urlencode($mid) . "&tabControl_active_tab=" . urlencode($_REQUEST["tabControl_active_tab"] ?? '') . "&back_url_settings=" . urlencode($_REQUEST["back_url_settings"] ?? ''));
    }
}

$queryObject = \Bitrix\Main\SiteTable::getList([
    'select' => ['LID', 'NAME'],
    'filter' => [],
    'order' => ['SORT' => 'ASC'],
]);
$listSite = [];
$listTabs = [];
while ($site = $queryObject->fetch()) {
    $listSite[] = $site['LID'];

    $listTabs[] = [
        'DIV'   => $site['LID'],
        'TAB'   => $site['LID'],
        'TITLE' => $site['NAME'],
    ];
}

$tabControl = new CAdminTabControl('mindbox_loyalty', $listTabs);

$defaultOptions = Option::getDefaults(MINDBOX_LOYALTY_ADMIN_MODULE_NAME);
$arAllOptions = [];
foreach ($listSite as $site) {
    $arOptions = [
        Loc::getMessage('MINDBOX_LOYALTY_HEADING_MAIN'),
        SettingsEnum::ENABLED_LOYALTY => [
            'id' => SettingsEnum::ENABLED_LOYALTY . '__' . $site,
            'origin' => SettingsEnum::ENABLED_LOYALTY,
            'label' => Loc::getMessage('MINDBOX_LOYALTY_ENABLED_LOYALTY', ['#LID#' => $site]),
            'hints' => Loc::getMessage('MINDBOX_LOYALTY_ENABLED_LOYALTY_HINTS', ['#LID#' => $site]),
            'type' => [
                'type' => 'checkbox',
            ]
        ],
        SettingsEnum::TEST_MODE => [
            'id' => SettingsEnum::TEST_MODE . '__' . $site,
            'origin' => SettingsEnum::TEST_MODE,
            'label' => Loc::getMessage('MINDBOX_LOYALTY_TEST_MODE', ['#LID#' => $site]),
            'hints' => Loc::getMessage('MINDBOX_LOYALTY_TEST_MODE_HINTS', ['#LID#'=>$site]),
            'type' => [
                'type' => 'checkbox',
            ]
        ],
        SettingsEnum::ENDPOINT => [
            'id' => SettingsEnum::ENDPOINT . '__' . $site,
            'origin' => SettingsEnum::ENDPOINT,
            'label' => Loc::getMessage('MINDBOX_LOYALTY_ENDPOINT', ['#LID#' => $site]),
            'hints' => Loc::getMessage('MINDBOX_LOYALTY_ENDPOINT_HINTS', ['#LID#'=>$site]),
            'type' => [
                'type' => 'text',
                'size' => 60,
            ]
        ],
        SettingsEnum::SECRET_KEY => [
            'id' => SettingsEnum::SECRET_KEY . '__' . $site,
            'origin' => SettingsEnum::SECRET_KEY,
            'label' => Loc::getMessage('MINDBOX_LOYALTY_SECRET_KEY', ['#LID#' => $site]),
            'hints' => Loc::getMessage('MINDBOX_LOYALTY_SECRET_KEY_HINTS', ['#LID#'=>$site]),
            'type' => [
                'type' => 'text',
                'size' => 60,
            ]
        ],
        SettingsEnum::WEBSITE_PREFIX => [
            'id' => SettingsEnum::WEBSITE_PREFIX . '__' . $site,
            'origin' => SettingsEnum::WEBSITE_PREFIX,
            'label' => Loc::getMessage('MINDBOX_LOYALTY_WEBSITE_PREFIX', ['#LID#' => $site]),
            'hints' => Loc::getMessage('MINDBOX_LOYALTY_WEBSITE_PREFIX_HINTS', ['#LID#'=>$site]),
            'type' => [
                'type' => 'text',
                'size' => 60,
            ]
        ],
        SettingsEnum::BRAND => [
            'id' => SettingsEnum::BRAND . '__' . $site,
            'origin' => SettingsEnum::BRAND,
            'label' => Loc::getMessage('MINDBOX_LOYALTY_BRAND', ['#LID#' => $site]),
            'hints' => Loc::getMessage('MINDBOX_LOYALTY_BRAND_HINTS', ['#LID#'=>$site]),
            'type' => [
                'type' => 'text',
                'size' => 60,
            ]
        ],
        SettingsEnum::DISABLE_PROCESSING_USER_GROUPS => [
            'id' => SettingsEnum::DISABLE_PROCESSING_USER_GROUPS . '__' . $site,
            'origin' => SettingsEnum::DISABLE_PROCESSING_USER_GROUPS,
            'label' => Loc::getMessage('MINDBOX_LOYALTY_DISABLE_PROCESSING_USER_GROUPS', ['#LID#' => $site]),
            'hints' => Loc::getMessage('MINDBOX_LOYALTY_DISABLE_PROCESSING_USER_GROUPS_HINTS', ['#LID#'=>$site]),
            'type' => [
                'type' => 'multiselectbox',
                'options' => \Mindbox\Loyalty\Options::getUserGroups(),
                'size' => 3
            ]
        ],
        SettingsEnum::LOYALTY_ENABLE_EVENTS => [
            'id' => SettingsEnum::LOYALTY_ENABLE_EVENTS . '__' . $site,
            'origin' => SettingsEnum::LOYALTY_ENABLE_EVENTS,
            'label' => Loc::getMessage('MINDBOX_LOYALTY_ENABLE_EVENTS', ['#LID#' => $site]),
            'hints' => Loc::getMessage('MINDBOX_LOYALTY_ENABLE_EVENTS_HINTS', ['#LID#'=>$site]),
            'type' => [
                'type' => 'multiselectbox',
                'options' => \Mindbox\Loyalty\Support\LoyalityEvents::getAll(),
                'size' => 5
            ]
        ],
        SettingsEnum::USER_AUTO_SUBSCRIBE_POINTS => [
            'id' => SettingsEnum::USER_AUTO_SUBSCRIBE_POINTS . '__' . $site,
            'origin' => SettingsEnum::USER_AUTO_SUBSCRIBE_POINTS,
            'label' => Loc::getMessage('MINDBOX_LOYALTY_USER_AUTO_SUBSCRIBE_POINTS', ['#LID#' => $site]),
            'hints' => Loc::getMessage('MINDBOX_LOYALTY_USER_AUTO_SUBSCRIBE_POINTS_HINTS', ['#LID#'=>$site]),
            'type' => [
                'type' => 'multiselectbox',
                'options' => \Mindbox\Loyalty\Options::getSubscribePoints(),
                'size' => 5
            ]
        ],
        SettingsEnum::USER_LOGIN_IS_EMAIL => [
            'id' => SettingsEnum::USER_LOGIN_IS_EMAIL . '__' . $site,
            'origin' => SettingsEnum::USER_LOGIN_IS_EMAIL,
            'label' => Loc::getMessage('MINDBOX_LOYALTY_USER_EMAIL_IS_LOGIN', ['#LID#' => $site]),
            'hints' => Loc::getMessage('MINDBOX_LOYALTY_USER_EMAIL_IS_LOGIN_HINTS', ['#LID#'=>$site]),
            'type' => [
                'type' => 'checkbox',
            ]
        ],
        SettingsEnum::YML_BASE_PRICE_ID => [
            'id' => SettingsEnum::YML_BASE_PRICE_ID . '__' . $site,
            'origin' => SettingsEnum::YML_BASE_PRICE_ID,
            'label' => Loc::getMessage('MINDBOX_LOYALTY_YML_BASE_PRICE_ID', ['#LID#' => $site]),
            'hints' => Loc::getMessage('MINDBOX_LOYALTY_YML_BASE_PRICE_ID_HINTS', ['#LID#' => $site]),
            'type' => [
                'type' => 'selectbox',
                'options' => \Mindbox\Loyalty\Options::getPrices(),
                'size' => 1
            ]
        ],
        Loc::getMessage('MINDBOX_LOYALTY_HEADING_PRIMARY_KEY'),
        SettingsEnum::EXTERNAL_PRODUCT => [
            'id' => SettingsEnum::EXTERNAL_PRODUCT . '__' . $site,
            'origin' => SettingsEnum::EXTERNAL_PRODUCT,
            'label' => Loc::getMessage('MINDBOX_LOYALTY_EXTERNAL_PRODUCT', ['#LID#' => $site]),
            'hints' => Loc::getMessage('MINDBOX_LOYALTY_EXTERNAL_PRODUCT_HINTS', ['#LID#'=>$site]),
            'type' => [
                'type' => 'text',
                'size' => 60,
            ]
        ],
        SettingsEnum::EXTERNAL_USER => [
            'id' => SettingsEnum::EXTERNAL_USER . '__' . $site,
            'origin' => SettingsEnum::EXTERNAL_USER,
            'label' => Loc::getMessage('MINDBOX_LOYALTY_EXTERNAL_USER', ['#LID#' => $site]),
            'hints' => Loc::getMessage('MINDBOX_LOYALTY_EXTERNAL_USER_HINTS', ['#LID#'=>$site]),
            'type' => [
                'type' => 'text',
                'size' => 60,
            ]
        ],
        SettingsEnum::TEMP_EXTERNAL_ORDER => [
            'id' => SettingsEnum::TEMP_EXTERNAL_ORDER . '__' . $site,
            'origin' => SettingsEnum::TEMP_EXTERNAL_ORDER,
            'label' => Loc::getMessage('MINDBOX_LOYALTY_TEMP_EXTERNAL_ORDER', ['#LID#' => $site]),
            'hints' => Loc::getMessage('MINDBOX_LOYALTY_TEMP_EXTERNAL_ORDER_HINTS', ['#LID#' => $site]),
            'type' => [
                'type' => 'text',
                'size' => 60,
            ]
        ],
        SettingsEnum::EXTERNAL_ORDER => [
            'id' => SettingsEnum::EXTERNAL_ORDER . '__' . $site,
            'origin' => SettingsEnum::EXTERNAL_ORDER,
            'label' => Loc::getMessage('MINDBOX_LOYALTY_EXTERNAL_ORDER', ['#LID#' => $site]),
            'hints' => Loc::getMessage('MINDBOX_LOYALTY_EXTERNAL_ORDER_HINTS', ['#LID#' => $site]),
            'type' => [
                'type' => 'text',
                'size' => 60,
            ]
        ],
        SettingsEnum::WEBSITE_ORDER_FIELD => [
            'id' => SettingsEnum::WEBSITE_ORDER_FIELD . '__' . $site,
            'origin' => SettingsEnum::WEBSITE_ORDER_FIELD,
            'label' => Loc::getMessage('MINDBOX_LOYALTY_WEBSITE_ORDER_FIELD', ['#LID#' => $site]),
            'hints' => Loc::getMessage('MINDBOX_LOYALTY_WEBSITE_ORDER_FIELD_HINTS', ['#LID#' => $site]),
            'type' => [
                'type' => 'selectbox',
                'options' => [
                    'ACCOUNT_NUMBER' => 'ACCOUNT_NUMBER',
                    'ID'             => 'ID'
                ],
                'size' => 1
            ]
        ],
        SettingsEnum::BALANCE_SYSTEM_NAME => [
            'id' => SettingsEnum::BALANCE_SYSTEM_NAME . '__' . $site,
            'origin' => SettingsEnum::BALANCE_SYSTEM_NAME,
            'label' => Loc::getMessage('MINDBOX_LOYALTY_BALANCE_SYSTEM_NAME', ['#LID#' => $site]),
            'hints' => Loc::getMessage('MINDBOX_LOYALTY_BALANCE_SYSTEM_NAME_HINTS', ['#LID#' => $site]),
            'type' => [
                'type' => 'text',
                'size' => 60,
            ]
        ],
        Loc::getMessage('MINDBOX_LOYALTY_HEADING_HTTP_CLIENT'),
        SettingsEnum::API_DOMAIN => [
            'id' => SettingsEnum::API_DOMAIN . '__' . $site,
            'origin' => SettingsEnum::API_DOMAIN,
            'label' => Loc::getMessage('MINDBOX_LOYALTY_API_DOMAIN', ['#LID#' => $site]),
            'hints' => Loc::getMessage('MINDBOX_LOYALTY_API_DOMAIN_HINTS', ['#LID#'=>$site]),
            'type' => [
                'type' => 'selectbox',
                'options' => [
                    'mindbox'    => 'api.mindbox.ru',
                    'maestro' => 'api.maestra.io',
                    'api.s.mindbox' => 'api.s.mindbox.ru',
                ],
                'size' => 1
            ]
        ],
        SettingsEnum::API_DOMAIN_CUSTOM => [
            'id' => SettingsEnum::API_DOMAIN_CUSTOM . '__' . $site,
            'origin' => SettingsEnum::API_DOMAIN_CUSTOM,
            'label' => Loc::getMessage('MINDBOX_LOYALTY_API_DOMAIN_CUSTOM', ['#LID#' => $site]),
            'hints' => Loc::getMessage('MINDBOX_LOYALTY_API_DOMAIN_CUSTOM_HINTS', ['#LID#' => $site]),
            'type' => [
                'type' => 'text',
                'size' => 60
            ]
        ],
        SettingsEnum::HTTP_CLIENT => [
            'id' => SettingsEnum::HTTP_CLIENT . '__' . $site,
            'origin' => SettingsEnum::HTTP_CLIENT,
            'label' => Loc::getMessage('MINDBOX_LOYALTY_HTTP_CLIENT', ['#LID#' => $site]),
            'hints' => Loc::getMessage('MINDBOX_LOYALTY_HTTP_CLIENT_HINTS', ['#LID#'=>$site]),
            'type' => [
                'type' => 'selectbox',
                'options' => [
                    'stream' => 'Stream',
                    'curl'   => 'Curl'
                ],
                'size' => 1
            ]
        ],
        SettingsEnum::TIMEOUT => [
            'id' => SettingsEnum::TIMEOUT . '__' . $site,
            'origin' => SettingsEnum::TIMEOUT,
            'label' => Loc::getMessage('MINDBOX_LOYALTY_TIMEOUT', ['#LID#' => $site]),
            'hints' => Loc::getMessage('MINDBOX_LOYALTY_TIMEOUT_HINTS', ['#LID#'=>$site]),
            'type' => [
                'type' => 'text',
                'size' => 60,
            ]
        ],
        SettingsEnum::IS_LOGGING => [
            'id' => SettingsEnum::IS_LOGGING . '__' . $site,
            'origin' => SettingsEnum::IS_LOGGING,
            'label' => Loc::getMessage('MINDBOX_LOYALTY_IS_LOGGING', ['#LID#' => $site]),
            'hints' => Loc::getMessage('MINDBOX_LOYALTY_IS_LOGGING_HINTS', ['#LID#'=>$site]),
            'type' => [
                'type' => 'checkbox',
            ]
        ],
        SettingsEnum::LOG_PATH => [
            'id' => SettingsEnum::LOG_PATH . '__' . $site,
            'origin' => SettingsEnum::LOG_PATH,
            'label' => Loc::getMessage('MINDBOX_LOYALTY_LOG_PATH', ['#LID#' => $site]),
            'hints' => Loc::getMessage('MINDBOX_LOYALTY_LOG_PATH_HINTS', ['#LID#'=>$site]),
            'type' => [
                'type' => 'text',
                'size' => 60,
            ]
        ],
        SettingsEnum::LOG_LIFE_TIME => [
            'id' => SettingsEnum::LOG_LIFE_TIME . '__' . $site,
            'origin' => SettingsEnum::LOG_LIFE_TIME,
            'label' => Loc::getMessage('MINDBOX_LOYALTY_LOG_LIFE_TIME', ['#LID#' => $site]),
            'hints' => Loc::getMessage('MINDBOX_LOYALTY_LOG_LIFE_TIME_HINTS', ['#LID#'=>$site]),
            'type' => [
                'type' => 'text',
                'size' => 60,
            ]
        ],
        Loc::getMessage('MINDBOX_LOYALTY_HEADING_USER_FIELDS'),
        SettingsEnum::USER_BITRIX_FIELDS => [
            'id' => SettingsEnum::USER_BITRIX_FIELDS . '__' . $site,
            'origin' => SettingsEnum::USER_BITRIX_FIELDS,
            'label' => Loc::getMessage('BITRIX_FIELDS', ['#LID#' => $site]),
            'hints' => Loc::getMessage('BITRIX_FIELDS', ['#LID#' => $site]),
            'current' => [],
            'type' => [
                'type' => 'selectbox',
                'options' => \Mindbox\Loyalty\Options::getUserFields(),
                'size' => 1
            ],

        ],
        SettingsEnum::USER_MINDBOX_FIELDS => [
            'id' => SettingsEnum::USER_MINDBOX_FIELDS . '__' . $site,
            'origin' => SettingsEnum::USER_MINDBOX_FIELDS,
            'label' => Loc::getMessage('MINDBOX_FIELDS', ['#LID#' => $site]),
            'hints' => Loc::getMessage('MINDBOX_FIELDS', ['#LID#' => $site]),
            'type' => [
                'type' => 'text',
                'size' => 60,
            ]
        ],
        [
            'id' => 'user_module_button_add' . $site,
            'current' => \Mindbox\Loyalty\Options::getAddOrderMatchButton('user_module_button_add_' . $site),
            'type' => [
                'type' => 'statichtml'
            ]
        ],
        [
            'id' => '',
            'current' => \Mindbox\Loyalty\Options::getMatchesTable('user-table' . '_' . $site),
            'type' => [
                'type' => 'statichtml'
            ]
        ],
        SettingsEnum::USER_FIELDS_MATCH => [
            'id' => SettingsEnum::USER_FIELDS_MATCH . '__' . $site,
            'origin' => SettingsEnum::USER_FIELDS_MATCH,
            'type' => [
                'type' => 'text',
                'size' => 60,
            ]
        ],

        Loc::getMessage('MINDBOX_LOYALTY_HEADING_ORDER_FIELDS'),

        SettingsEnum::ORDER_BITRIX_FIELDS => [
            'id' => SettingsEnum::ORDER_BITRIX_FIELDS . '__' . $site,
            'origin' => SettingsEnum::ORDER_BITRIX_FIELDS,
            'label' => Loc::getMessage('BITRIX_FIELDS', ['#LID#' => $site]),
            'hints' => Loc::getMessage('BITRIX_FIELDS', ['#LID#' => $site]),
            'current' => [],
            'type' => [
                'type' => 'selectbox',
                'options' => \Mindbox\Loyalty\Options::getOrderFields($site),
                'size' => 1
            ],

        ],
        SettingsEnum::ORDER_MINDBOX_FIELDS => [
            'id' => SettingsEnum::ORDER_MINDBOX_FIELDS . '__' . $site,
            'origin' => SettingsEnum::ORDER_MINDBOX_FIELDS,
            'label' => Loc::getMessage('MINDBOX_FIELDS', ['#LID#' => $site]),
            'hints' => Loc::getMessage('MINDBOX_FIELDS', ['#LID#' => $site]),
            'type' => [
                'type' => 'text',
                'size' => 60,
            ]
        ],
        [
            'id' => 'order_fields_module_button_add' . $site,
            'current' => \Mindbox\Loyalty\Options::getAddOrderMatchButton('order_fields_module_button_add' . $site),
            'type' => [
                'type' => 'statichtml'
            ]
        ],
        [
            'id' => '',
            'current' => \Mindbox\Loyalty\Options::getMatchesTable('order-props-table' . '_' . $site),
            'type' => [
                'type' => 'statichtml'
            ]
        ],
        SettingsEnum::ORDER_FIELDS_MATCH => [
            'id' => SettingsEnum::ORDER_FIELDS_MATCH . '__' . $site,
            'origin' => SettingsEnum::ORDER_FIELDS_MATCH,
            'type' => [
                'type' => 'text',
                'size' => 60,
            ]
        ],

        Loc::getMessage('MINDBOX_LOYALTY_HEADING_ORDER_STATUS'),

        SettingsEnum::ORDER_BITRIX_STATUS => [
            'id' => SettingsEnum::ORDER_BITRIX_STATUS . '__' . $site,
            'origin' => SettingsEnum::ORDER_BITRIX_STATUS,
            'label' => Loc::getMessage('BITRIX_FIELDS', ['#LID#' => $site]),
            'hints' => Loc::getMessage('BITRIX_FIELDS', ['#LID#' => $site]),
            'current' => [],
            'type' => [
                'type' => 'selectbox',
                'options' => \Mindbox\Loyalty\Options::getOrderStatuses(),
                'size' => 1
            ],

        ],
        SettingsEnum::ORDER_MINDBOX_STATUS => [
            'id' => SettingsEnum::ORDER_MINDBOX_STATUS . '__' . $site,
            'origin' => SettingsEnum::ORDER_MINDBOX_STATUS,
            'label' => Loc::getMessage('MINDBOX_FIELDS', ['#LID#' => $site]),
            'hints' => Loc::getMessage('MINDBOX_FIELDS', ['#LID#' => $site]),
            'type' => [
                'type' => 'text',
                'size' => 60,
            ]
        ],
        [
            'id' => 'status_order_module_button_add' . $site,
            'current' => \Mindbox\Loyalty\Options::getAddOrderMatchButton('status_order_module_button_add' . $site),
            'type' => [
                'type' => 'statichtml'
            ]
        ],
        [
            'id' => '',
            'current' => \Mindbox\Loyalty\Options::getMatchesTable('order-status-table' . '_' . $site),
            'type' => [
                'type' => 'statichtml'
            ]
        ],
        SettingsEnum::ORDER_STATUS_MATCH => [
            'id' => SettingsEnum::ORDER_STATUS_MATCH . '__' . $site,
            'origin' => SettingsEnum::ORDER_STATUS_MATCH,
            'type' => [
                'type' => 'text',
                'size' => 60,
            ]
        ],

        Loc::getMessage('MINDBOX_LOYALTY_HEADING_USER_GROUP_DISABLED_EVENTS'),
        SettingsEnum::USER_GROUP_DISABLED_EVENTS_GROUP_ID => [
            'id' => SettingsEnum::USER_GROUP_DISABLED_EVENTS_GROUP_ID . '__' . $site,
            'origin' => SettingsEnum::USER_GROUP_DISABLED_EVENTS_GROUP_ID,
            'label' => Loc::getMessage('BITRIX_USER_GROUP', ['#LID#' => $site]),
            'hints' => Loc::getMessage('BITRIX_USER_GROUP', ['#LID#' => $site]),
            'current' => [],
            'type' => [
                'type' => 'selectbox',
                'options' => \Mindbox\Loyalty\Options::getUserGroups(),
                'size' => 1
            ]
        ],
        SettingsEnum::USER_GROUP_DISABLED_EVENTS_EVENT_NAME => [
            'id' => SettingsEnum::USER_GROUP_DISABLED_EVENTS_EVENT_NAME . '__' . $site,
            'origin' => SettingsEnum::USER_GROUP_DISABLED_EVENTS_EVENT_NAME,
            'label' => Loc::getMessage('MODULE_EVENT_NAME', ['#LID#' => $site]),
            'hints' => Loc::getMessage('MODULE_EVENT_NAME', ['#LID#' => $site]),
            'current' => [],
            'type' => [
                'type' => 'selectbox',
                'options' => \Mindbox\Loyalty\Support\LoyalityEvents::getAll(),
                'size' => 1
            ]
        ],
        [
            'id' => 'disabled_events_module_button_add' . $site,
            'current' => \Mindbox\Loyalty\Options::getAddOrderMatchButton('disabled_events_module_button_add' . $site),
            'type' => [
                'type' => 'statichtml'
            ]
        ],
        [
            'id' => '',
            'current' => \Mindbox\Loyalty\Options::getMatchesTable('disabled-events-table' . '_' . $site, Loc::getMessage('BITRIX_USER_GROUP'), Loc::getMessage('MODULE_EVENT_NAME')),
            'type' => [
                'type' => 'statichtml'
            ]
        ],
        SettingsEnum::USER_GROUP_DISABLED_EVENTS_MATCH => [
            'id' => SettingsEnum::USER_GROUP_DISABLED_EVENTS_MATCH . '__' . $site,
            'origin' => SettingsEnum::USER_GROUP_DISABLED_EVENTS_MATCH,
            'type' => [
                'type' => 'text',
                'size' => 60,
            ]
        ],

        Loc::getMessage('MINDBOX_LOYALTY_HEADING_YML_FEED'),
        SettingsEnum::YML_FEED_ENABLED => [
            'id' => SettingsEnum::YML_FEED_ENABLED . '__' . $site,
            'origin' => SettingsEnum::YML_FEED_ENABLED,
            'label' => Loc::getMessage('MINDBOX_LOYALTY_YML_FEED_ENABLED', ['#LID#' => $site]),
            'hints' => Loc::getMessage('MINDBOX_LOYALTY_YML_FEED_ENABLED_HINTS', ['#LID#' => $site]),
            'type' => [
                'type' => 'checkbox',
            ]
        ],
        SettingsEnum::YML_CATALOG_IBLOCK_ID => [
            'id' => SettingsEnum::YML_CATALOG_IBLOCK_ID . '__' . $site,
            'origin' => SettingsEnum::YML_CATALOG_IBLOCK_ID,
            'label' => Loc::getMessage('MINDBOX_LOYALTY_YML_CATALOG_IBLOCK_ID', ['#LID#' => $site]),
            'hints' => Loc::getMessage('MINDBOX_LOYALTY_YML_CATALOG_IBLOCK_ID_HINTS', ['#LID#' => $site]),
            'type' => [
                'type' => 'selectbox',
                'options' => \Mindbox\Loyalty\Options::getIblocks(),
                'size' => 1
            ]
        ],
        SettingsEnum::YML_PROTOCOL => [
            'id' => SettingsEnum::YML_PROTOCOL . '__' . $site,
            'origin' => SettingsEnum::YML_PROTOCOL,
            'label' => Loc::getMessage('MINDBOX_LOYALTY_YML_PROTOCOL', ['#LID#' => $site]),
            'hints' => Loc::getMessage('MINDBOX_LOYALTY_YML_PROTOCOL_HINTS', ['#LID#' => $site]),
            'type' => [
                'type' => 'checkbox',
            ]
        ],
        SettingsEnum::YML_PATH => [
            'id' => SettingsEnum::YML_PATH . '__' . $site,
            'origin' => SettingsEnum::YML_PATH,
            'label' => Loc::getMessage('MINDBOX_LOYALTY_YML_PATH', ['#LID#' => $site]),
            'hints' => Loc::getMessage('MINDBOX_LOYALTY_YML_PATH_HINTS', ['#LID#' => $site]),
            'type' => [
                'type' => 'text',
                'size' => 60,
            ]
        ],
        SettingsEnum::YML_CHUNK_SIZE => [
            'id' => SettingsEnum::YML_CHUNK_SIZE . '__' . $site,
            'origin' => SettingsEnum::YML_CHUNK_SIZE,
            'label' => Loc::getMessage('MINDBOX_LOYALTY_YML_CHUNK_SIZE', ['#LID#' => $site]),
            'hints' => Loc::getMessage('MINDBOX_LOYALTY_YML_CHUNK_SIZE_HINTS', ['#LID#' => $site]),
            'type' => [
                'type' => 'text',
                'size' => 60,
            ]
        ],
        SettingsEnum::YML_SERVER_NAME => [
            'id' => SettingsEnum::YML_SERVER_NAME . '__' . $site,
            'origin' => SettingsEnum::YML_SERVER_NAME,
            'label' => Loc::getMessage('MINDBOX_LOYALTY_YML_SERVER_NAME', ['#LID#' => $site]),
            'hints' => Loc::getMessage('MINDBOX_LOYALTY_YML_SERVER_NAME_HINTS', ['#LID#' => $site]),
            'type' => [
                'type' => 'text',
                'size' => 60,
            ]
        ],
    ];

    foreach ($arOptions as &$option) {
        if (!is_array($option)) {
            continue;
        }

        if (!isset($option['origin'])) {
            continue;
        }

        if (isset($option['current'])) {
            continue;
        }

        switch ($option['origin']) {
            case SettingsEnum::DISABLE_PROCESSING_USER_GROUPS:
                $option['current'] = explode(',',
                    Option::get(MINDBOX_LOYALTY_ADMIN_MODULE_NAME, $option['origin'], $defaultOptions[$option['origin']], $site));
            default:
                $option['current'] = Option::get(MINDBOX_LOYALTY_ADMIN_MODULE_NAME, $option['origin'], $defaultOptions[$option['origin']], $site);
                break;
        }
    }

    if ($arOptions[SettingsEnum::YML_CATALOG_IBLOCK_ID]['current'] !== '') {
        $catalogIblockId = (int) $arOptions[SettingsEnum::YML_CATALOG_IBLOCK_ID]['current'];
        $arOptions[SettingsEnum::YML_CATALOG_PROPERTIES] = [
            'id' => SettingsEnum::YML_CATALOG_PROPERTIES . '__' . $site,
            'origin' => SettingsEnum::YML_CATALOG_PROPERTIES,
            'current' => explode(',',
                Option::get(MINDBOX_LOYALTY_ADMIN_MODULE_NAME, SettingsEnum::YML_CATALOG_PROPERTIES, $defaultOptions[SettingsEnum::YML_CATALOG_PROPERTIES], $site)),
            'label' => Loc::getMessage('MINDBOX_LOYALTY_YML_CATALOG_PROPERTIES', ['#LID#' => $site]),
            'hints' => Loc::getMessage('MINDBOX_LOYALTY_YML_CATALOG_PROPERTIES_HINTS', ['#LID#' => $site]),
            'type' => [
                'type' => 'multiselectbox',
                'options' => \Mindbox\Loyalty\Options::getIblockProperty($catalogIblockId),
                'size' => 3
            ]
        ];

        if (($iblockOffersId = \Mindbox\Loyalty\Options::getOffersCatalogId($catalogIblockId)) > 0) {
            $arOptions[SettingsEnum::YML_OFFERS_PROPERTIES] = [
                'id' => SettingsEnum::YML_OFFERS_PROPERTIES . '__' . $site,
                'origin' => SettingsEnum::YML_OFFERS_PROPERTIES,
                'current' => explode(',',
                    Option::get(MINDBOX_LOYALTY_ADMIN_MODULE_NAME, SettingsEnum::YML_OFFERS_PROPERTIES, $defaultOptions[SettingsEnum::YML_OFFERS_PROPERTIES], $site)),
                'label' => Loc::getMessage('MINDBOX_LOYALTY_YML_OFFERS_PROPERTIES', ['#LID#' => $site]),
                'hints' => Loc::getMessage('MINDBOX_LOYALTY_YML_OFFERS_PROPERTIES_HINTS', ['#LID#' => $site]),
                'type' => [
                    'type' => 'multiselectbox',
                    'options' => \Mindbox\Loyalty\Options::getIblockProperty($iblockOffersId),
                    'size' => 3
                ]
            ];
        }
    }
    $arOptions[] = [
        'current' => \Mindbox\Loyalty\Options::getFeedUpdateButton('feed_module_button_update' . $site),
        'type' => [
            'type' => 'statichtml'
        ]
    ];
    $arOptions[] = [
        'current' => '<p class="feed_module_message_update feed_module_message_update_'.$site.'" style="display: none;"></p>',
        'type' => [
            'type' => 'statichtml'
        ]
    ];

    $arOptions[] = Loc::getMessage('MINDBOX_LOYALTY_FAVORITE');
    $arOptions[SettingsEnum::FAVORITE_TYPE] = [
        'id' => SettingsEnum::FAVORITE_TYPE . '__' . $site,
        'origin' => SettingsEnum::FAVORITE_TYPE,
        'label' => Loc::getMessage('MINDBOX_LOYALTY_FAVORITE_TYPE', ['#LID#' => $site]),
        'hints' => Loc::getMessage('MINDBOX_LOYALTY_FAVORITE_TYPE_HINTS', ['#LID#' => $site]),
        'current' => Option::get(MINDBOX_LOYALTY_ADMIN_MODULE_NAME, SettingsEnum::FAVORITE_TYPE, $defaultOptions[SettingsEnum::FAVORITE_TYPE], $site),
        'type' => [
            'type' => 'selectbox',
            'options' => \Mindbox\Loyalty\Support\FavoriteTypesEnum::getTypes(),
            'size' => 1
        ]
    ];
    $arOptions[SettingsEnum::FAVORITE_FIELD_NAME] = [
        'id' => SettingsEnum::FAVORITE_FIELD_NAME . '__' . $site,
        'origin' => SettingsEnum::FAVORITE_FIELD_NAME,
        'current' => Option::get(MINDBOX_LOYALTY_ADMIN_MODULE_NAME, SettingsEnum::FAVORITE_FIELD_NAME, $defaultOptions[SettingsEnum::FAVORITE_FIELD_NAME], $site),
        'label' => Loc::getMessage('MINDBOX_LOYALTY_FAVORITE_FIELD_NAME', ['#LID#' => $site]),
        'hints' => Loc::getMessage('MINDBOX_LOYALTY_FAVORITE_FIELD_NAME_HINTS', ['#LID#' => $site]),
        'type' => [
            'type' => 'selectbox',
            'options' => \Mindbox\Loyalty\Options::getUserFields(),
            'size' => 1
        ]
    ];

    $arOptions[] = Loc::getMessage('MINDBOX_LOYALTY_HEADING_CUSTOM_OPERATIONS');

    $defaultOperationNames = \Mindbox\Loyalty\Support\DefaultOperations::getMap();

    foreach ($defaultOperationNames as $defaultOperationName) {
        $arOptions[$defaultOperationName] = [];
        $arOptions[$defaultOperationName] = [
            'id' => $defaultOperationName . '__' . $site,
            'origin' => $defaultOperationName,
            'current' => Option::get(MINDBOX_LOYALTY_ADMIN_MODULE_NAME, $defaultOperationName, $defaultOptions[$defaultOperationName], $site),
            'label' => $defaultOperationName,
            'hints' => Loc::getMessage('MINDBOX_LOYALTY_OPERATIONS_HINTS', ['#OPERATION#' => $defaultOperationName]),
            'type' => [
                'type' => 'text',
                'size' => 20,
            ]
        ];
    }

    $arAllOptions[$site] = $arOptions;
}
?>
<form method="post"
      action="<?= $APPLICATION->GetCurPage() ?>?mid=<?= htmlspecialcharsbx($mid) ?>&lang=<?= LANG ?>">
    <?php echo bitrix_sessid_post() ?>
    <?php
    $tabControl->Begin();

    foreach ($listSite as $site) {
        $tabControl->BeginNextTab();

        foreach ($arAllOptions[$site] as $arOption) {
            if (!is_array($arOption)) {
                ?>
                <tr class="heading">
                <td colspan="2"><?= htmlspecialcharsbx($arOption); ?></td></tr><?php
                continue;
            }

            $currentValue = $arOption['current'];
            $type = $arOption['type'];
            $controlId = htmlspecialcharsbx($arOption['id']);
            $controlName = 'MINDBOX_LOYALTY_' . htmlspecialcharsbx($arOption['id']);
            $originName = $arOption['origin'] ?? '';

            ?>
            <tr data-type="<?= $type['type'] ?>">
                <td style="width: 40%; white-space: nowrap;" <?php if ($type['type'] === 'statichtml') echo ' class="adm-detail-valign-top"'?>>
                    <?php
                    if (isset($arOption['hints'])) {
                        ?><span id="hint_<?= $controlId; ?>"></span>
                        <script>BX.hint_replace(BX('hint_<?=$controlId;?>'), '<?=\CUtil::JSEscape($arOption['hints']); ?>');</script>&nbsp;<?php
                    }

                    if (isset($arOption['label'])) {
                        ?><label for="<?= $controlId; ?>"><?= htmlspecialcharsbx($arOption['label']); ?></label><?php
                    } ?>

                <td>
                    <?php
                    switch ($type['type']) {
                        case 'checkbox':
                            ?>
                            <input type="hidden" name="<?= $controlName; ?>" data-origin-name="<?$originName?>" value="N">
                            <input type="checkbox" id="<?= $controlId; ?>" name="<?= $controlName; ?>"
                                   value="Y"<?= ($currentValue == "Y" ? " checked" : ""); ?>><?php
                            break;
                        case 'text':
                            ?><input
                                type="text"
                                id="<?= $controlId; ?>"
                                data-origin-name="<?$originName?>"
                                name="<?= $controlName; ?>"
                                value="<?= htmlspecialcharsbx($currentValue); ?>" size="<?= $type['size']; ?>"
                                maxlength="255"
                            ><?php
                            break;
                        case 'statichtml':
                            echo $currentValue;
                            break;
                        case 'selectbox':
                            ?>
                            <select id="<?= $controlId; ?>" name="<?= $controlName; ?>"
                                    size="<?= $type['size'] ?>">
                                <?php foreach ($type['options'] as $oId => $oValue) {?>
                                    <option <?php
                                    if (is_array($currentValue)) {
                                        if (in_array($oId, $currentValue)) {
                                            echo "selected";
                                        }
                                    } else {
                                        if ($oId == $currentValue) {
                                            echo "selected";
                                        }
                                    }

                                    ?> value="<?= htmlspecialcharsbx($oId) ?>">
                                        <?= htmlspecialcharsbx($oValue) ?>
                                    </option>
                                <?php } ?>
                            </select>
                            <?php
                            break;
                        case 'multiselectbox':
                            if (is_string($currentValue) && str_contains($currentValue, ',')) {
                                $currentValue = explode(',', $currentValue);
                            }
                            ?>
                            <select id="<?= $controlId; ?>" name="<?= $controlName; ?>[]" multiple
                                    size="<?= $type['size'] ?>">
                                <?php foreach ($type['options'] as $oId => $oValue) {?>
                                    <option <?php
                                    if (is_array($currentValue)) {
                                        if (in_array($oId, $currentValue)) {
                                            echo "selected";
                                        }
                                    } else {
                                        if ($oId == $currentValue) {
                                            echo "selected";
                                        }
                                    }

                                    ?> value="<?= htmlspecialcharsbx($oId) ?>">
                                        <?= htmlspecialcharsbx($oValue) ?>
                                    </option>
                                <?php } ?>
                            </select>
                        <?php
                        break;
                    }
                    ?>
                </td>
            </tr>
            <?php
        }

        $tabControl->EndTab();
    }

    $tabControl->Buttons(); ?>
    <input type="submit" class="adm-btn-save" name="save" value="<?= Loc::getMessage('MINDBOX_LOYALTY_SAVE') ?>">
    <?php
    unset($arOption, $arAllOptions);
    $tabControl->End(); ?>
</form>

<style>
    .module_button {
      padding: 6px 13px 6px;
      margin: 2px;
      border-radius: 4px;
      border: none;
      border-top: 1px solid #fff;
      -webkit-box-shadow: 0 0 1px rgba(0,0,0,.11), 0 1px 1px rgba(0,0,0,.3), inset 0 1px #fff, inset 0 0 1px rgba(255,255,255,.5);
      box-shadow: 0 0 1px rgba(0,0,0,.3), 0 1px 1px rgba(0,0,0,.3), inset 0 1px 0 #fff, inset 0 0 1px rgba(255,255,255,.5);
      background-color: #e0e9ec;
      background-image: -webkit-linear-gradient(bottom, #d7e3e7, #fff) !important;
      background-image: -moz-linear-gradient(bottom, #d7e3e7, #fff) !important;
      background-image: -ms-linear-gradient(bottom, #d7e3e7, #fff) !important;
      background-image: -o-linear-gradient(bottom, #d7e3e7, #fff) !important;
      background-image: linear-gradient(bottom, #d7e3e7, #fff) !important;
      color: #3f4b54;
      cursor: pointer;
      font-family: "Helvetica Neue",Helvetica,Arial,sans-serif;
      font-weight: bold;
      font-size: 13px;
      line-height: 18px;
      text-shadow: 0 1px rgba(255,255,255,0.7);
      text-decoration: none;
      position: relative;
      vertical-align: middle;
      -webkit-font-smoothing: antialiased;
      margin-right: 10px;
      outline: none;
      border-spacing: 0;
      float: left;
    }

    .module_button_delete {
        height: 10px;
        display: inline-block;
        width: 10px;
    }
    .th {
        background-color: #e0e8ea;
        padding: 15px;
        text-align: center;
        min-width: 400px;
    }
    .th-empty {
        background-color: #e0e8ea;
        padding: 15px;
        text-align: center;
    }
    .table td {
        border-top: 1px solid #87919c;
        padding: 15px;
        text-align: center;
    }
    .table {
        margin: 0 auto !important;
        border-collapse: collapse;
    }

    select {
        width: 400px;
    }

    select option:checked {
        background-color: rgb(206, 206, 206);
    }

    tr[data-type="multiselectbox"] {
        vertical-align: top;
    }

    .feed_module_message_update {
        margin: 0;
    }

</style>

<script>
    const sites = <?= CUtil::PhpToJsObject($listSite);?>;

    function addButtonHandler(mindboxName, bitrixName, tableClass, propName, useEx = false) {
        let mindboxKey = document.querySelector('[name="'+mindboxName+'"]').value;
        let bitrixKey = document.querySelector('[name="'+bitrixName+'"]').value;

        if (mindboxKey && bitrixKey) {
            useEx
                ? setPropsExt(bitrixKey, mindboxKey, propName)
                : setProps(bitrixKey, mindboxKey, propName)

            reInitTable(tableClass, propName, useEx);
        }
    }

    function removeButtonHandler(key, tableClass, propName, useEx= false) {
        removeProps(key, propName);
        reInitTable(tableClass, propName, useEx);
    }

    function hideInput(selector) {
        document.querySelector(selector).style.display = 'none';
    }

    function addRow(bitrixKey, mindboxKey, tableClass, propName, useEx = false) {
        if (mindboxKey && bitrixKey) {
            let row = document.querySelector('table.table.'+tableClass+' tbody').insertRow();

            row.insertCell().appendChild(document.createTextNode(bitrixKey));
            row.insertCell().appendChild(document.createTextNode(mindboxKey));

            let link = document.createElement('a');
            link.classList.add('module_button_delete');
            link.innerHTML = '<svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 96 96" enable-background="new 0 0 96 96" xml:space="preserve"><polygon fill="#AAAAAB" points="96,14 82,0 48,34 14,0 0,14 34,48 0,82 14,96 48,62 82,96 96,82 62,48 "></polygon></svg>';
            link.href = 'javascript:void(0)';
            link.onclick = () => {
                let rowKey = useEx ? `${bitrixKey}-${mindboxKey}` : bitrixKey;
                removeButtonHandler(rowKey, tableClass, propName, useEx)
            };

            row.insertCell().appendChild(link);
        }
    }

    function reInitTable(tableClass, propName, useEx = false) {
        removeTable(tableClass);
        useEx
            ? createTableExt(tableClass, propName)
            : createTable(tableClass, propName)
    }

    function createTable(tableClass, propName) {
        let props = getProps(propName);

        Object.keys(props).map((objectKey, index) => {
            let value = props[objectKey];
            addRow(objectKey, value, tableClass, propName);
        });
    }

    function createTableExt(tableClass, propName) {
        let props = getProps(propName);

        Object.keys(props).map((objectKey, index) => {
            let value = props[objectKey];

            addRow(value['key'], value['value'], tableClass, propName, true);
        });
    }


    function removeProps(key, propName) {
        let currentProps = getProps(propName);

        delete currentProps[key];

        document.querySelector('[name="'+propName+'"]').value = JSON.stringify(currentProps);
    }

    function setProps(key, value, propName) {
        let currentProps = getProps(propName);

        if (Object.keys(currentProps).indexOf(value) === -1) {
            currentProps[key] = value;
        }

        document.querySelector('[name="'+propName+'"]').value = JSON.stringify(currentProps);
    }

    function setPropsExt(key, value, propName) {
        let currentProps = getProps(propName);
        let rowKey = `${key}-${value}`;

        if (Object.keys(currentProps).indexOf(rowKey) === -1) {
            currentProps[rowKey] = {
                key: key,
                value: value
            };
        }

        document.querySelector('[name="'+propName+'"]').value = JSON.stringify(currentProps);
    }


    function getProps(propName) {
        let string = document.querySelector('[name="'+propName+'"]').value;

        if (string) {
            return JSON.parse(string);
        }

        return JSON.parse('{}');
    }

    function removeTable(tableClass) {
        document.querySelectorAll('.table.'+tableClass+' tr:not(.title)').forEach((e) => {
            e.remove()
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        sites.forEach((element) => {

            createTable('user-table' + "_" + element, 'MINDBOX_LOYALTY_<?= SettingsEnum::USER_FIELDS_MATCH?>'+ "__" + element);
            hideInput('[name="MINDBOX_LOYALTY_<?= SettingsEnum::USER_FIELDS_MATCH?>' + '__' + element +'"]');
            document.querySelector('.module_button_add.user_module_button_add_' + element).onclick = () => {
                addButtonHandler(
                    'MINDBOX_LOYALTY_<?= SettingsEnum::USER_MINDBOX_FIELDS?>'  + '__' + element,
                    'MINDBOX_LOYALTY_<?= SettingsEnum::USER_BITRIX_FIELDS?>' + '__' + element,
                    'user-table' + "_" + element,
                    'MINDBOX_LOYALTY_<?= SettingsEnum::USER_FIELDS_MATCH?>' + '__' + element
                );
            };

            createTable('order-props-table' + "_" + element, 'MINDBOX_LOYALTY_<?= SettingsEnum::ORDER_FIELDS_MATCH?>'+ "__" + element);
            hideInput('[name="MINDBOX_LOYALTY_<?= SettingsEnum::ORDER_FIELDS_MATCH?>' + '__' + element +'"]');
            document.querySelector('.module_button_add.order_fields_module_button_add' + element).onclick = () => {
                addButtonHandler(
                    'MINDBOX_LOYALTY_<?= SettingsEnum::ORDER_MINDBOX_FIELDS?>'  + '__' + element,
                    'MINDBOX_LOYALTY_<?= SettingsEnum::ORDER_BITRIX_FIELDS?>' + '__' + element,
                    'order-props-table' + "_" + element,
                    'MINDBOX_LOYALTY_<?= SettingsEnum::ORDER_FIELDS_MATCH?>' + '__' + element
                );
            };

            createTable('order-status-table' + "_" + element, 'MINDBOX_LOYALTY_<?= SettingsEnum::ORDER_STATUS_MATCH?>'+ "__" + element);
            hideInput('[name="MINDBOX_LOYALTY_<?= SettingsEnum::ORDER_STATUS_MATCH?>' + '__' + element +'"]');
            document.querySelector('.module_button_add.status_order_module_button_add' + element).onclick = () => {
                addButtonHandler(
                    'MINDBOX_LOYALTY_<?= SettingsEnum::ORDER_MINDBOX_STATUS?>'  + '__' + element,
                    'MINDBOX_LOYALTY_<?= SettingsEnum::ORDER_BITRIX_STATUS?>' + '__' + element,
                    'order-status-table' + "_" + element,
                    'MINDBOX_LOYALTY_<?= SettingsEnum::ORDER_STATUS_MATCH?>' + '__' + element
                );
            };

            createTableExt('disabled-events-table' + "_" + element, 'MINDBOX_LOYALTY_<?= SettingsEnum::USER_GROUP_DISABLED_EVENTS_MATCH?>'+ "__" + element);
            hideInput('[name="MINDBOX_LOYALTY_<?= SettingsEnum::USER_GROUP_DISABLED_EVENTS_MATCH?>' + '__' + element +'"]');
            document.querySelector('.module_button_add.disabled_events_module_button_add' + element).onclick = () => {
                addButtonHandler(
                    'MINDBOX_LOYALTY_<?= SettingsEnum::USER_GROUP_DISABLED_EVENTS_EVENT_NAME?>' + '__' + element,
                    'MINDBOX_LOYALTY_<?= SettingsEnum::USER_GROUP_DISABLED_EVENTS_GROUP_ID?>'  + '__' + element,
                    'disabled-events-table' + "_" + element,
                    'MINDBOX_LOYALTY_<?= SettingsEnum::USER_GROUP_DISABLED_EVENTS_MATCH?>' + '__' + element,
                    true
                );
            };

            document.querySelector('.module_button_update.feed_module_button_update' + element).onclick = () => {
                BX.ajax.runAction('mindbox:loyalty.calculate.FeedController.update', {
                    data: {
                        siteId: element
                    }
                }).then(function (response) {
                    const messageContainer = document.querySelector('.feed_module_message_update_' + element);
                    messageContainer.style.display = 'none';
                    if (messageContainer) {
                        if (response.data.message) {
                            messageContainer.innerHTML = response.data.message;
                        }
                    }
                    messageContainer.style.display = 'block';
                });
            };
        });
    });

</script>
