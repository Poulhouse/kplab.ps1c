<?php
defined('B_PROLOG_INCLUDED') and (B_PROLOG_INCLUDED === true) or die();

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$aMenu = array(
    array(
        'parent_menu' => 'global_menu_content',
        'sort' => 400,
        'text' => "название",
        'title' => "Название2",
        'url' => 'ps1c_index.php',
        'items_id' => 'menu_references',
        'items' => array(
            array(
                'text' => "Трпр",
                'url' => 'ps1c_index.php?param1=paramval&lang='.LANGUAGE_ID,
                'more_url' => array('ps1c_index.php?param1=paramval&lang='.LANGUAGE_ID),
                'title' => "Уруруру",
            ),
        ),
    ),
);

return $aMenu;
