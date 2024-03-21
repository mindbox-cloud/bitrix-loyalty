<?php
/** @global CUser $USER */
/** @global CMain $APPLICATION */
/** @global CMain $APPLICATION */
/** @global string $mid */
/** @const SITE_SERVER_NAME */

use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Mindbox\Loyalty\Settings\SettingsEnum;

defined('B_PROLOG_INCLUDED') and (B_PROLOG_INCLUDED === true) or die();
defined('MINDBOX_LOYALTY_ADMIN_MODULE_NAME') or define('MINDBOX_LOYALTY_ADMIN_MODULE_NAME', 'mindbox.loyalty');

Loader::includeModule('mindbox.loyalty');
Loader::includeModule(MINDBOX_LOYALTY_ADMIN_MODULE_NAME);
Loader::includeModule('sale');
Loc::loadLanguageFile(__FILE__);

if (!$USER->isAdmin()) {
    $APPLICATION->authForm('Nope');
}

$request = \Bitrix\Main\Context::getCurrent()->getRequest();


if ($request->isPost() && $request->get('save') && check_bitrix_sessid()) {
    $queryObject = \Bitrix\Main\SiteTable::getList([
        'select' => ['LID', 'NAME'],
        'filter' => [],
        'order' => ['SORT' => 'ASC'],
    ]);
    $listSite = [];
    while ($site = $queryObject->fetch()) {
        $listSite[] = $site['LID'];
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

        if (is_array($option)) {
            $option = implode(',', $option);
        }

        if ($key === SettingsEnum::DISABLE_PROCESSING_GROUPS && $request->get('MINDBOX_LOYALTY' . SettingsEnum::DISABLE_PROCESSING . '__' . $site) === 'N') {
            $option = '';
        }

        if (empty($option)) {
            \Bitrix\Main\Config\Option::delete(MINDBOX_LOYALTY_ADMIN_MODULE_NAME, ['name' => $key, 'site_id' => $site]);
        } else {
            Option::set(MINDBOX_LOYALTY_ADMIN_MODULE_NAME, $key, $option, $site);
        }
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
    ];

    foreach ($arOptions as &$option) {
        if (!is_array($option)) {
            continue;
        }

        switch ($option['origin']) {
            case SettingsEnum::DISABLE_PROCESSING_GROUPS:
                $option['current'] = explode(',',
                    Option::get(MINDBOX_LOYALTY_ADMIN_MODULE_NAME, $option['origin'], $defaultOptions[$option['origin']], $site));
            default:
                $option['current'] = Option::get(MINDBOX_LOYALTY_ADMIN_MODULE_NAME, $option['origin'], $defaultOptions[$option['origin']], $site);
                break;
        }
    }

    $arAllOptions[$site] = $arOptions;
}


?>
<form method="post"
      action="<?php echo $APPLICATION->GetCurPage() ?>?mid=<?= htmlspecialcharsbx($mid) ?>&lang=<?php echo LANG ?>">
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
            } else {
                $currentValue = $arOption['current'];
                $type = $arOption['type'];
                $controlId = htmlspecialcharsbx($arOption['id']);
                $controlName = 'MINDBOX_LOYALTY_' . htmlspecialcharsbx($arOption['id']);

                $style = '';
                if (
                    $arOption['origin'] == SettingsEnum::DISABLE_PROCESSING_GROUPS
                    && isset($arAllOptions[SettingsEnum::DISABLE_PROCESSING]['current'])
                    && $arAllOptions[SettingsEnum::DISABLE_PROCESSING]['current'] !== 'Y'
                ) {
                    $style = 'display: none;';
                }
                ?>
                <tr style="<?= $style ?>" data-type="<?= $type['type'] ?>">
                    <td style="width: 40%; white-space: nowrap;">
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
                                <input type="hidden" name="<?= $controlName; ?>" value="N">
                                <input type="checkbox" id="<?= $controlId; ?>" name="<?= $controlName; ?>"
                                       value="Y"<?= ($currentValue == "Y" ? " checked" : ""); ?>><?php
                                break;
                            case 'text':
                                ?><input type="text" id="<?= $controlId; ?>" name="<?= $controlName; ?>"
                                         value="<?= htmlspecialcharsbx($currentValue); ?>" size="<?= $type['size']; ?>"
                                         maxlength="255"><?php
                                break;
                            case 'statichtml':
                                echo $currentValue;
                                break;
                            case 'multiselectbox':
                                if ($arOption['origin'] === SettingsEnum::DISABLE_PROCESSING_GROUPS) {
                                    ?>
                                    <p class="disable_processing_groups_title"><?= Loc::getMessage('MINDBOX_LOYALTY_DISABLE_PROCESSING_GROUPS') ?></p>
                                    <?php
                                }
                                ?>
                                <input type="hidden" name="<?= $controlName; ?>" value="">
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
    select {
        width: 400px;
    }

    select option:checked {
        background-color: rgb(206, 206, 206);
    }

    tr[data-type="multiselectbox"] {
        vertical-align: top;
    }

    .disable_processing_groups_title {
        margin: 0 0 5px 0;
        font-size: 12px;
        color: #666;
    }
</style>