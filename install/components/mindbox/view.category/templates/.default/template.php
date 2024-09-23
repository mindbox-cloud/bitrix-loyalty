<?php
/** @var array $arResult */
?>
<script>
    mindbox("async", {
      operation: "<?=$arResult['OPERATION_PREFIX']?>.ViewCategory",
      data: {
        viewProductCategory: {
          productCategory: {
            ids: {
                <?=$arResult['ID_KEY']?>: '<?=$arResult['CATEGORY_ID']?>'
            }
          }
        }
      }
    });
</script>

