<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;
?>
<?php
/** @var array $arResult */
/** @var array $arParams */
?>
<div>
    <div><?= Loc::getMessage('BONUS_TOTAL_COUNT', ['f'=>'12312']) ?> <strong><?= $arResult['bonuses']['available_format'] ?></strong></div>
</div>

<?php if (isset($arResult['loyalty']['next_level']['name']) && $arResult['loyalty']['next_level']['name'] !== '') { ?>
    <div>
        <?php echo Loc::getMessage(
                'LOYALTY_INFO',
                [
                    '#BONUS#' => $arResult['loyalty']['next_level']['total'],
                    '#STATUS#' => $arResult['loyalty']['next_level']['name'],
                    '#MONTH#' => $arResult['loyalty']['next_level']['month']
                ]
        );
        ?>
    </div>
<?php } ?>

<?php if ($arResult['history']) { ?>
    <div class="mindbox-history">
        <div class="mindbox-history__title"><?= Loc::getMessage('HISTORY_LIST_TITLE') ?></div>
        <table class="mindbox-history__list">
            <tr>
                <th><?= Loc::getMessage('HISTORY_LIST_DATE') ?></th>
                <th><?= Loc::getMessage('HISTORY_LIST_SIZE') ?></th>
                <th><?= Loc::getMessage('HISTORY_LIST_REASON') ?></th>
                <th><?= Loc::getMessage('HISTORY_LIST_DATE_END') ?></th>
            </tr>
            <tbody>
            <?php foreach ($arResult['history'] as $item) { ?>
                <tr>
                    <td><?= $item['start'] ?></td>
                    <td><?= $item['size'] ?></td>
                    <td><?= $item['name'] ?></td>
                    <td><?= $item['end'] ?></td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
        <?php if (count($arResult['history']) === intval($arParams['HISTORY_PAGE_SIZE'])) { ?>
            <button id="mindbox-bonus-more" class="mindbox-bonus-more" data-page="1">
                <?= Loc::getMessage('HISTORY_LIST_MORE') ?>
            </button>
        <?php } ?>
    </div>
<?php } ?>

<script type="text/javascript">
    const bonusHistoryData = {
        signedParameters: '<?= $this->getComponent()->getSignedParameters()?>',
        componentName: '<?= $this->getComponent()->getName()?>'
    };
</script>

