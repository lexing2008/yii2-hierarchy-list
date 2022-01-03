<?php
namespace lexing2008\yii2HierarchyList\models;

use Yii;
use yii\base\BaseObject;

/**
 * Модель HierarchyListModel
 * позволяет работать с иерархическим списком
 * @author Alexey Sogoyan
 * @site https://www.linkedin.com/in/alexey-sogoyan/
 */
abstract class HierarchyListModel extends BaseObject
{
    /**
     * Поле идинтификатора
     */
    const FIELD_ID = 'id';

    /**
     * ID главного родителя в иерархии
     */
    const MAIN_PARENT_ID = 0;

    /**
     * Нужно ли подгружать данные при создании объекта
     * @var bool
     */
    public $autoLoad = false;

    /**
     * Категории
     * @var array
     */
    public $category;

    /**
     * Упорядоченные по дереву элементы
     * @var array
     */
    public $items;

    /**
     * Полe, содержащее parent_id
     * @var string
     */
    public $fieldParentId = 'parent_id';

    /**
     * Поле уровень вложенности
     * @var string
     */
    public $fieldLevel = 'l';

    /**
     * Количество элементов в $items. Вычисляется как счетчик. используется как счетчик
     * @var int
     */
    protected $currentCountItems = 0;

    /**
     * Поля, по которым возможно искать элементы
     * @var string[]
     */
    public $fieldsForSearch = [];

    /**
     * Содержит соответствия ID => Номер позиции в $this->>items
     * @var array
     */
    protected $mapByid = [];

    /*
     * Флаг того, что данные были подгружены
     */
    protected $flagLoaded = false;

    /**
     * Название поля идентификатора записи
     * @var string
     */
    public $fieldIdName = self::FIELD_ID;

    /**
     * Возвращает ключ кэша
     * @return string
     */
    abstract public function getCacheKey(): string;

    /**
     * Получение элементов иерархического списка из таблицы
     */
    abstract public function getItemsFromTable(): array;

    /**
     * Получение элементов иерархического списка из кэша
     */
    abstract public function getItemsFromCache(): array;

    /**
     * Сохранение элементов иерархического списка в кэш
     */
    abstract public function saveItemsToCache();

    /**
     * @return mixed удаляет кэш
     */
    abstract public function deleteCache();

    /**
     * Конструктор класса
     * @param array $config конфиг
     * @param bool $autoLoad автоматическая загрука из кэша, если не получилось из кэша, то из БД при создании объекта
     */
    public function __construct(array $config = [])
    {
        parent::__construct($config);

        $this->fieldsForSearch[] = $this->fieldIdName;

        // подгружаем всю информацию
        if ($this->autoLoad){
            $this->load();
        }
    }

    /**
     * Инициализирует (подгружает данные), если ранее не подгружал
     */
    public function init(): self {
        if(!$this->flagLoaded){
            $this->load();
        }

        return $this;
    }

    /**
     * Подгружаем данные
     */
    public function load() {
        // если не удалось подгрузить из кэша
        if(!$this->loadFromCache()){
            // подгружаем из таблицы
            $this->loadFromTable();
        }
        $this->flagLoaded = true;
    }

    /**
     * Подгружает из кэша данные иерархии
     * @return bool получилось подгрузить из кэша данные иерархии
     */
    public function loadFromCache(): bool
    {
        $this->items = $this->getItemsFromCache();

        $this->createMaps();

        return !empty($this->items);
    }

    /**
     * Создает индекс, связывающий ID с номеом позиции в $this->>items
     */
    protected function createMaps(){
        // создаем поля карты соответствий
        foreach($this->fieldsForSearch as $fieldName){
            $this->{'mapBy' . $fieldName} = [];
        }
        // заполняем карты соответствий
        foreach($this->fieldsForSearch as $fieldName){
            foreach ($this->items as $key => &$item){
                $this->{'mapBy' . $fieldName}[ $item[$fieldName] ] = $key;
            }
        }
    }

    /**
     * Загрузка информации из БД и формирование правильной иерархической структуры
     */
    public function loadFromTable()
    {
        // Получение элементов иерархического списка из таблицы
        $records = $this->getItemsFromTable();
        $rows = count($records);
        $this->category = [];
        $this->items = [];
        for ($i = 0; $i < $rows; ++$i) {
            $this->category[$records[$i][$this->fieldIdName]] = $records[$i];
        }

        // устанавливаем текущее количество в нуль
        $this->currentCountItems = 0;
        // учищаем индекс по ID
        $this->mapByid = [];
        // приводим в структурированный вид иерархический список
        $this->nextItem( self::MAIN_PARENT_ID ); // (Родитель) pid = 0; (Уровень вложенности) level = 0; Начинаем отсчет уровня вложенности
        // сохраняем в кэш
        $this->saveItemsToCache();
    }

    /**
     * Функция находит все элементы родителя
     * @param int $parentId идентификатор родителя. Для самого верхнего это 0
     * @param int $level уровень вложенности
     */
    private function nextItem(int $parentId, int $level = 0)
    {
        // просматриваем весь массив
        foreach ($this->category as $key => $val) {
            // элемент пренадлежит родителю
            if ($val[ $this->fieldParentId ] == $parentId) {
                // добавляем текущий элемент в наш массив упорядоченных элементов
                $this->items[$this->currentCountItems]          = $val;
                $this->items[$this->currentCountItems][$this->fieldLevel] = $level;

                // создаем карту соотвествий поля  позиции элемента
                foreach($this->fieldsForSearch as $fieldName){
                    $this->{'mapBy' . $fieldName}[ $val[$fieldName] ] = $this->currentCountItems;
                }

                // удаляем текущий элемент из массива
                unset($this->category[$key]);
                // увеличивае счетчик
                $this->currentCountItems++;
                // рекурсивно ищем потомков для данного
                $this->nextItem($key, $level + 1);
            }
        }
    }

    /**
     * Возвращает массив всех потомков заданного элемента
     * @param int $parentId  идентификатор родителя
     * @return array массив потомков
     */
    public function getChildren($parentId = 0, string $byField = self::FIELD_ID): array
    {
        // проверяем, доступен ли поиск по данному полю
        $this->checkField($byField);

        if($byField == self::FIELD_ID &&  $parentId == self::MAIN_PARENT_ID){
            return $this->items;
        }

        $arr        = [];
        $i          = $this->{'mapBy' . $byField}[ $parentId ];
        $flagLevel  = $this->items[$i][$this->fieldLevel];
        ++$i;
        $count = count($this->items);
        while ($i < $count && $this->items[$i][$this->fieldLevel] > $flagLevel) {
            $arr[] = $this->items[$i];
            ++$i;
        }
        return $arr;
    }


    /**
     * Возвращает массив всех потомков первого уровня относительно родителя
     * @param int $parentId значение поля родителя
     * @param string $byField
     * @return array
     */
    public function getChildrenFirstLevel($parentId = 0, string $byField = self::FIELD_ID): array
    {
        // проверяем, доступен ли поиск по данному полю
        $this->checkField($byField);

        $arr = [];

        if($byField == self::FIELD_ID && $parentId == self::MAIN_PARENT_ID){
            $i     = 0;
            $level = 1;
        } else {
            $i          = $this->{'mapBy' . $byField}[ $parentId ];
            $level      = $this->items[$i][$this->fieldLevel]+1;
            ++$i;
        }

        $count = count($this->items);
        while ($i < $count && $this->items[$i][$this->fieldLevel] >= $level) {
            if($this->items[$i][$this->fieldLevel] == $level) {
                $arr[] = $this->items[$i];
            }
            ++$i;
        }
        return $arr;
    }

    /**
     * Возвращает родителя элемента
     * @param $id значение поля
     * @param string $byField название поля
     * @return array|null
     */
    public function getItem($id, string $byField = self::FIELD_ID): ?array
    {
        // проверяем, доступен ли поиск по данному полю
        $this->checkField($byField);

        return $this->items[ $this->{'mapBy'.$byField}[$id] ];
    }

    /**
     * Возвращает родителя элемента
     * @param $id значение поля
     * @param string $byField название поля
     * @return array|null
     */
    public function getParent($id, string $byField = self::FIELD_ID): ?array
    {
        // проверяем, доступен ли поиск по данному полю
        $this->checkField($byField);

        $parentId = $this->items[ $this->{'mapBy'.$byField}[$id] ][ $this->fieldParentId ];

        return $this->items[ $this->{'mapBy'.$this->fieldIdName}[$parentId] ];
    }

    /**
     * Возвращает всех родителей элемента
     * @param $id значение поля
     * @param string $byField название поля
     * @return array|null
     */
    public function getParents($id, string $byField = self::FIELD_ID): ?array
    {
        // проверяем, доступен ли поиск по данному полю
        $this->checkField($byField);

        $parents = [];

        $parentId =  $this->items[ $this->{'mapBy'.$byField}[$id] ][$this->fieldParentId];
        while( $parent = $this->items[ $this->{'mapBy'.$this->fieldIdName}[$parentId] ] ){
            $parents[]  = $parent;
            $parentId   = $parent[$this->fieldIdName];
        }

        return $parents;
    }

    /**
     * Возвращает элемент вместе с родительскими элементами
     * @param $id значение поля
     * @param string $byField имя поля
     * @return array|null
     */
    public function getItemWithParents($id, string $byField = self::FIELD_ID): ?array
    {
        // проверяем, доступен ли поиск по данному полю
        $this->checkField($byField);

        $items = [];

        $parentId = $this->items[ $this->{'mapBy'.$byField}[$id] ][$this->fieldIdName];
        while( $parent = $this->items[ $this->{'mapBy'.$this->fieldIdName}[$parentId] ] ){
            $items[]    = $parent;
            $parentId   = $parent[$this->fieldIdName];
        }

        return $items;
    }

    /**
     * Возвращает все элементы
     * @return array все элементы
     */
    public function getAllItems(): array
    {
        return $this->items;
    }

    /**
     * Проверяет наличие поля в $this->fieldsForSearch
     * @param string $fieldName имя поля
     */
    protected function checkField(string $fieldName)
    {
        if(!in_array($fieldName, $this->fieldsForSearch)){
            throw new Exception("Field $fieldName not found in $this->fieldsForSearch. Please, add field in $this->fieldsForSearch.");
        }
    }
}