<?php

return array (
  'id' => 'database-indexes',
  'title' => 'Индексы, поиск и производительность',
  'paragraphs' => 
  array (
    0 => 'Первичные и уникальные ключи автоматически являются индексами. Дополнительные B-tree индексы покрывают внешние ключи, статусы, даты и часто используемые поля фильтров. В связующих таблицах составной PRIMARY KEY хорошо работает от первой колонки, а отдельный индекс по второй колонке обеспечивает обратное направление поиска.',
    1 => 'В contacts и custom_field_values определены FULLTEXT-индексы, однако текущие репозитории не используют MATCH ... AGAINST: текстовый поиск выполняется через LIKE с шаблоном %значение%. Такой шаблон обычно не использует обычный B-tree индекс. На небольшой базе это приемлемо, но при росте контактов поиск необходимо измерять через EXPLAIN ANALYZE и при необходимости перевести на FULLTEXT или отдельный поисковый сервис.',
    2 => 'Индекс создают под конкретный запрос, а не под каждую колонку. Избыточные индексы занимают место и замедляют INSERT/UPDATE. Для составного индекса важен порядок колонок: idx_api_logs_key_created полезен для WHERE api_key_id = ? ORDER BY created_at, но не заменяет индекс, начинающийся с created_at, для общего временного диапазона.',
  ),
  'examples' => 
  array (
    0 => 
    array (
      'title' => 'Проверка плана запроса',
      'code' => 'EXPLAIN ANALYZE
SELECT id, full_name, email
FROM contacts
WHERE created_at >= \'2026-01-01 00:00:00\'
  AND email_status = \'valid\'
ORDER BY created_at DESC
LIMIT 50;',
    ),
    1 => 
    array (
      'title' => 'FULLTEXT, который пока не использует приложение',
      'code' => 'SELECT id, full_name, email, phone
FROM contacts
WHERE MATCH(full_name, email, phone)
      AGAINST(:query IN NATURAL LANGUAGE MODE)
ORDER BY created_at DESC
LIMIT 50;',
    ),
  ),
);

