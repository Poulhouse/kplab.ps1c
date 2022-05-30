## Модуль «Подарочные сертификаты из 1С»
Модуль для использования логики работы подарочных сертификатов из 1С в интернет-магазине. Модуль помогает избавиться от рутинного добавления вручную правил корзины, создания купонов. Использует стандартную механику применения купонов.
>### [Проблема](#problem)
>Использование подарочных сертификатов на сайте в системе 1
С-Битрикс: Управление сайтом реализовано простым ручным внесением «правил корзины» и созданием таким же образом «Купонов». Также первоначально отсутствует какая-либо связь со справочником «Подарочные сертификаты» в системе учета 1С.

>### [Задача](#zadacha)
>Использование подарочных сертификатов как на сайте, так и в розничных магазинах, вне зависимости от того, где они были приобретены. 
>- На экране оформления заказа пользователь вносит номер сертификата (штрих-код) в поле «применить купон», при нажатии на одноименную кнопку, сумма заказа должна уменьшиться на номинал подарочного сертификата, чей номер был введён.
>- Сертификат можно использовать только один раз. Если сумма сертификата превышает сумму покупки, остаток сгорает (по возможности). Сертификат не закрепляется за пользователем, кому он был подарен или кем он приобретён.
>- Применение сертификата должно засчитываться после проведения оплаты заказа, на который он был применен.
### [Решение](#resheniye)
#### Со стороны 1С:
1. Добавить реквизиты справочника «Подарочные сертификаты» («Активность», «ДатаПродажи», «Сумма»), расширением в конфигурацию 1С, без возможности редактировании в элементе справочника. 
2. Скачать и установить бесплатное дополнение для торговых конфигураций 1С для интеграции 1
С с интернет-магазином на базе «1С-Битрикс: Управление сайтом». [Ссылка на страницу скачивания дополнения](https://1c.1c-bitrix.ru/ecommerce/download.php).
3. Провести настройку выгрузки пользовательских справочников на сайт. А также настроить общие параметры обмена и не забыть про периодический обмен данными. [Ссылка на официальную документацию](https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=131&LESSON_ID=10197&LESSON_PATH=10211.6315.10185.10195.10197).

#### Со стороны сайта на 1С-Битрикс:
Создан обработчик событий `OnSuccessCatalogImport1C` (успешный импорт из 1С) и `OnSuccessCatalogImportHL` (успешный импорт элементов Highload-блока)

После того, как происходит стандартный обмен (импорт из 1С) справочником «Подарочные сертификаты» создается Highload-блок (HL-блок) под названием `PodarochnyeSertifikaty`

Обработка происходит следующим образом:
- Проводится сбор всех HL-блоков в результирующий массив с помощью метода `getList` класса `HighloadBlockTable
` с применением фильтрации по `ID` и `NAME`, чтобы в массив добавился необходимый нам HL-блок;

<pre>
$rsData = HighloadBlockTable::getList(
    array(
        'filter' => array(
            '>ID' => $NS['custom']['lastId'], //ID справочника из 1С при импорте
            'NAME' => 'PodarochnyeSertifikaty'
        )
    )
);
</pre>

- Создаем объект-запрос из полученного массива;
- Построчно вытаскиваем информацию о полях HL-блока:
    - `UF_SHTRIKHKOD`,
    - `UF_RASSH1SUMMA`,
    - `UF_NAME`,
    - `UF_RASSH1AKTIVNOST`,
    - `UF_RASSH1DATAPRODAZH`; 
- Записываем их в переменные;
- С помощью класса `Internals\DiscountCouponTable` и метода `getList`
 получаем массив из списка существующих купонов с применением фильтрации по `ACTIVE` и `COUPON`;
- Если купона ещё не существует:
    - создаем правило корзины с помощью класса `\CSaleDiscount` и его метода `Add($arFields)`, записываем результат добавления правила корзины `(ID)` в переменную `$DISCOUNT_ID`,
    - формируем массив данных по купону и создаем купон, передачей сформированного массива запросом в БД `$DB->query($strSql)`;
- Если купон существует, но отличается активность от той что импортируется из 1С:
    - обновляем информацию о купоне через запрос к БД;
- Удаляем из HL-блока обработанный элемент `$DataClass::delete($row['ID'])`;
