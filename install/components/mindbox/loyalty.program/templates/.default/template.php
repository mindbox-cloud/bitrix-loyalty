<?php
/** @var array $arResult */
?>
<div>
    <div>На вашем бонусном счету <strong><?= $arResult['bonuses']['available_format'] ?></strong></div>
</div>

<?php if(isset($arResult['loyalty']['next_level']['name']) && $arResult['loyalty']['next_level']['name'] !== '') { ?>
    <div>
        Накопите еще <?= $arResult['loyalty']['next_level']['total']?> и получите <?= $arResult['loyalty']['next_level']['name'] ?> статус в <?= $arResult['loyalty']['next_level']['month']?>
    </div>
<?php } ?>

<?php if ($arResult['history']) { ?>
    <div class="mindbox-history">
        <div class="mindbox-history__title">История начисления и списания бонусов</div>
        <table class="mindbox-history__list">
            <tr>
                <th>Дата</th>
                <th>Размер изменения</th>
                <th>Причина</th>
                <th>Дата сгорания</th>
            </tr>
            <tbody>
                <?php foreach ($arResult['history'] as $item) { ?>
                    <tr>
                        <td><?=$item['start']?></td>
                        <td><?=$item['size']?></td>
                        <td><?=$item['name']?></td>
                        <td><?=$item['end']?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
        <?php if(count($arResult['history']) < intval($arParams['HISTORY_PAGE_SIZE'])) { ?>
            <button id="mindbox-bonus-more" class="mindbox-bonus-more" data-page="1">Загрузить еще</button>
        <?php } ?>
    </div>
<?php } ?>

<script type="text/javascript">
    const bonusHistoryData = {
        signedParameters: '<?= $this->getComponent()->getSignedParameters()?>',
        componentName: '<?= $this->getComponent()->getName()?>'
    };
</script>

