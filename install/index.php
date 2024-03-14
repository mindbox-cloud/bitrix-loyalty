<?php
defined('B_PROLOG_INCLUDED') and (B_PROLOG_INCLUDED === true) or die();

use Bitrix\Main\Localization\Loc;

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

    }

    public function UnInstallDB()
    {

    }

    public function InstallEvents()
    {
    }

    public function UnInstallEvents()
    {
    }

    public function InstallFiles()
    {
    }

    public function UnInstallFiles()
    {
    }
}
