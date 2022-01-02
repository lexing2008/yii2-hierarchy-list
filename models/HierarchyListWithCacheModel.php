<?php

namespace lexing2008\yii2HierarchyList\models;

use Yii;

/**
 * Модель HierarchyListWithFileCacheModel
 * позволяет работать с иерархическим списком рубрик
 * @author Alexey Sogoyan
 * @site https://www.linkedin.com/in/alexey-sogoyan/
 */
abstract class HierarchyListWithCacheModel extends HierarchyListModel
{
    /**
     * Получение элементов иерархического списка из файлового кэша
     * @return array элементы иерархического списка
     */
    public function getItemsFromCache(): array
    {
        $data = Yii::$app->cache->get( $this->getCacheKey() );

        return empty($data) ? [] : $data;
    }

    /**
     * Сохранение элементов иерархического списка в кэш
     */
    public function saveItemsToCache()
    {
        Yii::$app->cache->set( $this->getCacheKey() );
    }

    /**
     * @return mixed|void удаление кэша
     */
    public function deleteCache()
    {
        Yii::$app->cache->delete( $this->getCacheKey() );
    }
}