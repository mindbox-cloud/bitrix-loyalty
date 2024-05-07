<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Models;

use Bitrix\Catalog\Product\Price\Calculation;
use Bitrix\Main\Config\Option;
use Bitrix\Sale\Discount\Actions;
use Bitrix\Sale\Discount\Formatter;
use Bitrix\Sale\Order;
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
        $basketCode = $basketItem->getId();
        $requestedPromotions = [];

        if ($arPriceTypeDiscount = self::getDiscountByPriceType($basketItem)) {
            $this->result['ORDER'][] = $arPriceTypeDiscount['BASKET'];
            $this->result['DISCOUNT_LIST'][$arPriceTypeDiscount['DISCOUNT']['ID']] = $arPriceTypeDiscount['DISCOUNT'];
        }

        // Обработка скидок каталога
        if (isset($this->result['BASKET']) && is_array($this->result['BASKET']) && isset($this->result['BASKET'][$basketCode])) {
            $arDiscountList = $this->result['DISCOUNT_LIST'];

            foreach ($this->result['BASKET'][$basketCode] as $discountBasket) {
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
        if (isset($this->result['ORDER']) && is_array($this->result['ORDER'])) {
            $arDiscountList = $this->result['DISCOUNT_LIST'];
            foreach ($this->result['ORDER'] as $discountBasket) {
                // Скидка на товар, если будет на доставку, то будет ['RESULT']['DELIVERY']
                if (!isset($discountBasket['RESULT']['BASKET'])) {
                    continue;
                }

                $discountId = $discountBasket['DISCOUNT_ID'];
                $arDiscount = $arDiscountList[$discountId];

                foreach ($discountBasket['RESULT']['BASKET'] as $iterableBasketCode => $applyDiscount) {
                    $discountPrice = 0;
                    $externalId = '';

                    if ($basketCode !== $iterableBasketCode) {
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
                        $requestedPromotions[] = [
                            'type'      => 'discount',
                            'promotion' => [
                                'ids' => [
                                    'externalId' => $externalId
                                ],
                            ],
                            'amount'    => PriceMaths::roundPrecision($discountPrice * $quantity)
                        ];
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

    /**
     * Тип скидок, когда есть два типа цены, OLD_PRICE => DISCOUNT_PRICE и на событиях через них делают скидки
     *
     * @param BasketItem $basketItem
     * @return array
     * @throws \Bitrix\Main\ArgumentNullException
     * @throws \Bitrix\Main\LoaderException
     */
    protected function getDiscountByPriceType(BasketItem $basketItem): array
    {
        if (!\Bitrix\Main\Loader::includeModule('catalog')) {
            return [];
        }

        $productPrices = self::getProductPrices($basketItem->getProductId());
        $basePriceGroupId = $this->getBasePriceId();

        if ($productPrices === []) {
            return [];
        }
        $arDiscount = [];

        $catalogGroupId = 0;
        foreach ($productPrices as $productPrice) {
            if (roundEx($productPrice['PRICE'], 2) === roundEx($basketItem->getBasePrice(), 2)) {
                $catalogGroupId = $productPrice['CATALOG_GROUP_ID'];
            }
        }
        unset($productPrice);

        foreach ($productPrices as $productPrice) {
            if (
                $productPrice['CATALOG_GROUP_ID'] === $basePriceGroupId
                && $productPrice['PRICE'] > $basketItem->getBasePrice()
                && $catalogGroupId > 0
            ) {
                $realDiscountId = 'CATALOG-GROUP-' . $catalogGroupId;

                $arDiscount['BASKET'] = [
                    'DISCOUNT_ID' => $realDiscountId,
                    'RESULT' => [
                        'BASKET' => [
                            $basketItem->getId() => [
                                'APPLY' => 'Y',
                                'DESCR' => 'Discount by price type',
                                'DESCR_DATA' => [
                                    [
                                        'TYPE' => 'CATALOG_GROUP_CUSTOM',
                                        'VALUE_TYPE' => 'CATALOG_GROUP_CUSTOM',
                                        'RESULT_VALUE' => $productPrice['PRICE'] - $basketItem->getBasePrice(),
                                    ]
                                ],
                                'MODULE_ID' => 'catalog',
                                'PRODUCT_ID' => $basketItem->getProductId(),
                                'BASKET_ID' => $basketItem->getId(),
                            ]
                        ]
                    ]
                ];

                $arDiscount['DISCOUNT'] = [
                    'ID' => $realDiscountId,
                    'DISCOUNT_ID' => $realDiscountId,
                    'REAL_DISCOUNT_ID' => $realDiscountId,
                    'MODULE_ID' => 'catalog',
                    'NAME' => 'Discount by price type',
                ];
            }
        }
        unset($productPrices, $productPrice);

        return $arDiscount;
    }

    protected function getProductPrices(int $productId): array
    {
        $iterPrices = \Bitrix\Catalog\PriceTable::getList([
            'select' => ['*'],
            'filter' => [
                '=PRODUCT_ID' => $productId,
            ],
            'order'  => ['CATALOG_GROUP_ID' => 'ASC']
        ]);

        $allProductPrices = [];
        while ($price = $iterPrices->fetch()) {
            $allProductPrices[] = $price;
        }

        return $allProductPrices;
    }

    protected function getBasePriceId(): int
    {
        $basePriceGroupId = (int) $this->settings->getBasePriceId();

        if ($basePriceGroupId !== 0) {
            return $basePriceGroupId;
        }

        $basePrice = \Bitrix\Catalog\GroupTable::getList([
            'filter' => ['BASE' => 'Y'],
            'select' => ['ID']
        ])->fetch();

       return (int) $basePrice['ID'];
    }

    protected function createSettings(): Settings
    {
        return SettingsFactory::create();
    }
}