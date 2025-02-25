<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Models;

use Bitrix\Catalog\Product\Price\Calculation;
use Bitrix\Main\Config\Option;
use Bitrix\Sale\Discount\Actions;
use Bitrix\Sale\Discount\Formatter;
use Bitrix\Sale\BasketItem;
use Bitrix\Sale\OrderBase;
use Bitrix\Sale\PriceMaths;
use Mindbox\Loyalty\Support\Settings;
use Mindbox\Loyalty\Support\SettingsFactory;

class RequestedPromotions
{
    private Settings $settings;
    private array $result = [];
    public function __construct(?OrderBase $order, ?Settings $settings)
    {
        if ($settings === null) {
            $settings = $this->createSettings();
        }

        $this->settings = $settings;

        if (isset($order)) {
            $discounts = $order->getDiscount();
        }

        if (isset($discounts)) {
            $discounts->calculate();

            $this->result = $discounts->getApplyResult();
        }
    }

    /**
     * Большая простыня кода, разделять не стал
     *
     * @param BasketItem $basketItem
     * @return array
     * @throws \Bitrix\Main\ArgumentNullException
     */
    public function getDataForBasketItem(BasketItem $basketItem)
    {
        $basePrice = $basketItem->getBasePrice();
        $currentPrice = $basePrice;
        $basketCode = $basketItem->getBasketCode();
        $requestedPromotions = [];
        $result = $this->result;

        $customPromotions = \Mindbox\Loyalty\EventSender::callEventOnCustomPromotionsBasketItem($basketItem, $this->settings);

        foreach ($customPromotions as $arPriceTypeDiscount) {
            $result['ORDER'][] = $arPriceTypeDiscount['BASKET'];
            $result['DISCOUNT_LIST'][$arPriceTypeDiscount['DISCOUNT']['ID']] = $arPriceTypeDiscount['DISCOUNT'];
        }
        unset($customPromotions);

        // Обработка скидок каталога
        if (isset($result['BASKET']) && is_array($result['BASKET']) && isset($result['BASKET'][$basketCode])) {
            $arDiscountList = $result['DISCOUNT_LIST'];

            foreach ($result['BASKET'][$basketCode] as $discountBasket) {
                $discountPrice = 0;
                $externalId = '';
                $quantity = $basketItem->getQuantity();

                $discountId = $discountBasket['DISCOUNT_ID'];
                $arDiscount = $arDiscountList[$discountId];

                $applyDiscount = $discountBasket['RESULT'];

                if ($applyDiscount['APPLY'] !== 'Y') {
                    continue;
                }

                $arActionDescrData = current($applyDiscount['DESCR_DATA']);

                if ($arDiscount['MODULE_ID'] === 'sale') {
                    if (isset($arActionDescrData['VALUE_TYPE'])) {
                        switch ($arActionDescrData['VALUE_TYPE']) {
                            case Actions::VALUE_TYPE_PERCENT:
                                // процент скидки на товар - P
                                $discountPrice = $arActionDescrData['VALUE_ACTION'] == Formatter::VALUE_ACTION_DISCOUNT
                                    ? $arActionDescrData['RESULT_VALUE']
                                    : -1 * $arActionDescrData['RESULT_VALUE'];

                                break;
                            case Actions::VALUE_TYPE_FIX:
                                // фиксированная скидка на товар - F
                                $discountPrice = (float) $arActionDescrData['RESULT_VALUE'];
                                break;
                            case Actions::VALUE_TYPE_SUMM:
                                // установка скидки на общую сумму товаров - S
                                $discountPrice = $arActionDescrData['VALUE_ACTION'] == Formatter::VALUE_ACTION_DISCOUNT
                                    ? $arActionDescrData['RESULT_VALUE']
                                    : -1 * $arActionDescrData['RESULT_VALUE'];

                                break;
                            case Actions::VALUE_TYPE_CLOSEOUT:
                                // фиксированная скидка на товар - C
                                $discountPrice = $arActionDescrData['VALUE_ACTION'] == Formatter::VALUE_ACTION_DISCOUNT
                                    ? $arActionDescrData['RESULT_VALUE']
                                    : -1 * $arActionDescrData['RESULT_VALUE'];
                                break;
                        }
                    } elseif (isset($arActionDescrData['TYPE'])) {
                        switch ($arActionDescrData['TYPE']) {
                            case Formatter::TYPE_SIMPLE:
                                // процент скидки на товар
                                $discountPrice = Calculation::roundPrecision(
                                    $currentPrice * ($arActionDescrData['VALUE'] / 100)
                                );
                                break;
                            case Formatter::TYPE_LIMIT_VALUE:
                            case Formatter::TYPE_VALUE:
                                // фиксированная скидка на товар - 2, 4
                                $discountPrice = (float) $arActionDescrData['VALUE'];
                                break;
                            case Formatter::TYPE_FIXED:
                                // установка стоимости на товар - 8
                                $requestedPromotions = [];
                                $currentPrice = $basePrice;
                                $discountPrice = (float) ($currentPrice - $arActionDescrData['VALUE']);
                                break;
                            case Formatter::TYPE_SIMPLE_GIFT:
                                // подарок - 32
                                $requestedPromotions = [];
                                $currentPrice = $basePrice;
                                $discountPrice = (float) ($currentPrice - $arActionDescrData['VALUE']);
                                break;
                        }
                    }

                    $externalId = "SCR-" . $arDiscount['REAL_DISCOUNT_ID'];
                } elseif ($arDiscount['MODULE_ID'] === 'catalog') {
                    if (isset($arActionDescrData['VALUE_TYPE'])) {
                        switch ($arActionDescrData['VALUE_TYPE']) {
                            case Actions::VALUE_TYPE_PERCENT:
                                // процент скидки на товар - P
                                $discountPrice = (float) $arActionDescrData['RESULT_VALUE'];
                                break;
                            case Actions::VALUE_TYPE_FIX:
                                // фиксированная скидка на товар - F
                                $discountPrice = (float) $arActionDescrData['VALUE'];
                                break;
                            case Actions::VALUE_TYPE_SUMM:
                                // скидка на общую сумму товаров - S
                                $discountPrice = $arActionDescrData['RESULT_VALUE'];
                                break;
                            case Actions::VALUE_TYPE_CLOSEOUT:
                                // Скидка на каждый товар - C
                                $discountPrice = (float) $arActionDescrData['VALUE'];
                                break;
                        }
                    } elseif (isset($arActionDescrData['TYPE'])) {
                        switch ($arActionDescrData['TYPE']) {
                            case Formatter::TYPE_SIMPLE:
                                // процент скидки на товар - 1
                                // не удалось получить данную скидку
                                $discountPrice = (float)$arActionDescrData['RESULT_VALUE'];;
                                break;
                            case Formatter::TYPE_LIMIT_VALUE:
                            case Formatter::TYPE_VALUE:
                                // фиксированная скидка на товар
                                // не удалось получить данную скидку
                                $discountPrice = (float) $arActionDescrData['VALUE'];
                                break;
                            case Formatter::TYPE_FIXED:
                                // фиксированная стоимость на товар - 8
                                $requestedPromotions = [];
                                $currentPrice = $basePrice;
                                $discountPrice = (float) ($currentPrice - $arActionDescrData['VALUE']);

                                break;
                        }
                    }

                    $externalId = "PD-" . $arDiscount['REAL_DISCOUNT_ID'];
                }

                if (
                    isset($arActionDescrData['LIMIT_TYPE'])
                    && isset($arActionDescrData['LIMIT_VALUE'])
                    && $arActionDescrData['LIMIT_TYPE'] === Formatter::LIMIT_MAX
                    && $discountPrice > $arActionDescrData['LIMIT_VALUE']
                ) {
                    $discountPrice = (float) $arActionDescrData['LIMIT_VALUE'];
                }

                // todo, нужен пример получения купона, для передачи купона
                if ($discountPrice != 0 && !empty($externalId)) {
                    $requestedPromotions[] = [
                        'type'      => 'discount',
                        'promotion' => [
                            'ids' => [
                                'externalId' => $externalId
                            ],
                        ],
                        'amount' => PriceMaths::roundPrecision($discountPrice * $quantity)
                    ];
                }

                if (!self::isPercentFromBasePrice() && $discountPrice !== 0) {
                    $currentPrice = $basePrice - $discountPrice;
                }
            }
        }

        // Обработка правил работы с корзиной
        if (isset($result['ORDER']) && is_array($result['ORDER'])) {
            $arDiscountList = $result['DISCOUNT_LIST'];
            foreach ($result['ORDER'] as $discountBasket) {
                // Скидка на товар, если будет на доставку, то будет ['RESULT']['DELIVERY']
                if (!isset($discountBasket['RESULT']['BASKET'])) {
                    continue;
                }

                $discountId = $discountBasket['DISCOUNT_ID'];
                $arDiscount = $arDiscountList[$discountId];
                $coupon = $arDiscount['USE_COUPONS'] === 'Y' ? $discountBasket['COUPON_ID'] : null;

                foreach ($discountBasket['RESULT']['BASKET'] as $iterableBasketCode => $applyDiscount) {
                    $discountPrice = 0;
                    $externalId = '';

                    if ($basketCode != $applyDiscount['BASKET_ID']) {
                        continue;
                    }

                    if ($applyDiscount['APPLY'] !== 'Y') {
                        continue;
                    }

                    $quantity = $basketItem->getQuantity();
                    $arActionDescrData = current($applyDiscount['DESCR_DATA']);

                    if ($arDiscount['MODULE_ID'] === 'sale') {
                        if (isset($arActionDescrData['VALUE_TYPE'])) {
                            switch ($arActionDescrData['VALUE_TYPE']) {
                                case Actions::VALUE_TYPE_PERCENT:
                                    // процент скидки на товар - P
                                    $discountPrice = $arActionDescrData['VALUE_ACTION'] == Formatter::VALUE_ACTION_DISCOUNT
                                        ? $arActionDescrData['RESULT_VALUE']
                                        : -1 * $arActionDescrData['RESULT_VALUE'];

                                    break;
                                case Actions::VALUE_TYPE_FIX:
                                    // фиксированная скидка на товар - F
                                    $discountPrice = (float) $arActionDescrData['RESULT_VALUE'];
                                    break;
                                case Actions::VALUE_TYPE_SUMM:
                                    // установка скидки на общую сумму товаров - S
                                    $discountPrice = $arActionDescrData['VALUE_ACTION'] == Formatter::VALUE_ACTION_DISCOUNT
                                        ? $arActionDescrData['RESULT_VALUE']
                                        : -1 * $arActionDescrData['RESULT_VALUE'];

                                    break;
                                case Actions::VALUE_TYPE_CLOSEOUT:
                                    // фиксированная скидка на товар - C
                                    $discountPrice = $arActionDescrData['VALUE_ACTION'] == Formatter::VALUE_ACTION_DISCOUNT
                                        ? $arActionDescrData['RESULT_VALUE']
                                        : -1 * $arActionDescrData['RESULT_VALUE'];
                                    break;
                                case 'CATALOG_GROUP_CUSTOM':
                                    // кастомная скидка по типу цен
                                    $discountPrice = $arActionDescrData['RESULT_VALUE'];
                            }
                        } elseif (isset($arActionDescrData['TYPE'])) {
                            switch ($arActionDescrData['TYPE']) {
                                case Formatter::TYPE_SIMPLE:
                                    // процент скидки на товар
                                    $discountPrice = Calculation::roundPrecision(
                                        $currentPrice * ($arActionDescrData['VALUE'] / 100)
                                    );
                                    break;
                                case Formatter::TYPE_LIMIT_VALUE:
                                case Formatter::TYPE_VALUE:
                                    // фиксированная скидка на товар - 2, 4
                                    $discountPrice = (float) $arActionDescrData['VALUE'];
                                    break;
                                case Formatter::TYPE_FIXED:
                                    // установка стоимости на товар - 8
                                    $discountPrice = (float) ($currentPrice - $arActionDescrData['VALUE']);
                                    break;
                                case Formatter::TYPE_SIMPLE_GIFT:
                                    // подарок - 32
                                    $requestedPromotions = [];
                                    $currentPrice = $basePrice;
                                    $discountPrice = (float) ($currentPrice - $arActionDescrData['VALUE']);
                                    break;
                            }
                        }

                        $externalId = "SCR-" . $arDiscount['REAL_DISCOUNT_ID'];
                    } elseif ($arDiscount['MODULE_ID'] === 'catalog') {
                        if (isset($arActionDescrData['VALUE_TYPE'])) {
                            switch ($arActionDescrData['VALUE_TYPE']) {
                                case Actions::VALUE_TYPE_PERCENT:
                                    // процент скидки на товар - P
                                    $discountPrice = (float) $arActionDescrData['RESULT_VALUE'];
                                    break;
                                case Actions::VALUE_TYPE_FIX:
                                    // фиксированная скидка на товар - F
                                    $discountPrice = (float) $arActionDescrData['VALUE'];
                                    break;
                                case Actions::VALUE_TYPE_SUMM:
                                    // скидка на общую сумму товаров - S
                                    $discountPrice = $arActionDescrData['RESULT_VALUE'];
                                    break;
                                case Actions::VALUE_TYPE_CLOSEOUT:
                                    // Скидка на каждый товар - C
                                    $discountPrice = (float) $arActionDescrData['RESULT_VALUE'];
                                    break;
                                case 'CATALOG_GROUP_CUSTOM':
                                    // кастомная скидка по типу цен
                                    $discountPrice = $arActionDescrData['RESULT_VALUE'];
                                    break;
                                default:
                                    $discountPrice = (float) $arActionDescrData['RESULT_VALUE'];
                                    break;
                            }
                        } elseif (isset($arActionDescrData['TYPE'])) {
                            switch ($arActionDescrData['TYPE']) {
                                case Formatter::TYPE_SIMPLE:
                                    // процент скидки на товар - 1
                                    $discountPrice = (float) $currentPrice * ($arActionDescrData['VALUE'] / 100);
                                    break;
                                case Formatter::TYPE_LIMIT_VALUE:
                                case Formatter::TYPE_VALUE:
                                    // фиксированная скидка на товар
                                    $discountPrice = (float) $arActionDescrData['VALUE'];
                                    break;
                                case Formatter::TYPE_FIXED:
                                    // фиксированная стоимость на товар - 8
                                    $discountPrice = (float) ($currentPrice - $arActionDescrData['VALUE']);

                                    break;
                            }
                        }

                        $externalId = "PD-" . $arDiscount['REAL_DISCOUNT_ID'];
                    }

                    if (
                        isset($arActionDescrData['LIMIT_TYPE'])
                        && isset($arActionDescrData['LIMIT_VALUE'])
                        && $arActionDescrData['LIMIT_TYPE'] === Formatter::LIMIT_MAX
                        && $discountPrice > $arActionDescrData['LIMIT_VALUE']
                    ) {
                        $discountPrice = (float) $arActionDescrData['LIMIT_VALUE'];
                    }

                    if ($discountPrice != 0 && !empty($externalId)) {
                        $discountData = [
                            'type' => 'discount',
                            'promotion' => [
                                'ids' => [
                                    'externalId' => $externalId
                                ],
                            ],
                            'amount' => PriceMaths::roundPrecision($discountPrice * $quantity)
                        ];

                        if ($coupon) {
                            $discountData['coupon'] = [
                                'ids' => [
                                    'code' => $coupon
                                ]
                            ];
                        }

                        $requestedPromotions[] = $discountData;
                    }
                }

                if (!self::isPercentFromBasePrice() && $discountPrice !== 0) {
                    $currentPrice = $basePrice - $discountPrice;
                }
            }
        }

        return $requestedPromotions;
    }

    public static function isPercentFromBasePrice(): bool
    {
        return Option::get('sale', 'get_discount_percent_from_base_price', 'N') === 'Y';
    }

    protected function createSettings(): Settings
    {
        return SettingsFactory::create();
    }
}