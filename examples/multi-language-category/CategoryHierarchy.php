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
 *  't' => title,
 *  'a' => alias,
 *  'p' => parentId (id родительского элемента),
 *  's' => publication_status
 * ]
 * @package common\models
 */
class CategoryHierarchy extends  HierarchyListWithCacheModel
{
    /**
     * @var string языковая версия
     */
    public $language = '';

    public function __construct(array $config = [])
    {
        // делаем поиск по alias
        $this->fieldsForSearch = ['a'];
        // устанавливаем название поля parent_id
        $this->fieldParentIdName = 'p';
        // устанавливаем название поля level
        $this->fieldLevelName = 'l';

        parent::__construct($config);

        if(empty($this->language)){
            throw new \Exception('Нужно при создании объекта ' . __CLASS__ . ' указать $language');
        }
    }


    public function getCacheKey(): string
    {
        return __CLASS__ . $this->language;
    }

    public function getItemsFromTable(): array
    {
        return Category::find()
                    ->lang($this->language)
                    ->alias('a')
                    ->select(['a.id', 'a.position as w',  'a.publication_status as s', 'a.parent_id as p', 'category_lang.title as t', 'category_lang.alias as a'])
                    ->orderBy(['a.position' => SORT_ASC])
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
                return $item['s'] == Category::PUBLICATION_STATUS_PUBLISH;
            });
    }

    /**
     * Удаляем кэш для всех языков
     * @throws \Exception
     */
    public static function deleteCacheForAllLanguages()
    {
        foreach (Yii::$app->params['languages'] as $lang){
            $model = new self(['language' => $lang]);
            $model->deleteCache();
        }
    }
}