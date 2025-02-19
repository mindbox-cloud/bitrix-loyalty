<?php
/** @var array $arResult */
?>
<div>
    <div>На вашем бонусном счету <strong><?= $arResult['bonuses']['available_format'] ?></strong></div>
</div>

<?php if($arResult['loyalty']['next_level']['name'] !== '') { ?>
    <div>
        Накопите еще <?= $arResult['loyalty']['next_level']['total']?> и получите <?= $arResult['loyalty']['next_level']['name'] ?> статус в <?= $arResult['loyalty']['next_level']['month']?>
    </div>
<?php } ?>

<?php if ($arResult['history']) { ?>
    <div class="mindbox-history">
        <div class="mindbox-history__title">История начисления и списания бонусов</div>
        <div class="mindbox-history__list">
            <?php foreach ($arResult['history'] as $item) { ?>
                <div>
                <span>
                    <?= $item['name'] ?>
                </span><br>
                    <span class="mindbox-history__date">
                    <?= $item['date'] ?>
                </span>
                </div>
                <div>
                    <?= $item['sum_format'] ?>
                </div>
                <div>
                <span class="mindbox-history__spend">
                    <?= $item['is_positive'] ? '+' : '-' ?><?= $item['size_format'] ?>
                </span>
                </div>
            <?php } ?>
            <button id="mindbox-bonus-more">Загрузить еще</button>
        </div>
    </div>
<?php } ?>

<script type="text/javascript">
    const bonusHistoryData = {
        signedParameters: '<?= $this->getComponent()->getSignedParameters()?>',
        componentName: '<?= $this->getComponent()->getName()?>'
    };
</script>

