<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Events;

use Bitrix\Main\Page\Asset;
use Bitrix\Main\Page\AssetLocation;
use Bitrix\Sale\Internals\OrderPropsTable;
use Bitrix\Sale\Order;
use Mindbox\Loyalty\PropertyCodeEnum;
use Mindbox\Loyalty\Services\CalculateService;
use Mindbox\Loyalty\Support\LoyalityEvents;
use Mindbox\Loyalty\Support\SessionStorage;

class AdminPageEvent
{
    const PAGE_TYPE = [
        'EDIT' => 1,
        'CREATE' => 2
    ];

    public static function onAdminSaleOrderEdit()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return;
        }

        if (!LoyalityEvents::checkEnableEvent(LoyalityEvents::EDIT_ORDER_TO_ADMIN_PAGE)) {
            return;
        }

        $jsString = self::getAdditionalScriptForOrderEditPage(self::PAGE_TYPE['EDIT']);

        Asset::getInstance()->addString($jsString, true, AssetLocation::AFTER_JS);
    }

    public static function onAdminSaleOrderCreate()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return;
        }

        if (!LoyalityEvents::checkEnableEvent(LoyalityEvents::CREATE_ORDER_TO_ADMIN_PAGE)) {
            return;
        }

        $jsString = self::getAdditionalScriptForOrderEditPage(self::PAGE_TYPE['CREATE']);
        Asset::getInstance()->addString($jsString, true, AssetLocation::AFTER_JS);
    }

    private static function getAdditionalScriptForOrderEditPage(int $type): string
    {
        if ($type === self::PAGE_TYPE['CREATE']) {
            $onclick = 'BX.Sale.Admin.OrderAjaxer.refreshOrderData.setFlag(false); BX.Sale.Admin.OrderEditPage.refreshDiscounts(); return false;';
        } elseif ($type === self::PAGE_TYPE['EDIT']) {
            $onclick = 'BX.Sale.Admin.OrderEditPage.tailsLoaded = true; BX.Sale.Admin.OrderEditPage.onRefreshOrderDataAndSave(); return false;';
        }

        $return = '';
        $orderPropertyIds = self::getAdditionLoyaltyOrderPropsIds();
        $bonusPropertyCode = PropertyCodeEnum::PROPERTIES_MINDBOX_BONUS;
        $promocodePropertyCode = PropertyCodeEnum::PROPERTIES_MINDBOX_PROMO_CODE;
        $couponsPropertyCode = PropertyCodeEnum::PROPERTIES_MINDBOX_PROMOCODES;

        $orderId = (int)$_REQUEST['ID'];
        $saveButtonText = 'Применить';
        $bonusAvailableDescription = 'Доступно бонусов для списания: ';

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

                    let saveButton = "<input style='margin: 0 10px;' type='button' class='bx-adm-pc-input-submit' value='{$saveButtonText}' onclick='{$onclick}'>";
                                        
                    let propertyPomocodeId = Object.keys(mindboxPropsIds).find(key => mindboxPropsIds[key] === '{$promocodePropertyCode}');
                    const propertyPromocodeInput = document.querySelector('input[name="PROPERTIES[' + propertyPomocodeId + ']"]');
                    propertyPromocodeInput.insertAdjacentHTML('afterend', '<br><i class="mindbox_property_promocode_info" style="margin-top: 6px;display: block;"></i> ');
                    propertyPromocodeInput.insertAdjacentHTML('afterend', saveButton);
                    
                    let propertyCouponsId = Object.keys(mindboxPropsIds).find(key => mindboxPropsIds[key] === '{$couponsPropertyCode}');
                    const propertyCouponsInput = document.querySelector('input[name="PROPERTIES[' + propertyCouponsId + '][0]"]');
                    if (propertyCouponsInput) {
                        const node = propertyCouponsInput.closest('.adm-detail-content-cell-r');
                        node.insertAdjacentHTML('beforeEnd', saveButton);
                    }
                                       
                    let propertyBonusesId = Object.keys(mindboxPropsIds).find(key => mindboxPropsIds[key] === '{$bonusPropertyCode}');
                    const propertyBonusesInput = document.querySelector('input[name="PROPERTIES[' + propertyBonusesId + ']"]');
                    propertyBonusesInput.insertAdjacentHTML('afterend', '<br><i class="mindbox_property_bonuses_info" style="margin-top: 6px;display: block;">{$bonusAvailableDescription}{$bonusAvailableValue}</i> ');
                    propertyBonusesInput.insertAdjacentHTML('afterend', saveButton);
                    
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
            PropertyCodeEnum::PROPERTIES_MINDBOX_PROMO_CODE,
            PropertyCodeEnum::PROPERTIES_MINDBOX_PROMOCODES
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