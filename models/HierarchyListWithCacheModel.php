<?php

namespace lexing2008\yii2HierarchyList\models;

use Yii;

/**
 * Модель HierarchyListWithFileCacheModel
 * позволяет работать с иерархическим списком  любого уровня вложенности
 * МДанна
 * @author Alexey Sogoyan
 * @site https://www.linkedin.com/in/alexey-sogoyan/
 */
abstract class HierarchyListWithCacheModel extends HierarchyListModel
{
    /**
     * @var Модуль кэширования, если не задан, то по умолчанию используется Yii::$app->cache
     */
    public $cacher = null;

    /**
     * Получение элементов иерархического списка из файлового кэша
     * @return array элементы иерархического списка
     */
    public function getItemsFromCache(): array
    {
        $data = $this->getCacher()->get( $this->getCacheKey() );

        return empty($data) ? [] : $data;
    }

    /**
     * Сохранение элементов иерархического списка в кэш
     */
    public function saveItemsToCache()
    {
        $this->getCacher()->set( $this->getCacheKey(), $this->items );
    }

    /**
     * @return mixed|void удаление кэша
     */
    public function deleteCache()
    {
        $this->getCacher()->delete( $this->getCacheKey() );
    }

    /**
     * Возвращает модуль кэширования
     * @return Модуль|\yii\caching\CacheInterface|null
     */
    public function getCacher(){
        return empty($this->cacher) ? Yii::$app->cache : $this->cacher;
    }
}