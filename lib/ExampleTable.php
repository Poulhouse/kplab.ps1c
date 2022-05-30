<?php
namespace KPlab\ps1C;

defined('B_PROLOG_INCLUDED') and (B_PROLOG_INCLUDED === true) or die();

use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Entity\Event;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\Entity\IntegerField;
use Bitrix\Main\Entity\StringField;
use Bitrix\Main\Entity\Validator;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use Bitrix\Main\Type\DateTime;
use Bitrix\Sale\Internals;
use Bitrix\Sale\Internals\DiscountCouponTable;

Loc::loadMessages(__FILE__);

class ExampleTable extends DataManager
{
    public static function getTableName()
    {
        return 'ps1c_example';
    }

    public static function getMap()
    {
        return array(
            new IntegerField('ID', array(
                'autocomplete' => true,
                'primary' => true,
                'title' => Loc::getMessage('ID'),
            )),
            new StringField('NAME', array(
                'required' => true,
                'title' => Loc::getMessage('NAME'),
                'default_value' => function () {
                    return Loc::getMessage('NAME_DEFAULT_VALUE');
                },
                'validation' => function () {
                    return array(
                        new Validator\Length(null, 255),
                    );
                },
            )),
            new StringField('IMAGE_SET', [
                'required' => false,
                'title' => Loc::getMessage('IMAGE_SET'),
                'fetch_data_modification' => function () {
                    return array(
                        function ($value) {
                            if (strlen($value)) {
                                return explode(',', $value);
                            }
                        },
                    );
                },
                'save_data_modification' => function () {
                    return array(
                        function ($value) {
                            if (is_array($value)) {
                                $value = array_filter($value, 'intval');

                                return implode(',', $value);
                            }
                        },
                    );
                },
            ]),
        );
    }

    public function OnAfterAddUpdate() {
        global $DB;
		Loader::includeModule('main');
		Loader::includeModule('sale');
		Loader::includeModule('catalog');
		Loader::includeModule('highloadblock');

        $stepInterval = (int) Option::get("catalog", "1C_INTERVAL", "-");
        $startTime = time();
        // Флаг импорта файла торговых предложений
        //$isOffers = strpos($_REQUEST['filename'], 'offers') !== false;
        $NS = &$_SESSION["BX_CML2_IMPORT"]["NS"];

        if (!isset($NS['custom']['lastId'])) {
            // Последний отработанный элемент для пошаговости.
            $NS['custom']['lastId'] = 0;
            $NS['custom']['counter'] = 0;
        }

        $rsData = HighloadBlockTable::getList(array('filter' => array('>ID' => $NS['custom']['lastId'],'NAME' => 'PodarochnyeSertifikaty')));
        $errorMessage = null;
		;
        if ( !($arData = $rsData->fetch()) ){

            $errorMessage = 'Инфоблок "PodarochnyeSertifikaty" не найден';

        } else {

            $Entity = HighloadBlockTable::compileEntity($arData);
            $DataClass = $Entity->getDataClass();
            $TYPE = DiscountCouponTable::TYPE_ONE_ORDER;
            $MAX_USE = 1;

            $table = DiscountCouponTable::getTableName();

            //Создадим объект - запрос
            $Query = new Query($Entity);

            //Зададим параметры запроса, любой параметр можно опустить
            $Query->setSelect(array('*'));
            //$Query->setFilter(array('ID'=> array(2,12,6)));
            //$Query->setOrder(array('UF_SORT' => 'ASC'));

            //Выполним запрос
            $res = $Query->exec();

            //Получаем результат по привычной схеме
			//$res = new CDBResult($result);
            while ($row = $res->fetch()){
                if (Loader::IncludeModule("catalog")) {
                    // Создадим купон
                    $COUPON = preg_replace('/[^0-9]/', '', trim((string)$row['UF_SHTRIKHKOD']));
                    $sumKupona = preg_replace('/[^0-9]/', '', trim($row['UF_RASSH1SUMMA']));
                    $DESCRIPTION = trim((string)$row['UF_NAME']);
                    if(trim($row['UF_RASSH1AKTIVNOST']) == '1'){ $ACTIVE = "Y"; }else { $ACTIVE = "N"; }

                    //Дата купона
                    $ACTIVE_DATE_TIME = new \DateTime($row['UF_RASSH1DATAPRODAZH']);
                    $ACTIVE_FROM = DateTime::createFromPhp(new \DateTime($row['UF_RASSH1DATAPRODAZH']))->format("Y-m-d H:i:s");
                    $ACTIVE_TO = DateTime::createFromPhp(new \DateTime($row['UF_RASSH1DATAPRODAZH']))->add("12 month")->format("Y-m-d H:i:s");

                    $couponIterator_1 = Internals\DiscountCouponTable::getList(array(
                        'select' => array('ACTIVE','COUPON'),
                        'filter' => array('COUPON' => $COUPON)
                    ));
                    if ($existCoupon = $couponIterator_1->fetch())
                    {
                        $info = array(
                            'ACTIVE' => (string)$existCoupon['ACTIVE'],
                            'COUPON' => (string)$existCoupon['COUPON']
                        );
                    };

                    if(!$info) {

                        //Поля правил корзины
                        $arFields = [
                            'LID' => 's1',
                            'NAME' => $DESCRIPTION,
                            'ACTIVE_FROM' => $ACTIVE_FROM,
                            'ACTIVE_TO' => $ACTIVE_TO,
                            'ACTIVE' => $ACTIVE,
                            'SORT' => 100,
                            'PRIORITY' => 1,
                            'LAST_DISCOUNT' => 'Y',
                            'LAST_LEVEL_DISCOUNT' => 'N',
                            'XML_ID' => '',
                            'CONDITIONS' => [
                                'CLASS_ID' => 'CondGroup',
                                'DATA' => [
                                    'All' => 'OR',
                                    'True' => 'True',
                                ],
                                'CHILDREN' => [
                                    0 => [
                                        'CLASS_ID' => 'CondBsktAmtBaseGroup',
                                        'DATA' => [
                                            'All' => 'AND',
                                            'logic' => 'EqGr',
                                            'Value' => $sumKupona,
                                        ],
                                        'CHILDREN' => []
                                    ],
                                    1 => [
                                        'CLASS_ID' => 'CondBsktAmtBaseGroup',
                                        'DATA' => [
                                            'All' => 'AND',
                                            'logic' => 'Less',
                                            'Value' => $sumKupona,
                                        ],
                                        'CHILDREN' => []
                                    ]
                                ]
                            ],
                            'ACTIONS' => [
                                'CLASS_ID' => 'CondGroup',
                                'DATA' => [
                                    'All' => 'AND',
                                ],
                                'CHILDREN' => [
                                    0 => [
                                        'CLASS_ID' => 'ActSaleBsktGrp',
                                        'DATA' => [
                                            'Type' => 'Discount',
                                            'Value' => $sumKupona,
                                            'Unit' => 'CurAll',
                                            'Max' => 0,
                                            'All' => 'AND',
                                            'True' => 'True',
                                        ],
                                        'CHILDREN' => []
                                    ]
                                ]
                            ],
                            'USER_GROUPS' => [2]
                        ];

                        $DISCOUNT_ID = \CSaleDiscount::Add($arFields);
	                    Internals\DiscountTable::setAllUseCoupons('Y'); //устанавливает флаг наличия купонов для всех правил корзины
                        $currentDateTime = new DateTime();
                        $currentDateTime = $currentDateTime->format("Y-m-d H:i:s");

                        //Поля купона
                        $stringFields = array("ID","ACTIVE_FROM","ACTIVE_TO","ACTIVE","COUPON","DATE_APPLY","TIMESTAMP_X","MODIFIED_BY","DATE_CREATE","CREATED_BY","DESCRIPTION");
                        $arFieldsCoupon = array(
                            'ID' => '',
                            'DISCOUNT_ID' => $DISCOUNT_ID,
                            'ACTIVE_FROM' => $ACTIVE_FROM,
                            'ACTIVE_TO' => $ACTIVE_TO,
                            "ACTIVE" => $ACTIVE,
                            'COUPON'      => $COUPON,
                            'TYPE'        => $TYPE,
                            'MAX_USE'     => 1,
                            'USE_COUNT' => 0,
                            'USER_ID' => 0,
                            'DATE_APPLY' => null,
                            'TIMESTAMP_X' => $currentDateTime,
                            'MODIFIED_BY' => null,
                            'DATE_CREATE' => $currentDateTime,
                            'CREATED_BY' => null,
                            'DESCRIPTION' => $DESCRIPTION
                        );


                        $str1 = "";
                        $str2 = "";
                        foreach($arFieldsCoupon as $field => $value) {
                            $str1 .= ($str1 <> ""? ", ":"")."`".$field."`";
                            if(in_array($field, $stringFields))
                                $str2 .= ($str2 <> ""? ", ":"")."'".$value."'";
                            else
                                $str2 .= ($str2 <> ""? ", ":"").$value;
                        }
                        if (strlen($EXIST_ID)>0) {
                            $strSql = "INSERT INTO ".$table."(ID,".$str1.") VALUES ('".$this->ForSql($EXIST_ID)."',".$str2.")";
                        } else {
                            $strSql = "INSERT INTO ".$table."(".$str1.") VALUES (".$str2.")";
                        }
                        $DB->query($strSql);
                        //Создали купон
                    } else if($info && $info['ACTIVE'] != $ACTIVE && $info['COUPON'] == $COUPON) {
                        //Купон уже есть и текущая активность отличается";
                        $str = "";
                        $stringFields_tw = array("ACTIVE"=>$ACTIVE);
                        foreach($stringFields_tw as $field => $value)
                        {
                            $str .= "`".$field."` = '".$value."', ";
                        }
                        $str = TrimEx($str,",");
                        //echo $str;
                        $strSql = "UPDATE ".$table." SET ".$str;
                        $DB->query($strSql);
                    }
                    //else if($info && $info['ACTIVE'] == $ACTIVE) {  }


                    //Удаляем из Хайлоад-блока
                    $delresult = $DataClass::delete($row['ID']);
                    if(!$delresult->isSuccess()){ //произошла ошибка
                        $error = true;
                        $errorMessage = $delresult->getErrorMessages(); //выведем ошибку
                    }


                } else {
                    $error = true;
                    $errorMessage = 'Не удалось подключить модуль "catalog"';
                }

                if ($error === true) {
                    break;
                }

                $NS['custom']['lastId'] = $arData['ID'];
                $NS['custom']['counter']++;

                // Прерывание по времени шага
                if ($stepInterval > 0 && (time() - $startTime) > $stepInterval) {
                    break;
                }

                //$step++;
            }
        }

        if ($arData != false) {
            if ($errorMessage === null) {
                echo "progress\n";
                echo "Обработано " .$NS['custom']['counter'] . " элементов, осталось " . $rsData->SelectedRowsCount()."\n";
                //if ($res->SelectedRowsCount() === 0) {echo "success\n";}
            } else {
                echo "failure\n" . $errorMessage;
            }
            $contents = ob_get_contents();
            ob_end_clean();
            if (toUpper(LANG_CHARSET) != "UTF-8") {
                $contents = $GLOBALS['APPLICATION']->ConvertCharset($contents, LANG_CHARSET, "utf-8");
            }

            header("Content-Type: text/html; charset=utf-8");
            print $contents;
            exit;
        }
    }
}
