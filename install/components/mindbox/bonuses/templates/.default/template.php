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

<div class="mindbox-bonus-container" data-entity="mindbox-bonuses" data-bonus-status="active">
    <div class="mindbox-bonus-section">
        <div class="mindbox-bonus-text">
            <?= Loc::getMessage('MINDBOX_BONUS_TOTAL') ?><span data-entity="mindbox-bonuses-total"><?= $arResult['total'] ?></span>
        </div>
        <div class="mindbox-bonus-text">
            <?= Loc::getMessage('MINDBOX_BONUS_AVAILABLE') ?><span data-entity="mindbox-bonuses-available"><?= $arResult['available'] ?></span>
        </div>
        <div class="mindbox-bonus-text">
            <?= Loc::getMessage('MINDBOX_BONUS_EARNED') ?><span data-entity="mindbox-bonuses-earned"><?= $arResult['earned'] ?></span>
        </div>
        <div class="mindbox-bonus-block-field">
            <div class="mindbox-bonus-block-field-description">
                <?= Loc::getMessage('MINDBOX_BONUS_LABLE') ?>
            </div>
            <div class="form">
                <div class="form-group" style="position: relative;">
                    <input
                        class="form-control"
                        data-entity="mindbox-bonuses-value"
                        type="number"
                        pattern="[0-9]*"
                        placeholder="0"
                        value="<?= $arResult['bonuses'] ?>"
                    >
                    <span class="mindbox-bonus-block-bonus-btn" data-entity="mindbox-bonuses-apply"></span>
                </div>
                <div class="mindbox-bonus-errors" data-entity="mindbox-bonuses-errors"></div>
            </div>
        </div>
    </div>
</div>

<?php
$messages = Loc::loadLanguageFile(__FILE__);
?>
<script>
    BX.message(<?=CUtil::PhpToJSObject($messages)?>);
</script>
