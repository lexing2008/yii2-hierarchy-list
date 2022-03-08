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
class CategoryPublishedHierarchy extends  HierarchyListWithCacheModel
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
    }

    public function getCacheKey(): string
    {
        return __CLASS__ . $this->language;
    }

    public function getItemsFromTable(): array
    {
        $category = new CategoryHierarchy(['language' => $this->language]);
        $category->initialize();

        return $category->getAllPublishedCategories();
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