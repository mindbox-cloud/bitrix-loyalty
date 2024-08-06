<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Events;

use Bitrix\Main\Page\Asset;
use Bitrix\Main\Page\AssetLocation;
use Bitrix\Sale\Internals\OrderPropsTable;
use Bitrix\Sale\Order;
use Mindbox\Loyalty\PropertyCodeEnum;
use Mindbox\Loyalty\Services\CalculateService;
use Mindbox\Loyalty\Support\SessionStorage;

class AdminPageEvent
{
    public static function onAdminSaleOrderEdit()
    {
        $jsString = self::getAdditionalScriptForOrderEditPage();

        Asset::getInstance()->addString($jsString, true, AssetLocation::AFTER_JS);
    }

    private static function getAdditionalScriptForOrderEditPage(): string
    {
        $return = '';
        $orderPropertyIds = self::getAdditionLoyaltyOrderPropsIds();
        $bonusPropertyCode = PropertyCodeEnum::PROPERTIES_MINDBOX_BONUS;
        $promocodePropertyCode = PropertyCodeEnum::PROPERTIES_MINDBOX_PROMO_CODE;

        $orderId = (int)$_REQUEST['ID'];
        $saveButtonText = 'Применить';
        $bonusAvailableDescription = 'Доступно бонусов для списания: ';

        $backgroundSuccess = '#009940';
        $backgroundError = '#e63535';

        if (!empty($orderId)) {
            $order = Order::load($orderId);

            $service = new CalculateService();
            try {
                $service->calculateOrder($order);
                $bonusAvailableValue = (int) SessionStorage::getInstance()->getOrderAvailableBonuses();
            } catch (\Exception $e) {
                $error = $e->getMessage();
                $bonusAvailableDescription = $error;
            }
        }

        if (!empty($orderPropertyIds) && is_array($orderPropertyIds)) {
            $encodeOrderPropertyIds = json_encode($orderPropertyIds);

            $return = <<<HTML
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const mindboxPropsIds = {$encodeOrderPropertyIds};
                    
                    let saveButton = "<input style='margin: 0 10px;' type='submit' class='bx-adm-pc-input-submit' value='{$saveButtonText}' onclick='BX.Sale.Admin.OrderEditPage.tailsLoaded = true; BX.Sale.Admin.OrderEditPage.onRefreshOrderDataAndSave(); return false;'>"
                    let defaultBitrixPromocode = document.querySelector('#sale-admin-order-coupons');
                                        
                    let propertyPomocodeId = Object.keys(mindboxPropsIds).find(key => mindboxPropsIds[key] === '{$promocodePropertyCode}');
                    const propertyPromocodeInput = document.querySelector('input[name="PROPERTIES[' + propertyPomocodeId + ']"]');
                    propertyPromocodeInput.insertAdjacentHTML('afterend', '<br><i class="mindbox_property_promocode_info" style="margin-top: 6px;display: block;"></i> ');
                    propertyPromocodeInput.insertAdjacentHTML('afterend', saveButton);
                    
                    let propertyBonusesId = Object.keys(mindboxPropsIds).find(key => mindboxPropsIds[key] === '{$bonusPropertyCode}');
                    const propertyBonusesInput = document.querySelector('input[name="PROPERTIES[' + propertyBonusesId + ']"]');
                    propertyBonusesInput.insertAdjacentHTML('afterend', '<br><i class="mindbox_property_bonuses_info" style="margin-top: 6px;display: block;">{$bonusAvailableDescription}{$bonusAvailableValue}</i> ');
                    propertyBonusesInput.insertAdjacentHTML('afterend', saveButton);
                    
                    if (defaultBitrixPromocode) {
                        defaultBitrixPromocode.closest('.adm-s-result-container-promo').remove();
                    }
                    
                     if(typeof BX !== 'undefined' && BX.addCustomEvent) {
                        BX.addCustomEvent('onAjaxSuccessFinish', BX.delegate(function(data) {
                            if (-1 === data.url.indexOf('calculate.AdminPage.get')) {
                                BX.ajax.runAction('mindbox:loyalty.calculate.AdminPage.get', {
                                }).then(function (response) {	
                                    let propertyInfo = document.querySelector('.mindbox_property_bonuses_info');                                     
                                    if (propertyInfo) {     
                                        propertyInfo.innerHTML = '{$bonusAvailableDescription}' + response.data.available;
                                    }
                                    
                                    if (response.data.available < propertyBonusesInput.value) {
                                        propertyBonusesInput.style.background = '{$backgroundError}';
                                    } else {
                                        propertyBonusesInput.style.background = 'none';
                                    }
                                      
                                      if (response.data.promocode_error) {                                        
                                        let promocodeNodeInfo = document.querySelector('.mindbox_property_promocode_info');
                                        
                                        promocodeNodeInfo.innerHTML = response.data.promocode_error;
                                        propertyPromocodeInput.style.background = '{$backgroundError}';
                                      } else {
                                         propertyPromocodeInput.style.background = 'none';
                                      }
                                });
                            }                       
                        }, this));
                     }
                });
            </script>
HTML;
        }
        return $return;
    }

    private static function getAdditionLoyaltyOrderPropsIds(): array
    {
        $return = [];

        $additionalPropertiesCode = [
            PropertyCodeEnum::PROPERTIES_MINDBOX_BONUS,
            PropertyCodeEnum::PROPERTIES_MINDBOX_PROMO_CODE
        ];

        $iterator = OrderPropsTable::getList([
            'filter' => ['CODE' => $additionalPropertiesCode],
            'select' => ['ID', 'CODE']
        ]);
        while ($property = $iterator->fetch()) {
            $return[$property['ID']] = $property['CODE'];
        }

        return $return;
    }
}