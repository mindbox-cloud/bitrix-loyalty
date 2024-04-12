<?php
defined('B_PROLOG_INCLUDED') and (B_PROLOG_INCLUDED === true) or die();

use Bitrix\Main\Context;
use Bitrix\Main\Localization\Loc;
use Mindbox\Loyalty\Install\DiscountRuleInstaller;
use Mindbox\Loyalty\Install\OrderGroupPropertyInstaller;
use Mindbox\Loyalty\Install\OrderPropertyInstaller;

if (class_exists('mindbox.loyalty')) {
    return;
}

require  __DIR__ . '/../include.php';

Loc::loadMessages(__FILE__);

class mindbox_loyalty extends CModule
{
    public $MODULE_ID = 'mindbox.loyalty';
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;
    public $PARTNER_NAME;
    public $PARTNER_URI;

    public function __construct()
    {
        $arModuleVersion = [];

        include(dirname(__FILE__) . '/version.php');

        $this->MODULE_VERSION = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];

        $this->MODULE_NAME = Loc::getMessage('MINDBOX_LOYALTY_MODULE_NAME');
        $this->MODULE_DESCRIPTION = Loc::getMessage('MINDBOX_LOYALTY_MODULE_DESCRIPTION');

        $this->PARTNER_NAME = Loc::getMessage('MINDBOX_LOYALTY_PARTNER_NAME');
        $this->PARTNER_URI = Loc::getMessage('MINDBOX_LOYALTY_PARTNER_URI');
    }

    public function DoInstall()
    {
        \Bitrix\Main\ModuleManager::registerModule($this->MODULE_ID);
        $this->InstallDB();
        $this->InstallFiles();
        $this->InstallEvents();
    }

    public function DoUninstall()
    {
        \Bitrix\Main\ModuleManager::unRegisterModule($this->MODULE_ID);
        $this->UnInstallEvents();
        $this->UnInstallDB();
        $this->UnInstallFiles();

    }

    public function InstallDB()
    {
        $discountTableInstance = \Bitrix\Main\ORM\Entity::getInstance(\Mindbox\Loyalty\Discount\BasketDiscountTable::class);

        if (!$discountTableInstance->getConnection()->isTableExists($discountTableInstance->getDBTableName())) {
            $discountTableInstance->createDbTable();
        }

        $siteId = $this->getCurrentSiteId();
        (new OrderGroupPropertyInstaller($siteId))->up();
        (new OrderPropertyInstaller($siteId))->up();
        (new DiscountRuleInstaller($siteId))->up();
    }

    public function UnInstallDB()
    {
//        @todo Насчет удаления надо обговорить
//        $discountTableInstance = \Bitrix\Main\ORM\Entity::getInstance(\Mindbox\Loyalty\Discount\BasketDiscountTable::class);
//
//        $discountTableInstance->getConnection()
//            ->queryExecute("drop table if exists " . $discountTableInstance->getDBTableName());

        $iterSite = \Bitrix\Main\SiteTable::getList([
            'select' => ['*'],
        ]);

        foreach ($iterSite as $site) {
            (new OrderGroupPropertyInstaller($site['LID']))->down();
            (new OrderPropertyInstaller($site['LID']))->down();
            (new DiscountRuleInstaller($site['LID']))->down();
        }

    }

    public function InstallEvents()
    {
        \Bitrix\Main\EventManager::getInstance()->registerEventHandler(
            'main',
            'OnAfterUserAdd',
            $this->MODULE_ID,
            \Mindbox\Loyalty\Event\CustomerEvent::class,
            'onAfterUserAdd'
        );

        \Bitrix\Main\EventManager::getInstance()->registerEventHandler(
            'main',
            'OnAfterUserAuthorize',
            $this->MODULE_ID,
            \Mindbox\Loyalty\Event\CustomerEvent::class,
            'onAfterUserAuthorize'
        );

        \Bitrix\Main\EventManager::getInstance()->registerEventHandler(
            'main',
            'OnAfterUserUpdate',
            $this->MODULE_ID,
            \Mindbox\Loyalty\Event\CustomerEvent::class,
            'onAfterUserUpdate'
        );

        \Bitrix\Main\EventManager::getInstance()->registerEventHandler(
            'sale',
            'OnBeforeSaleOrderFinalAction',
            $this->MODULE_ID,
            \Mindbox\Loyalty\Event\OrderEvent::class,
            'onBeforeSaleOrderFinalAction'
        );
    }

    public function UnInstallEvents()
    {
        \Bitrix\Main\EventManager::getInstance()->unRegisterEventHandler(
            'main',
            'OnAfterUserAdd',
            $this->MODULE_ID,
            \Mindbox\Loyalty\Event\CustomerEvent::class,
            'onAfterUserAdd'
        );

        \Bitrix\Main\EventManager::getInstance()->unRegisterEventHandler(
            'main',
            'OnAfterUserAuthorize',
            $this->MODULE_ID,
            \Mindbox\Loyalty\Event\CustomerEvent::class,
            'onAfterUserAuthorize'
        );

        \Bitrix\Main\EventManager::getInstance()->unRegisterEventHandler(
            'main',
            'OnAfterUserUpdate',
            $this->MODULE_ID,
            \Mindbox\Loyalty\Event\CustomerEvent::class,
            'onAfterUserUpdate'
        );

        \Bitrix\Main\EventManager::getInstance()->unRegisterEventHandler(
            'sale',
            'OnBeforeSaleOrderFinalAction',
            $this->MODULE_ID,
            \Mindbox\Loyalty\Event\OrderEvent::class,
            'onBeforeSaleOrderFinalAction'
        );
    }

    public function InstallFiles()
    {
    }

    public function UnInstallFiles()
    {
    }

    protected function getCurrentSiteId()
    {
        if (Context::getCurrent()->getRequest()->get('site_id') !== null) {
            return Context::getCurrent()->getRequest()->get('site_id');
        }

        $host = (string) Context::getCurrent()->getRequest()->getHttpHost();
        $directory = (string) Context::getCurrent()->getRequest()->getRequestedPageDirectory();

        $site = \Bitrix\Main\SiteTable::getByDomain($host, $directory);

        if ($site !== null) {
            return $site['LID'];
        }

        $defaultSite = \Bitrix\Main\SiteTable::getList([
            'filter' => ['=ACTIVE' => 'Y', '=DEF' => 'Y'],
            'select' => ['LID'],
        ])->fetch();

        return $defaultSite['LID'];
    }
}
