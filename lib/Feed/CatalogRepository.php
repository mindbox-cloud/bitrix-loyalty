<?php
namespace Mindbox\Loyalty\Feed;

use Bitrix\Catalog\PriceTable;
use Bitrix\Iblock\ElementTable;
use Bitrix\Iblock\SectionTable;

class CatalogRepository implements RepositoryInterface
{
    protected int $stepSize = 1000;
    protected ?int $offersCatalogId = null;

    public function getProducts(): \Iterator
    {
        $this->setProductCount();

        if ($this->getProductCount() === 0) {
            return [];
        }

        $pages = ceil($this->getProductCount() / $this->getStepSize());

        for ($step = 1; $step <= $pages; $step++) {
            $this->cleanProducts();
            $this->loadProducts($this->getStepSize(), $step);
            $this->loadOffers();

            yield ['products' => $this->products, 'offers' => $this->offers];
        }
    }

    /**
     * @param int $stepSize
     * @return void
     */
    public function setStepSize(int $stepSize): void
    {
        $this->stepSize = $stepSize;
    }

    /**
     * Возвращает категории.
     */
    public function getCategories(): array
    {
        $arSelect = [
            'ID',
            'IBLOCK_SECTION_ID',
            'NAME'
        ];

        $arFilter = [
            '=IBLOCK_ID' => $this->getIblockId(),
            '=ACTIVE' => 'Y'
        ];

        $iterator = SectionTable::getList([
            'filter' => $arFilter,
            'select' => $arSelect,
            'order' => ['SORT' => 'ASC']
        ]);

        $sections = [];
        while ($section = $iterator->fetch()) {
            $sections[$section['ID']] = $section;
        }

        return $sections;
    }

    /**
     * Возвращает валюты.
     * @return array
     */
    public function getCurrencies(): array
    {
        $iterator = \Bitrix\Currency\CurrencyTable::query()
            ->setSelect(['AMOUNT', 'CURRENCY'])
            ->exec();

        $currencies = [];
        while ($currency = $iterator->fetch()) {
            $currencies[$currency['CURRENCY']] = [
                'AMOUNT' => $currency['AMOUNT'],
                'CURRENCY' => $currency['CURRENCY'],
            ];
        }

        return $currencies;
    }

    /**
     * Возвращает список категорий, к которому принадлежит элемент
     * @param int $productId
     * @return array
     */
    public function getProductGroups(int $productId): array
    {
        $return = [];

        if (!empty($productId)) {
            $getElementGroups = \CIBlockElement::GetElementGroups($productId, false, ['ID', 'ACTIVE']);

            while ($item = $getElementGroups->Fetch()) {
                if ($item['ACTIVE'] === 'Y') {
                    $return[$item['ID']] = $item['ID'];
                }
            }
        }

        unset($getElementGroups, $item);

        return $return;
    }

    /**
     * @return void
     */
    public function cleanProducts()
    {
        $this->products = [];
        $this->offers = [];
    }

    /**
     * Загружаем товары
     * @param integer $limit  Количество выбираемых данныхы
     * @param integer $offset Шаг смещения в запросе
     */
    public function loadProducts($limit, $offset)
    {
        $arSelect = array(
            'ID',
            'IBLOCK_ID',
            'IBLOCK_SECTION_ID',
            'DETAIL_PAGE_URL',
            'CATALOG_GROUP_' . $this->getBasePriceId(),
            'NAME',
            'DETAIL_PICTURE',
            'DETAIL_TEXT',
            'PREVIEW_PICTURE',
            'PREVIEW_TEXT',
            'ACTIVE'
        );

        $iterator = \CIBlockElement::GetList(
            [],
            ['=IBLOCK_ID' => $this->getIblockId()],
            false,
            ['nTopCount' => $limit, 'nOffset' => $offset],
            $arSelect
        );

        $arProductId = [];

        while ($prod = $iterator->GetNext()) {
            $arProductId[] = $prod['ID'];

            $prod['prices'] = \CCatalogProduct::GetOptimalPrice($prod['ID'], 1, [], 'N', [], $this->getLid());

            if ((int)$prod['prices']['RESULT_PRICE']['PRICE_TYPE_ID'] !== $this->getBasePriceId()) {
                $prod['prices']['RESULT_PRICE'] = $this->getResultPrice($prod);
            }

            $prod['properties'] = [];

            $this->products[$prod['ID']] = $prod;
        }

        if (!empty($addProps = $this->getCatalogPropertyCode()) && !empty($arProductId)) {
            $properties = $this->getProperties($addProps, $this->getIblockId(), $arProductId);

            foreach ($properties as $elementId => $prop) {
                if (is_array($prop)) {
                    $this->products[$elementId]['properties'] = $prop;
                }
            }
        }

        unset($arSelect, $iterator, $prod, $addProps, $arProductId, $props, $prop);
    }

    public function getOffersIblockId(): ?int
    {
        if (!isset($this->offersCatalogId)) {
            $offersCatalogId = (\CCatalog::GetList(
                [],
                ['IBLOCK_ID' => $this->getIblockId()],
                false,
                [],
                ['ID', 'IBLOCK_ID', 'OFFERS_IBLOCK_ID']
            )->Fetch())['OFFERS_IBLOCK_ID'];

            $this->offersCatalogId = $offersCatalogId;
        }

        return $this->offersCatalogId;
    }

    /**
     * Загружает список торговых предложений
     */
    public function loadOffers()
    {
        $offersCatalogId = $this->getOffersIblockId();

        if ($offersCatalogId <= 0) {
            return;
        }

        if (count($this->products) === 0) {
            return;
        }

        $arSelect = [
            'ID',
            'IBLOCK_ID',
            'NAME',
            'DETAIL_PAGE_URL',
            'CATALOG_GROUP_' . $this->getBasePriceId(),
            'IBLOCK_SECTION_ID',
            'DETAIL_PICTURE',
            'PREVIEW_PICTURE',
            'ACTIVE'
        ];

        $this->offers = \CCatalogSKU::getOffersList(
            array_keys($this->products),
            $this->getIblockId(),
            [],
            $arSelect
        );

        $arOfferId = [];
        foreach ($this->offers as $productId => &$offers) {
            foreach ($offers as &$offer) {
                $arOfferId[] = $offer['ID'];

                $offer['prices'] = \CCatalogProduct::GetOptimalPrice($offer['ID'], 1, [], 'N', [],  $this->getLid());

                $offer['properties'] = [];

                if ($offer['prices']['RESULT_PRICE']['PRICE_TYPE_ID'] !== $this->getBasePriceId()) {
                    $offer['prices']['RESULT_PRICE'] = $this->getResultPrice($offer);
                }

                if (array_key_exists($productId, $this->products)) {
                    $offer['ACTIVE'] = ($this->products[$productId]['ACTIVE'] == 'N')
                        ? $this->products[$productId]['ACTIVE']
                        : $offer['ACTIVE'];

                    $offer['CATALOG_AVAILABLE'] = ($this->products[$productId]['CATALOG_AVAILABLE'] == 'N')
                        ? $this->products[$productId]['CATALOG_AVAILABLE']
                        : $offer['CATALOG_AVAILABLE'];
                }
            }
        }

        if (!empty($addProps = $this->getOffersPropertyCode()) && !empty($arOfferId)) {
            $properties = $this->getProperties($addProps, $offersCatalogId, $arOfferId);

            foreach ($this->offers as &$offers) {
                foreach ($offers as $offerId => &$offer) {
                    if (!empty($properties[$offerId]) && is_array($properties[$offerId])) {
                        $offer['properties'] = $properties[$offerId];
                    }
                }
            }
        }

        unset($arOfferId, $addProps, $properties, $arSelect, $offersCatalogId);
    }

    /**
     * Получение значения свойст товаров
     * @param array $propertyCode - массив кодов свойств
     * @param int $iblockId - ID инфоблока
     * @param array $productIds - ID товаров
     * @return array|false
     */
    public function getProperties($propertyIds, $iblockId, &$productIds)
    {
        $elementIndex = array_combine($productIds, $productIds);

        \CIBlockElement::GetPropertyValuesArray(
            $elementIndex,
            $iblockId,
            ['ID' => $elementIndex],
            ['ID' => $propertyIds],
            ['GET_RAW_DATA' => 'Y']
        );

        return $elementIndex;
    }

    /**
     * Возвращает информацию по инфоблоку
     * @param int $iblockId
     * @return array|false
     */
    public static function getIblockInfo($iblockId)
    {
        return \CIBlock::GetArrayByID($iblockId);
    }

    /**
     * @param string|null $lid
     * @return void
     */
    public function setLid(?string $lid): void
    {
        if ($lid) {
            $this->lid = $lid;
            return;
        }

        $info = $this->getIblockInfo($this->getIblockId());
        $this->lid = $info['LID'];
    }

    /**
     * Возвращает ID сайта
     * @return string
     */
    public function getLid()
    {
        return $this->lid;
    }


    /**
     * @param int|null $priceId
     * @return void
     */
    public function setBasePriceId(?int $priceId)
    {
        if ($priceId) {
            $this->basePriceId = $priceId;
            return;
        }

        $priceGroup = \Bitrix\Catalog\GroupTable::getList([
            'filter' =>  ['BASE' => 'Y'],
            'select' => ['ID'],
            'limit' => 1
        ])->fetch();

        $this->basePriceId = $priceGroup['ID'];
    }

    /**
     * Возвращает ID группы базовой цены
     * @return int
     */
    public function getBasePriceId(): int
    {
        return $this->basePriceId;
    }

    /**
     * Возвращает размер чанка для товаров
     * @return int
     */
    public function getStepSize()
    {
        return $this->stepSize;
    }

    /**
     * @param $iblockId
     * @return void
     */
    public function setIblockId($iblockId): void
    {
        $this->iblockId = (int)$iblockId;
    }

    /**
     * Возвращает ID инфоблока
     * @return int
     */
    public function getIblockId(): int
    {
        return $this->iblockId;
    }

    public function setProductCount(): void
    {
        $iter = ElementTable::getList([
            'filter' => [
                '=WF_STATUS_ID' => 1,
                '=WF_PARENT_ELEMENT_ID' => null,
                '=IBLOCK_ID' => $this->getIblockId()
            ],
            'select' => ['ID']
        ]);

        $this->productCount = $iter->getSelectedRowsCount();

        unset($iter);
    }

    /**
     * Возвращает общее количество товаров
     * @return int
     */
    public function getProductCount(): int
    {
        return $this->productCount;
    }


    /**
     * @param array|null $properties
     * @return void
     */
    public function setCatalogPropertyCode(?array $properties): void
    {
        if (!$properties) {
            return;
        }

        $this->catalogPropertyCode = array_map(static function ($prop) {
            return str_replace('PROPERTY_', '', $prop);
        }, $properties);
    }

    /**
     * Возвращает массив кодов свойств для товаров
     * @return array
     */
    public function getCatalogPropertyCode()
    {
        return $this->catalogPropertyCode;
    }

    /**
     * @param $properties
     * @return void
     */
    public function setOffersPropertyCode($properties)
    {
        if (!$properties) {
            return;
        }

        $this->offersPropertyCode = array_map(static function ($prop) {
            return str_replace('PROPERTY_', '', $prop);
        }, $properties);
    }

    /**
     * Возвращает массив кодов свойств для торговых предложений
     * @return array
     */
    public function getOffersPropertyCode()
    {
        return $this->offersPropertyCode;
    }

    public function getResultPrice($element)
    {
        $arResultPrices = $element['prices']['RESULT_PRICE'];

        $iterator = PriceTable::getList([
            'select' => ['CATALOG_GROUP_ID', 'PRICE'],
            'filter' => [
                '=PRODUCT_ID' => (int)$element['ID'],
                '=CATALOG_GROUP_ID' => $this->getBasePriceId()
            ],
        ]);

        if ($price = $iterator->fetch()) {
            $arResultPrices['BASE_PRICE'] = roundEx($price['PRICE'], 2);
            $arResultPrices['UNROUND_BASE_PRICE'] = $price['PRICE'];
        }

        return $arResultPrices;
    }


}