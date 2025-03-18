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

<?php if ($USER->IsAuthorized()) { ?>

    <div class="mindbox mindbox-basket-bonus mindbox-basket-bonus--active">
        <div class="mindbox-basket-bonus__container">
            <div class="mindbox-basket-bonus__promo-form-container">
                <h3 class="mindbox-basket-bonus__promo-form-title"><?= Loc::getMessage('BONUS_INFO_TITLE') ?></h3>
                <div class="mindbox-basket-bonus__bonus-container">
                    <span class="mindbox-basket-bonus__info-message">У вас</span>
                    <div class="mindbox-basket-bonus__bonus-amount-container">
                        <div class="mindbox-basket-bonus__bonus-amount" data-type="total"><?= $arResult['total'] ?></div>
                    </div>
                </div>
            </div>
            <p class="mindbox-basket-bonus__promo-cart-message"><?= Loc::getMessage('BONUS_MAXIMUM_FOR_ORDER') ?><span
                        class="mindbox-basket-bonus__promo-info-num" data-type="available"><?= $arResult['available'] ?></span>
            </p>
            <p class="mindbox-basket-bonus__promo-cart-message"><?= Loc::getMessage('BONUS_ACCRUED') ?><span
                        class="mindbox-basket-bonus__promo-info-num" data-type="earned"><?= $arResult['earned'] ?></span>
            </p>

            <div class="mindbox-basket-bonus__promo-form <?= $arResult['bonuses'] ? 'active' : '' ?>">
                <div class="mindbox-basket-bonus__promo-input-fields">
                    <div class="mindbox-basket-bonus__promo-input-fields-container">
                        <label for="mindbox-basket-bonus__promo"></label>
                        <input id="mindbox-basket-bonus__promo" class="mindbox-basket-bonus__promo" name="mindbox-bonus-value"
                               type="number"
                               pattern="[0-9]*"
                               placeholder="0" value="<?= $arResult['bonuses'] ?>" disabled>
                    </div>
                    <input class="mindbox-basket-bonus__promo-submit" type="submit"
                           value="<?= Loc::getMessage('BONUS_ACCEPT') ?>">
                </div>

                <p class="mindbox-basket-bonus__promo-error-message"><?= Loc::getMessage('BONUS_MAXIMUM_FOR_ORDER') ?><span
                            class="mindbox-basket-bonus__promo-info-num">0</span></p>
            </div>
        </div>
    </div>


<?php }

$messages = Loc::loadLanguageFile(__FILE__);
?>

<?php
$jsParams = [
    'IS_AJAX' => $arParams['IS_AJAX'],
];
?>

<script>
    window.isAjaxObj = <?= CUtil::PhpToJSObject($jsParams, false, true) ?>;
    BX.message(<?=CUtil::PhpToJSObject($messages)?>);
</script>