<?php

use Bitrix\Main\Localization\Loc;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
/**
 * @var array $arParams
 * @var array $arResult
 */
$this->addExternalCss("/bitrix/css/main/bootstrap.css");
?>
<p><?=Loc::getMessage('PROFILE_SYNC_DATA')?></p>
<form action="<?= POST_FORM_ACTION_URI ?>" method="post" class="mt-4 profile-complete-form">
    <?= bitrix_sessid_post() ?>
    <div class="alert"></div>
    <div class="mb-3">
        <label for="mindbox_last_name" class="form-label"><?= Loc::getMessage('PROFILE_LAST_NAME') ?></label>
        <input type="text" class="form-control" id="mindbox_last_name" name="LAST_NAME" value="<?=$arResult['USER_DATA']['LAST_NAME']?>">
    </div>
    <div class="mb-3">
        <label for="mindbox_first_name" class="form-label"><?= Loc::getMessage('PROFILE_FIRST_NAME') ?></label>
        <input type="text" class="form-control" id="mindbox_first_name" name="NAME" value="<?=$arResult['USER_DATA']['NAME']?>">
    </div>
    <div class="mb-3">
        <label for="mindbox_middle_name" class="form-label"><?= Loc::getMessage('PROFILE_SECOND_NAME') ?></label>
        <input type="text" class="form-control" id="mindbox_middle_name" name="SECOND_NAME" value="<?=$arResult['USER_DATA']['SECOND_NAME']?>">
    </div>
    <div class="mb-3">
        <label for="mindbox_email" class="form-label"><?= Loc::getMessage('PROFILE_EMAIL') ?></label>
        <input type="email" class="form-control" id="mindbox_email" name="EMAIL" value="<?=$arResult['USER_DATA']['EMAIL']?>">
    </div>
    <div class="mb-3">
        <label for="mindbox_phone" class="form-label"><?= Loc::getMessage('PROFILE_PHONE') ?></label>
        <input type="tel" class="form-control" id="mindbox_phone" name="PERSONAL_PHONE" value="<?=$arResult['USER_DATA']['PERSONAL_PHONE']?>">
    </div>
    <div class="mb-3">
        <label class="form-label"><?= Loc::getMessage('PROFILE_GENDER') ?></label>
        <div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" id="mindbox_gender_m" name="PERSONAL_GENDER" value="M" <?=$arResult['USER_DATA']['PERSONAL_GENDER'] === 'M' ? 'checked' : ''?>>
                <label class="form-check-label" for="mindbox_gender_m"><?= Loc::getMessage('PROFILE_GENDER_M') ?></label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" id="mindbox_gender_f" name="PERSONAL_GENDER" value="F" <?=$arResult['USER_DATA']['PERSONAL_GENDER'] === 'F' ? 'checked' : ''?>>
                <label class="form-check-label" for="mindbox_gender_f"><?= Loc::getMessage('PROFILE_GENDER_F') ?></label>
            </div>
        </div>
    </div>
    <div class="mb-3">
        <label for="mindbox_birthdate" class="form-label"><?= Loc::getMessage('PROFILE_BIRTHDAY') ?></label>
        <input type="date" class="form-control" id="mindbox_birthdate" name="PERSONAL_BIRTHDAY" value="<?=$arResult['USER_DATA']['PERSONAL_BIRTHDAY']?>">
    </div>
    <div class="text-center mt-4">
        <button type="submit" class="btn btn-primary"><?= Loc::getMessage('PROFILE_SAVE') ?></button>
        <a href="<?=$arParams['REDIRECT_PAGE']?>" class="btn btn-default"><?=Loc::getMessage('PROFILE_CANCEL')?></a>
    </div>
</form>

<script>
    BX.ready(function () {
        ProfileCompleteForm.init('.profile-complete-form');
    });
</script>