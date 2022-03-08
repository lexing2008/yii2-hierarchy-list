<?php

namespace common\models;

use Yii;
use lexing2008\yii2HierarchyList\models\HierarchyListWithCacheModel;

/**
 * Class CategoryHierarchy
 * Для работы с иерархическим списком категорий
 * Элементы:
 * [
 *  'id' => идентификатор записи,
 *  'title' => title,
 *  'alias' => alias,
 *  'parent_id' => (id родительского элемента),
 *  'status' => статус публикайии
 * ]
 * @package common\models
 */
class CategoryHierarchy extends  HierarchyListWithCacheModel
{
    public function __construct(array $config = [])
    {
        // делаем поиск по alias
        $this->fieldsForSearch = ['alias'];

        parent::__construct($config);
    }

    /**
     * Ключ, по которому будут храниться в кэше данные
     * @return string
     */
    public function getCacheKey(): string
    {
        return __CLASS__;
    }

    /**
     * Получение данных из БД
     * @return array
     */
    public function getItemsFromTable(): array
    {
        return Category::find()
                    ->select(['id', 'publication_status as status', 'parent_id', 'title', 'alias'])
                    ->orderBy(['position' => SORT_ASC])
                    ->asArray()
                    ->all();
    }

    /**
     * Возвращает только опубликованные категории с учетом иерархии
     * @return array
     */
    public function getAllPublishedCategories(){
        return
            $this->getAllItemsByCallback(function(&$item){
                return $item['status'] == Category::PUBLICATION_STATUS_PUBLISH;
            });
    }
}