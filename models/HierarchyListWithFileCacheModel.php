<?php

namespace lexing2008\yii2HierarchyList\models;

use Yii;

/**
 * Модель HierarchyListWithFileCacheModel
 * позволяет работать с иерархическим списком рубрик
 * @author Alexey Sogoyan
 * @site https://www.linkedin.com/in/alexey-sogoyan/
 */
abstract class HierarchyListWithFileCacheModel extends HierarchyListModel
{
    /**
     * Получение элементов иерархического списка из файлового кэша
     * @return array элементы иерархического списка
     */
    public function getItemsFromCache(): array
    {
        return (array)Yii::$app->cache->get( $this->getCacheKey() );
    }

    /**
     * Сохранение элементов иерархического списка в кэш
     */
    public function saveItemsToCache()
    {
        Yii::$app->cache->set( $this->getCacheKey() );
    }
}