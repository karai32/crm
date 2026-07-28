<?php

return array (
  'id' => 'database-development',
  'title' => 'Работа разработчика с базой',
  'paragraphs' => 
  array (
    0 => 'Изменение модели начинается со схемы и сценариев данных, затем обновляются Repository, Service, Controller, API, импорт, экспорт и представления. Новая колонка редко ограничивается одним SELECT: проверьте создание, редактирование, фильтрацию, массовые операции, API-формат и резервное восстановление.',
    1 => 'Для диагностики используйте SHOW CREATE TABLE, SHOW INDEX, INFORMATION_SCHEMA, EXPLAIN ANALYZE и точные SELECT-запросы. Не исправляйте рабочие данные вручную без сохранённого запроса, предварительного SELECT и резервной копии. Массовый UPDATE сначала запускается как SELECT с тем же WHERE внутри транзакции или на копии базы.',
    2 => 'Тестовые данные не должны содержать реальные персональные сведения. Снимок production-базы для разработки нужно анонимизировать: email, телефоны, имена, IP, request_body, response_body и значения пользовательских полей могут содержать персональные данные.',
  ),
  'examples' => 
  array (
    0 => 
    array (
      'title' => 'Проверка схемы перед изменением',
      'code' => 'SHOW CREATE TABLE contacts;
SHOW INDEX FROM contacts;

SELECT
    TABLE_ROWS,
    DATA_LENGTH,
    INDEX_LENGTH
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = \'contacts\';',
    ),
    1 => 
    array (
      'title' => 'Контрольный список изменения модели',
      'code' => '[ ] отдельная миграция и обновлённый schema.sql
[ ] совместимые типы внешних ключей
[ ] нужные UNIQUE, FOREIGN KEY и индексы
[ ] Repository и транзакционная граница Service
[ ] формы, фильтры, API, импорт и экспорт
[ ] обработка удаления и NULL
[ ] тест на существующих и пустых данных
[ ] резервная копия и понятный способ отката',
    ),
  ),
);

