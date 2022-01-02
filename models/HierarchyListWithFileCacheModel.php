<?php

namespace lexing2008\yii2HierarchyList\models;

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
        $items = [];
        // путь к файлу кэша
        $path = $this->getCacheFilePath();
        // проверяем существование файла
        if(file_exists($path)){
            // получаем элементы иерархического списка
            $items = json_decode(file_get_contents($path), true);
        }

        return $items;
    }

    /**
     * Сохранение элементов иерархического списка в кэш
     */
    public function saveItemsToCache()
    {
        // преобразуем массив элементов иерархического списка в json
        $content = json_encode($this->items);
        // записываем кэш в файл
        file_put_contents($this->getCacheFilePath(), $content);
    }
}