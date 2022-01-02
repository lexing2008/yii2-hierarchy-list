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
     * ID главного родителя в иерархии
     */
    const MAIN_PARENT_ID = 0;

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
     * Количество элементов в $items. Вычисляется как счетчик. используется как счетчик
     * @var int
     */
    protected $currentCountItems = 0;

    /**
     * Содержит соответствия ID => Номер позиции в $this->>items
     * @var array
     */
    protected $indexById = [];

    /*
     * Флаг того, что данные были подгружены
     */
    protected $flagLoaded = false;

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
     * Конструктор класса
     * @param array $config конфиг
     * @param bool $autoLoad автоматическая загрука из кэша, если не получилось из кэша, то из БД при создании объекта
     */
    public function __construct(array $config = [], bool $autoLoad = false)
    {
        parent::__construct($config);
        // подгружаем всю информацию
        if ($autoLoad){
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

            $this->flagLoaded = true;
        }
    }

    /**
     * Подгружает из кэша данные иерархии
     * @return bool получилось подгрузить из кэша данные иерархии
     */
    public function loadFromCache(): bool
    {
        $this->items = $this->getItemsFromCache();

        $this->createIndexById();

        return !empty($this->items);
    }

    /**
     * Создает индекс, связывающий ID с номеом позиции в $this->>items
     */
    protected function createIndexById(){
        $this->indexById = [];
        foreach ($this->items as $key => &$item){
            $this->indexById[ $item['id'] ] = $key;
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
        unset($this->category);
        unset($this->items);
        $this->category = [];
        for ($i = 0; $i < $rows; ++$i) {
            $this->category[$records[$i]['id']] = $records[$i];
        }

        // устанавливаем текущее количество в нуль
        $this->currentCountItems = 0;
        // учищаем индекс по ID
        $this->indexById = [];
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
                $this->items[$this->currentCountItems]['level'] = $level;

                $this->indexById[ $val['id'] ] = $this->currentCountItems;

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
     * @param int $parentId  идентификатор родителя. Приведен к int.
     * @return array массив потомков
     */
    public function getChildren(int $parentId = 0): array
    {
        if($parentId == self::MAIN_PARENT_ID){
            return $this->items;
        }

        $arr        = [];
        $i          = $this->indexById[ $parentId ];
        $flagLevel  = $this->items[$i]['level'];
        ++$i;
        $count = count($this->items);
        while ($i < $count && $this->items[$i]['level'] > $flagLevel) {
            $arr[] = $this->items[$i];
            ++$i;
        }
        return $arr;
    }

    /**
     * Возвращает массив всех потомков первого уровня относительно родителя
     * @param int $parentId  идентификатор родителя.
     * @return array массив потомков
     */
    public function getChildrenFirstLevel(int $parentId = 0): array
    {
        $arr = [];

        if($parentId == self::MAIN_PARENT_ID){
            $i     = 0;
            $level = 1;
        } else {
            $i          = $this->indexById[ $parentId ];
            $level      = $this->items[$i]['level']+1;
            ++$i;
        }

        $count = count($this->items);
        while ($i < $count && $this->items[$i]['level'] >= $level) {
            if($this->items[$i]['level'] == $level) {
                $arr[] = $this->items[$i];
            }
            ++$i;
        }
        return $arr;
    }

    /**
     * Возвращает родителя элемента
     * @param $id ID элемента
     * @return array|null
     */
    public function getParent($id): ?array {
        return $this->items[ $this->indexById[$id] ];
    }

    /**
     * Возвращает всех родителей элемента
     * @param $id ID элемента
     * @return array|null
     */
    public function getParents($id): ?array {
        $parents = [];

        $parentId =  $this->items[ $this->indexById[$id] ]['id'];
        while( $parent = $this->items[ $this->indexById[$parentId] ] ){
            $parents[]  = $parent;
            $parentId   = $parent['id'];
        }

        return $parents;
    }

    /**
     * Возвращает элемент вместе с родительскими элементами
     * @param $id ID элемента
     * @return array|null
     */
    public function getItemWithParents($id): ?array {
        $items = [];

        $parentId =  $id;
        while( $parent = $this->items[ $this->indexById[$parentId] ] ){
            $items[]    = $parent;
            $parentId   = $parent['id'];
        }

        return $items;
    }

    /**
     * Возвращает все элементы
     * @return array все элементы
     */
    public function getAllItems(): array {
        return $this->items;
    }
}