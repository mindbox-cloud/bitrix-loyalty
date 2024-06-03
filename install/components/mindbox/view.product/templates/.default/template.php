<?php
/** @var array $arResult */
?>
<script>
    mindbox("async", {
      operation: "<?=$arResult['OPERATION_PREFIX']?>.ViewProduct",
      data: {
        viewProduct: {
          product: {
            ids: {
                <?=$arResult['ID_KEY']?>: <?=$arResult['PRODUCT_ID']?>
            }
          }
        }
      }
    });
</script>

