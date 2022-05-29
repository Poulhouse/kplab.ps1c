<?php
defined('B_PROLOG_INCLUDED') and (B_PROLOG_INCLUDED === true) or die();

use Bitrix\Main\Loader;
use Bitrix\Main\EventManager;
use Bitrix\Highloadblock;
use Bitrix\Main\Entity;
use Bitrix\Sale\Internals;
use Bitrix\Sale\Internals\DiscountCouponTable;
use Bitrix\Main\Type\DateTime;

Loader::registerAutoLoadClasses('kplab.ps1c', array(
    // no thanks, bitrix, we better will use psr-4 than your class names convention
    'KPlab\Ps1C\ExampleTable' => 'lib/ExampleTable.php',
));

EventManager::getInstance()->addEventHandler('', 'PodarochnyeSertifikatyOnAfterAdd', function(){

    // do something when new user added
});