<?php
defined('B_PROLOG_INCLUDED') and (B_PROLOG_INCLUDED === true) or die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;

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
        $this->installAgents();
    }

    public function DoUninstall()
    {
        \Bitrix\Main\ModuleManager::unRegisterModule($this->MODULE_ID);
        $this->UnInstallEvents();
        $this->UnInstallDB();
        $this->UnInstallFiles();
        $this->unInstallAgents();
    }

    public function InstallDB()
    {

    }

    public function UnInstallDB()
    {

    }

    public function InstallEvents()
    {
        \Bitrix\Main\EventManager::getInstance()->registerEventHandler(
            'main',
            'OnAfterUserAdd',
            $this->MODULE_ID,
            \Mindbox\Loyalty\Events\CustomerEvent::class,
            'onAfterUserAdd'
        );

        \Bitrix\Main\EventManager::getInstance()->registerEventHandler(
            'main',
            'OnAfterUserAuthorize',
            $this->MODULE_ID,
            \Mindbox\Loyalty\Events\CustomerEvent::class,
            'onAfterUserAuthorize'
        );

        \Bitrix\Main\EventManager::getInstance()->registerEventHandler(
            'main',
            'OnAfterUserUpdate',
            $this->MODULE_ID,
            \Mindbox\Loyalty\Events\CustomerEvent::class,
            'onAfterUserUpdate'
        );
    }

    public function UnInstallEvents()
    {
        \Bitrix\Main\EventManager::getInstance()->unRegisterEventHandler(
            'main',
            'OnAfterUserAdd',
            $this->MODULE_ID,
            \Mindbox\Loyalty\Events\CustomerEvent::class,
            'onAfterUserAdd'
        );

        \Bitrix\Main\EventManager::getInstance()->unRegisterEventHandler(
            'main',
            'OnAfterUserAuthorize',
            $this->MODULE_ID,
            \Mindbox\Loyalty\Events\CustomerEvent::class,
            'onAfterUserAuthorize'
        );

        \Bitrix\Main\EventManager::getInstance()->unRegisterEventHandler(
            'main',
            'OnAfterUserUpdate',
            $this->MODULE_ID,
            \Mindbox\Loyalty\Events\CustomerEvent::class,
            'onAfterUserUpdate'
        );
    }

    public function installAgents(): void
    {
        $now = new DateTime();
        CAgent::AddAgent(
            "\Mindbox\Loyalty\Feed\AgentRunner::run();",
            $this->MODULE_ID,
            "N",
            86400,
            $now,
            "Y",
            $now,
            30
        );
    }

    public function unInstallAgents(): void
    {
        CAgent::RemoveAgent(
            "\Mindbox\Loyalty\Feed\AgentRunner::run();",
            $this->MODULE_ID
        );
    }

    public function InstallFiles()
    {
    }

    public function UnInstallFiles()
    {
    }
}
