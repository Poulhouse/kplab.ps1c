<?
defined('B_PROLOG_INCLUDED') and (B_PROLOG_INCLUDED === true) or die();

use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\EventManager;
use KPlab\ps1C\ExampleTable;

Loc::loadMessages(__FILE__);

if (class_exists('kplab_ps1c')) {
    return;
}

Class kplab_ps1c extends CModule
{
    /** @var string */
    public $MODULE_ID;

    /** @var string */
    public $MODULE_VERSION;

    /** @var string */
    public $MODULE_VERSION_DATE;

    /** @var string */
    public $MODULE_NAME;

    /** @var string */
    public $MODULE_DESCRIPTION;

    /** @var string */
    public $MODULE_GROUP_RIGHTS;

    /** @var string */
    public $PARTNER_NAME;

    /** @var string */
    public $PARTNER_URI;


    public function __construct()
    {
        $this->MODULE_ID = 'kplab.ps1c';
        $this->MODULE_VERSION = '0.0.1';
        $this->MODULE_VERSION_DATE = '2022-05-29 18:36:00';
        $this->MODULE_NAME = Loc::getMessage('MODULE_NAME');
        $this->MODULE_DESCRIPTION = Loc::getMessage('MODULE_DESCRIPTION');
        $this->MODULE_GROUP_RIGHTS = 'N';
        $this->PARTNER_NAME = "kplab";
        $this->PARTNER_URI = "http://kplab.ru";
    }

    public function doInstall()
    {
        ModuleManager::registerModule($this->MODULE_ID);
        $this->installDB();
		$this->InstallEvents();
    }

	public function InstallEvents(){
		EventManager::getInstance()->registerEventHandler("catalog","OnSuccessCatalogImport1C",$this->MODULE_ID,"KPlab\ps1C\ExampleTable","OnAfterAddUpdate");
        EventManager::getInstance()->registerEventHandler("catalog","OnSuccessCatalogImportHL",$this->MODULE_ID,"KPlab\ps1C\ExampleTable","OnAfterAddUpdate");
		return false;
	}
	public function unInstallEvents(){
		EventManager::getInstance()->unRegisterEventHandler("catalog","OnSuccessCatalogImport1C",$this->MODULE_ID,"KPlab\ps1C\ExampleTable","OnAfterAddUpdate");
        EventManager::getInstance()->unRegisterEventHandler("catalog","OnSuccessCatalogImportHL",$this->MODULE_ID,"KPlab\ps1C\ExampleTable","OnAfterAddUpdate");
		return false;
	}

    public function doUninstall()
    {
        $this->uninstallDB();
		$this->unInstallEvents();
        ModuleManager::unregisterModule($this->MODULE_ID);

    }

    public function installDB()
    {
        if (Loader::includeModule($this->MODULE_ID)) {
            ExampleTable::getEntity()->createDbTable();
        }
    }

    public function uninstallDB()
    {
        if (Loader::includeModule($this->MODULE_ID)) {
            $connection = Application::getInstance()->getConnection();
            $connection->dropTable(ExampleTable::getTableName());
        }
    }
}
?>

