<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Engine\ActionFilter\Authentication;
use Bitrix\Main\Loader;
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Errorable;
use Bitrix\Main\ErrorableImplementation;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\Compatible\DiscountCompatibility;
use Bitrix\Sale\DiscountCouponsManager;
use Mindbox\Loyalty\Support\SessionStorage;


class MindboxPromocode extends CBitrixComponent implements Controllerable, Errorable
{
    use ErrorableImplementation;

    /**
     * Обязательные модули для загрузки
     *
     * @var string[]
     */
    private static $moduleLoaded = [
        'mindbox.loyalty',
        'sale'
    ];


    public function __construct($component = null)
    {
        if (!$this->checkModules()) {
            return;
        }

        $this->errorCollection = new ErrorCollection();
        parent::__construct($component);
    }

    /**
     * Конфигурируем методы Action
     *
     * @return array[]
     */
    public function configureActions()
    {
        return [
            'get' => [
                'prefilters' => [
                    new \Bitrix\Main\Engine\ActionFilter\Csrf(),
                    new \Bitrix\Main\Engine\ActionFilter\HttpMethod([
                        \Bitrix\Main\Engine\ActionFilter\HttpMethod::METHOD_POST
                    ])
                ],
                'postfilters' => []
            ],
            'apply' => [
                'prefilters' => [
                    new \Bitrix\Main\Engine\ActionFilter\Csrf(),
                    new \Bitrix\Main\Engine\ActionFilter\HttpMethod([
                        \Bitrix\Main\Engine\ActionFilter\HttpMethod::METHOD_POST
                    ])
                ],
                'postfilters' => []
            ],
            'remove' => [
                'prefilters' => [
                    new \Bitrix\Main\Engine\ActionFilter\Csrf(),
                    new \Bitrix\Main\Engine\ActionFilter\HttpMethod([
                        \Bitrix\Main\Engine\ActionFilter\HttpMethod::METHOD_POST
                    ])
                ],
                'postfilters' => []
            ],
            'clear' => [
                'prefilters' => [
                    new \Bitrix\Main\Engine\ActionFilter\Csrf(),
                    new \Bitrix\Main\Engine\ActionFilter\HttpMethod([
                        \Bitrix\Main\Engine\ActionFilter\HttpMethod::METHOD_POST
                    ])
                ],
                'postfilters' => []
            ]
        ];
    }

    /**
     * @return mixed
     */
    public function getAction()
    {
        $result = array_merge(self::getAppliedBitrixPromocode(), SessionStorage::getInstance()->getPromocode());

        return $result;
    }

    /**
     * Операция на применение промокода
     *
     * @param $coupon
     */
    public function applyAction(string $coupon)
    {
        $coupon = htmlspecialchars_decode(trim($coupon));

        if (!empty($coupon)) {
            DiscountCouponsManager::init();
            $couponInfo = DiscountCouponsManager::getData($coupon, true); // получаем информацио о купоне

            if ($couponInfo['ID'] > 0) {
                DiscountCouponsManager::add($coupon);

                $basket = \Bitrix\Sale\Basket::loadItemsForFUser(
                    \Bitrix\Sale\Fuser::getId(),
                    \Bitrix\Main\Context::getCurrent()->getSite()
                );
                $discount = \Bitrix\Sale\Discount::buildFromBasket($basket, new \Bitrix\Sale\Discount\Context\Fuser($basket->getFUserId(true)));
                $refreshStrategy = \Bitrix\Sale\Basket\RefreshFactory::create(\Bitrix\Sale\Basket\RefreshFactory::TYPE_FULL);
                $result = $basket->refresh($refreshStrategy);
                $discount->calculate();
            } else {
                SessionStorage::getInstance()->setPromocode($coupon);
            }
        }
    }

    /**
     * Операция на применение промокода
     *
     * @param $coupon
     */
    public function removeAction(string $coupon)
    {
        DiscountCouponsManager::init(DiscountCouponsManager::MODE_CLIENT);
        $coupon = htmlspecialchars_decode(trim($coupon));

        if (!empty($coupon)) {
            $arCoupons = DiscountCouponsManager::get(true, ['COUPON' => $coupon], true, true);
            if (!empty($arCoupons)) {
                $arCoupon = array_shift($arCoupons);
                DiscountCouponsManager::delete($coupon);

                $basket = \Bitrix\Sale\Basket::loadItemsForFUser(
                    \Bitrix\Sale\Fuser::getId(),
                    \Bitrix\Main\Context::getCurrent()->getSite()
                );
                $discount = \Bitrix\Sale\Discount::buildFromBasket($basket, new \Bitrix\Sale\Discount\Context\Fuser($basket->getFUserId(true)));
                $refreshStrategy = \Bitrix\Sale\Basket\RefreshFactory::create(\Bitrix\Sale\Basket\RefreshFactory::TYPE_FULL);
                $result = $basket->refresh($refreshStrategy);
                $discount->calculate();
            } else {
                SessionStorage::getInstance()->unsetPromocode($coupon);
            }
        }
    }

    /**
     * Операция отмены применения промокода
     *
     */
    public function clearAction()
    {
        DiscountCouponsManager::init(DiscountCouponsManager::MODE_CLIENT);
        DiscountCouponsManager::clear(true);
        SessionStorage::getInstance()->clearField(SessionStorage::PROMOCODE);
    }

    private static function getAppliedBitrixPromocode(): ?array
    {
        DiscountCouponsManager::init();

        $basket = \Bitrix\Sale\Basket::loadItemsForFUser(
            \Bitrix\Sale\Fuser::getId(),
            \Bitrix\Main\Context::getCurrent()->getSite()
        );
        $discount = \Bitrix\Sale\Discount::buildFromBasket($basket, new \Bitrix\Sale\Discount\Context\Fuser($basket->getFUserId(true)));
        $refreshStrategy = \Bitrix\Sale\Basket\RefreshFactory::create(\Bitrix\Sale\Basket\RefreshFactory::TYPE_FULL);
        $basket->refresh($refreshStrategy);
        $discount->calculate();

        $coupons = DiscountCouponsManager::get(true, [], true, true);

        if (empty($coupons)) {
            return [];
        }

        $result = [];
        foreach ($coupons as $coupon) {
            if ($coupon['STATUS'] === DiscountCouponsManager::STATUS_NOT_FOUND || $coupon['STATUS'] === DiscountCouponsManager::STATUS_FREEZE) {
                $coupon['JS_STATUS'] = 'BAD';
                $coupon['ERROR_TEXT'] = Loc::getMessage('MINDBOX_LOYALTY_COUPON_NOT_FOUND');
            } elseif ($coupon['STATUS'] === DiscountCouponsManager::STATUS_NOT_APPLYED || $coupon['STATUS'] === DiscountCouponsManager::STATUS_ENTERED) {
                $coupon['JS_STATUS'] = 'ENTERED';
                $coupon['ERROR_TEXT'] = Loc::getMessage('MINDBOX_LOYALTY_COUPON_CAN_NOT_BE_USER');
            } else {
                $coupon['JS_STATUS'] = 'APPLIED';
            }

            $result[$coupon['COUPON']] = [
                'value' => $coupon['COUPON'],
                'apply' => $coupon['JS_STATUS'] === 'APPLIED',
                'error' => $coupon['ERROR_TEXT'] ?? '',
            ];
        }

        return $result;
    }

    /**
     * Метод инициализации компонента

     */
    public function executeComponent()
    {
        $this->arResult['coupons'] = $this->getAction();
        $this->includeComponentTemplate();
    }

    /**
     * Метод подключения модулей
     *
     * @return bool
     */
    protected function checkModules(): bool
    {
        foreach (self::$moduleLoaded as $module) {
            if (!Loader::includeModule($module)) {
                ShowError(sprintf('Module %s not loaded', $module));

                return false;
            }
        }

        return true;
    }
}
