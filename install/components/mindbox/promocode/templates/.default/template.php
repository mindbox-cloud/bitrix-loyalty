<?php

/**
 * @var CBitrixComponentTemplate $this
 * @var array $arParams
 * @var array $arResult
 * @var string $componentPath
 * @var string $templateName
 * @var string $templateFile
 * @var string $templateFolder
 * @global $APPLICATION
 * @global $DB
 * @global $USER
 */

use Bitrix\Main\Localization\Loc;

\Bitrix\Main\Page\Asset::getInstance()->addCss('style.css');
\Bitrix\Main\Page\Asset::getInstance()->addJs('script.js');
?>

<div class="mindbox-promocode-container" data-entity="mindbox-promocode">
    <div class="mindbox-coupon-section">
        <div class="mindbox-coupon-block-field">
            <div class="mindbox-coupon-block-field-description">
                <?= Loc::getMessage('MINDBOX_COUPON_ENTER')?>
            </div>

            <div class="form">
                <div class="form-group" style="position: relative;">
                    <input type="text" class="form-control" placeholder="" data-entity="mindbox-coupon-input">
                    <span class="mindbox-coupon-block-coupon-btn" data-action="apply"></span>
                </div>
            </div>
        </div>
    </div>

    <div class="mindbox-coupon-alert-section">
        <div class="mindbox-coupon-alert-inner" data-entity="mindbox-promocode-list">
            <? foreach ($arResult['coupons'] as $couponData): ?>
                <div class="mindbox-coupon-alert <?= $couponData['apply'] ? 'text-muted' : 'text-danger' ?>">
                    <span class="mindbox-coupon-text">
                        <strong><?= $couponData['value']?></strong> <?= $couponData['apply'] ? Loc::getMessage('MINDBOX_COUPON_APPLY') : $couponData['error'] ?? Loc::getMessage('MINDBOX_COUPON_ERROR')?>
                    </span>
                    <span class="close-link" data-entity="mindbox-coupon-delete" data-action="remove" data-coupon="<?= $couponData['value']?>"><?=Loc::getMessage('MINDBOX_COUPON_DELETE')?></span>
                </div>
            <? endforeach; ?>
        </div>
    </div>
</div>

<?php
$messages = Loc::loadLanguageFile(__FILE__);
?>
<script>
    BX.message(<?=CUtil::PhpToJSObject($messages)?>);
</script>

