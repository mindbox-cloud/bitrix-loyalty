<?php
/** @var array $arResult */
?>

<script>
    mindbox("async", <?= Bitrix\Main\Web\Json::encode($arResult['PAYLOAD'])?>);
</script>

