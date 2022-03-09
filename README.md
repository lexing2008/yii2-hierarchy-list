# yii2-hierarchy-list
Allows you to work with hierarchical lists (structures), for example, create a hierarchical menu, hierarchical categories

## Доступные методы
```php
load() - Подгружает данные вначале из кэша, если не получилось, то из таблицы
loadFromCache() - Подгружает из кэша данные иерархии
loadFromTable() - Загрузка информации из БД и формирование правильной иерархической структуры
saveItemsToCache() - Сохранение иерархического списка в кэш
deleteCache() - Удаление кэша
getChildren($parentId = 0, string $byField = self::FIELD_ID): array - получаем всех потомков родителя. Ищет родителя по умолчанию по 'id'
getItemWithChildren($parentId = 0, string $byField = self::FIELD_ID): array - Возвращает заданный элемент и массив всех потомков заданного элемента. Ищет родителя по умолчанию по 'id'
getChildrenFirstLevel($parentId = 0, string $byField = self::FIELD_ID): array - Возвращает массив всех потомков первого уровня относительно родителя. Ищет родителя по умолчанию по 'id'
getItem($id, string $byField = self::FIELD_ID): ?array - Возвращает элемент
getParent($id, string $byField = self::FIELD_ID): ?array - Возвращает родителя элемента
getParents($id, string $byField = self::FIELD_ID): ?array - Возвращает всех родителей элемента. Ищет родителя по умолчанию по 'id'
getItemWithParents($id, string $byField = self::FIELD_ID): ?array - Возвращает элемент вместе с родительскими элементами. Ищет родителя по умолчанию по 'id'
getAllItems(): array - Возвращает все элементы
getAllItemsByCallback($callbackFunction): array - Возвращает только элементы и их потомки для которых callback функция $callbackFunction возвращает true
```
