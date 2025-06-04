<?php

namespace Mindbox\Loyalty\Feed;

use Bitrix\Catalog\PriceTable;
use Bitrix\Main\Text\Encoding;
use domDocument;

class YmlFeedMindbox
{
    const DESCRIPTION_TEXT_LENGTH = 3000;

    protected $lid = '';

    protected $protocol = 'https://';

    protected array $products = [];
    protected array $offers = [];

    protected string $serverName;
    protected RepositoryInterface $catalogRepository;
    protected $feedPath;

    /**
     * @param RepositoryInterface $catalogRepository
     * @return void
     */
    public function setCatalogRepository(RepositoryInterface $catalogRepository): void
    {
        $this->catalogRepository = $catalogRepository;
    }

    public function setServerName(string $serverName): void
    {
        $this->serverName = $serverName;
    }

    public function getServerName(): string
    {
        return $this->serverName;
    }

    /**
     * @return void
     * @throws \DOMException
     */
    public function generateYml(): void
    {
        $dom = new domDocument('1.0', 'utf-8');
        $root = $dom->createElement('yml_catalog');
        $root->setAttribute('date', date('Y-m-d H:i'));
        $dom->appendChild($root);

        $shop = $dom->createElement('shop');
        $shop = $root->appendChild($shop);

        $name = self::yandexText2xml($this->getSiteName());

        $siteName = $dom->createElement('name', $name);
        $shop->appendChild($siteName);

        $companyName = $dom->createElement('company', $name);
        $shop->appendChild($companyName);

        $siteUrl = $dom->createElement('url', self::yandexText2xml($this->getProtocol() . $this->getServerName()));
        $shop->appendChild($siteUrl);

        $currenciesDom = $dom->createElement('currencies');
        $currenciesDom = $shop->appendChild($currenciesDom);

        $currencies = $this->catalogRepository->getCurrencies();
        foreach ($currencies as $currency) {
            $currencyDom = $dom->createElement('currency');
            $currencyDom->setAttribute('id', self::yandexText2xml($currency['CURRENCY']));
            $currencyDom->setAttribute('rate', self::yandexText2xml((int)$currency['AMOUNT']));
            $currenciesDom->appendChild($currencyDom);
        }

        $categoriesDom = $dom->createElement('categories');
        $categoriesDom = $shop->appendChild($categoriesDom);

        $categories = $this->catalogRepository->getCategories();
        foreach ($categories as $category) {
            $categoryDom = $dom->createElement('category', self::yandexText2xml($category['NAME']));
            $categoryDom->setAttribute('id', Helper::getSectionCode($category['ID']));

            if (isset($category['IBLOCK_SECTION_ID']) && !empty($category['IBLOCK_SECTION_ID'])) {
                $categoryDom->setAttribute('parentId', Helper::getSectionCode($category['IBLOCK_SECTION_ID']));
            }

            $categoriesDom->appendChild($categoryDom);
        }

        $offers = $dom->createElement('offers');
        $offers = $shop->appendChild($offers);

        $products = $this->catalogRepository->getProducts();
        $articleProperty = $this->catalogRepository->getArticleProperty();
        $brandProperty = $this->catalogRepository->getBrandProperty();

        foreach ($products as $productsChunk) {
            $this->products = $productsChunk['products'];
            $this->offers = $productsChunk['offers'];

            foreach ($this->offers as $prodId => $ofrs) {
                foreach ($ofrs as $ofr) {
                    $offer = $dom->createElement('offer');

                    $offer->setAttribute('group_id', Helper::getElementCode($this->products[$prodId]['ID']));
                    $offer->setAttribute('id', Helper::getElementCode($ofr['ID']));

                    $available = ($ofr['CATALOG_AVAILABLE'] === 'Y' && $ofr['ACTIVE'] === 'Y') ? 'true' : 'false';
                    $offer->setAttribute('available', $available);

                    unset($available);

                    $offer = $offers->appendChild($offer);
                    if (!empty($ofr['NAME'])) {
                        $name = self::yandexText2xml($ofr['NAME']);
                    } else {
                        $name = self::yandexText2xml($this->products[$prodId]['NAME']);
                    }

                    $offerName = $dom->createElement('name', $name);
                    $offer->appendChild($offerName);

                    // description
                    if (!empty($ofr['~DETAIL_TEXT'])) {
                        $description = TruncateText($ofr['~DETAIL_TEXT'], self::DESCRIPTION_TEXT_LENGTH);
                    } else {
                        $description = TruncateText($this->products[$prodId]['~DETAIL_TEXT'], self::DESCRIPTION_TEXT_LENGTH);
                    }

                    if (empty($description)) {
                        if (!empty($ofr['~PREVIEW_TEXT'])) {
                            $description = TruncateText($ofr['~PREVIEW_TEXT'], self::DESCRIPTION_TEXT_LENGTH);
                        } else {
                            $description = TruncateText($this->products[$prodId]['~PREVIEW_TEXT'], self::DESCRIPTION_TEXT_LENGTH);
                        }
                    }

                    if (!empty($description)) {
                        $cdataDescription = $dom->createCDATASection($description);
                        $offerDescription = $dom->createElement('description');
                        $offerDescription->appendChild($cdataDescription);
                        $offer->appendChild($offerDescription);
                    }

                    // url
                    if ($this->products[$prodId]['DETAIL_PAGE_URL']) {
                        $offerUrl = $dom->createElement('url', self::yandexText2xml($this->getProtocol() . $this->getServerName() . $this->products[$prodId]['DETAIL_PAGE_URL']));
                        $offer->appendChild($offerUrl);
                    }

                    // prices
                    if (!empty($ofr['prices']) && $ofr['prices']['RESULT_PRICE']['BASE_PRICE'] !== $ofr['prices']['RESULT_PRICE']['DISCOUNT_PRICE']) {
                        $offerPrice = $dom->createElement('price', $ofr['prices']['RESULT_PRICE']['DISCOUNT_PRICE']);
                        $offer->appendChild($offerPrice);
                        $oldPrice = $dom->createElement('oldprice', $ofr['prices']['RESULT_PRICE']['BASE_PRICE']);
                        $offer->appendChild($oldPrice);
                    } else {
                        $offerPrice = $dom->createElement('price', $ofr['CATALOG_PRICE_' . $this->getBasePriceId()]);
                        $offer->appendChild($offerPrice);
                    }

                    $offerCurrencyId = $dom->createElement('currencyId', self::yandexText2xml($ofr['CATALOG_CURRENCY_' . $this->getBasePriceId()]));
                    $offer->appendChild($offerCurrencyId);

                    // categories
                    $productCategoryList = $this->getProductGroups((int)$prodId);
                    foreach ($productCategoryList as $productCategoryId) {
                        $offerCategoryId = $dom->createElement('categoryId', Helper::getSectionCode($productCategoryId));
                        $offer->appendChild($offerCategoryId);
                    }

                    // picture
                    $img = $ofr['DETAIL_PICTURE'] ?: $ofr['PREVIEW_PICTURE'];
                    if (!empty($img)) {
                        $url = $this->getPictureUrl($img);
                    } else {
                        $img = $this->products[$prodId]['DETAIL_PICTURE'] ?: $this->products[$prodId]['PREVIEW_PICTURE'];
                        $url = $this->getPictureUrl($img);
                    }
                    if ($url) {
                        $offerPicture = $dom->createElement('picture', self::yandexText2xml($url));
                        $offer->appendChild($offerPicture);
                    }

                    // properties
                    $ofr['properties'] = array_merge($ofr['properties'], $this->products[$prodId]['properties']);

                    foreach ($ofr['properties'] as $property) {
                        if (empty($property['VALUE'])) {
                            continue;
                        }

                        if (is_array($property['VALUE'])) {
                            $property['VALUE'] = implode('|', $property['VALUE']);
                        }

                        switch ($property['ID']) {
                            case $articleProperty:
                                $article = $dom->createElement('vendorCode', self::yandexText2xml($property['VALUE']));
                                $offer->appendChild($article);
                                break;
                            case $brandProperty:
                                $brand = $dom->createElement('vendor', self::yandexText2xml($property['VALUE']));
                                $offer->appendChild($brand);
                                break;
                        }
                    }

                    foreach ($ofr['properties'] as $property) {
                        if ($property['ID'] == $articleProperty || $property['ID'] == $brandProperty) {
                            continue;
                        }

                        if (!empty($property['VALUE'])) {
                            if (is_array($property['VALUE'])) {
                                $property['VALUE'] = implode('|', $property['VALUE']);
                            }

                            if (empty($property['CODE'])) {
                                $property['CODE'] = $property['XML_ID'];
                            }

                            $property['CODE'] = str_replace('_', '', $property['CODE']);
                            $param = $dom->createElement('param', self::yandexText2xml($property['VALUE']));
                            $param->setAttribute('name', $property['CODE']);

                            $offer->appendChild($param);
                        }
                    }
                }

                if (array_key_exists($prodId, $this->products)) {
                    unset($this->products[$prodId]);
                }
            }

            foreach ($this->products as $product) {
                $offer = $dom->createElement('offer');
                $offer->setAttribute('id', Helper::getElementCode($product['ID']));

                $available = ($product['CATALOG_AVAILABLE'] === 'Y' && $product['ACTIVE'] === 'Y') ? 'true' : 'false';

                $offer->setAttribute('available', $available);
                unset($available);

                $offer = $offers->appendChild($offer);
                $offerName = $dom->createElement('name', self::yandexText2xml($product['NAME']));
                $offer->appendChild($offerName);

                // description
                if (!empty($product['PREVIEW_TEXT'])) {
                    $offerDescription = $dom->createElement('description', self::yandexText2xml($product['PREVIEW_TEXT']));
                    $offer->appendChild($offerDescription);
                }

                // url
                if ($product['DETAIL_PAGE_URL']) {
                    $offerUrl = $dom->createElement('url', self::yandexText2xml($this->getProtocol() . $this->getServerName() . $product['DETAIL_PAGE_URL']));
                    $offer->appendChild($offerUrl);
                }

                if (!empty($product['prices']) && $product['prices']['RESULT_PRICE']['BASE_PRICE'] !== $product['prices']['RESULT_PRICE']['DISCOUNT_PRICE']) {
                    $offerPrice = $dom->createElement('price', $product['prices']['RESULT_PRICE']['DISCOUNT_PRICE']);
                    $offer->appendChild($offerPrice);
                    $oldPrice = $dom->createElement('oldprice', $product['prices']['RESULT_PRICE']['BASE_PRICE']);
                    $offer->appendChild($oldPrice);
                } else {
                    $offerPrice = $dom->createElement('price', $product['CATALOG_PRICE_' . $this->getBasePriceId()]);
                    $offer->appendChild($offerPrice);
                }

                $offerCurrencyId = $dom->createElement('currencyId', self::yandexText2xml($product['CATALOG_CURRENCY_' . $this->getBasePriceId()]));
                $offer->appendChild($offerCurrencyId);

                // category
                $productCategoryList = $this->getProductGroups((int)$product['ID']);

                foreach ($productCategoryList as $productCategoryId) {
                    $offerCategoryId = $dom->createElement('categoryId', Helper::getSectionCode($productCategoryId));
                    $offer->appendChild($offerCategoryId);
                }

                // picture
                $img = $product['DETAIL_PICTURE'] ?: $product['PREVIEW_PICTURE'];
                $url = $this->getPictureUrl($img);
                if ($url) {
                    $offerPicture = $dom->createElement('picture', self::yandexText2xml($url));
                    $offer->appendChild($offerPicture);
                }

                // property
                foreach ($product['properties'] as $property) {
                    if (empty($property['VALUE'])) {
                        continue;
                    }

                    if (is_array($property['VALUE'])) {
                        $property['VALUE'] = implode('|', $property['VALUE']);
                    }

                    switch ($property['ID']) {
                        case $articleProperty:
                            $article = $dom->createElement('vendorCode', self::yandexText2xml($property['VALUE']));
                            $offer->appendChild($article);
                            break;
                        case $brandProperty:
                            $brand = $dom->createElement('vendor', self::yandexText2xml($property['VALUE']));
                            $offer->appendChild($brand);
                            break;
                    }
                }
                foreach ($product['properties'] as $property) {
                    if ($property['ID'] == $articleProperty || $property['ID'] == $brandProperty) {
                        continue;
                    }

                    if (!empty($property['VALUE'])) {
                        if (is_array($property['VALUE'])) {
                            $property['VALUE'] = implode('|', $property['VALUE']);
                        }

                        if (empty($property['CODE'])) {
                            $property['CODE'] = $property['XML_ID'];
                        }

                        $property['CODE'] = str_replace('_', '', $property['CODE']);
                        $param = $dom->createElement('param', self::yandexText2xml($property['VALUE']));
                        $param->setAttribute('name', $property['CODE']);

                        $offer->appendChild($param);
                    }
                }
            }
        }

        $dom->save($_SERVER['DOCUMENT_ROOT'] . $this->feedPath);
    }

    public function setProtocol(bool $enableHttps): void
    {
        $this->protocol = $enableHttps === true ? 'https://' : 'http://';
    }

    /**
     * Получает протокол.
     * @return string
     */
    public function getProtocol()
    {
        return $this->protocol;
    }

    /**
     * Возвращает список категорий, к которому принадлежит элемент
     * @param int $productId
     * @return array
     */
    public function getProductGroups(int $productId): array
    {
        return $this->catalogRepository->getProductGroups($productId);
    }

    /**
     * Возвращает путь до изображения от корня
     * @param string $id id изображения
     * @return string
     */
    protected function getPictureUrl($id): ?string
    {
        if (empty($id)) {
            return null;
        }

        $pictureFile = \CFile::GetFileArray($id);

        if (empty($pictureFile)) {
            return null;
        }

        if (strncmp($pictureFile['SRC'], '/', 1) == 0) {
            $picturePath = $this->getProtocol() . $this->getServerName() . \Bitrix\Main\Web\Uri::urnEncode($pictureFile['SRC'], 'utf-8');
        } else {
            $picturePath = $pictureFile['SRC'];
        }

        return $picturePath;
    }

    /**
     * Возвращает название сайта
     * @return string
     */
    public function getSiteName(): string
    {
        $siteIterator = \Bitrix\Main\SiteTable::query()
            ->addSelect('SITE_NAME')
            ->addFilter('LID', $this->lid)
            ->exec();

        $siteName = null;
        if ($site = $siteIterator->fetch()) {
            $siteName = $site['SITE_NAME'];
        }

        return !empty($siteName) ? $siteName : 'sitename';
    }

    private static function yandexText2xml($text, $bHSC = true, $bDblQuote = false): string
    {
        $bHSC = true == $bHSC;
        $bDblQuote = true == $bDblQuote;

        if ($bHSC) {
            $text = htmlspecialcharsbx($text);
            if ($bDblQuote) {
                $text = str_replace('&quot;', '"', $text);
            }
        }

        $text = preg_replace('/[\x1-\x8\xB-\xC\xE-\x1F]/', '', $text);
        $text = str_replace("'", "&apos;", $text);
        $text = Encoding::convertEncoding($text, LANG_CHARSET, 'UTF-8');

        return $text;
    }

    /**
     * Возвращает ID группы базовой цены
     * @return int
     */
    public function getBasePriceId(): int
    {
        return $this->catalogRepository->getBasePriceId();
    }

    public function setFeedPath(string $path): void
    {
        $this->feedPath = $path;
    }
}
