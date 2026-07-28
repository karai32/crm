<?php

return array (
  'id' => 'database-health',
  'title' => 'Проверка целостности и обслуживание',
  'paragraphs' => 
  array (
    0 => 'Периодически проверяйте рост больших таблиц: contacts, custom_field_values, import_rows, import_errors и api_logs. Для журналов и импортов должна существовать согласованная политика хранения. Удаление истории выполняют небольшими порциями и только после понимания каскадов, чтобы не создавать долгую блокировку и большой всплеск binary log.',
    1 => 'CHECK TABLE не заменяет логические проверки. Отдельно ищите осиротевшие полиморфные значения, неизвестные permission_key, зависшие import_batches со статусом processing и API-логи без политики хранения. После крупных удалений оценивайте таблицы и индексы, но не запускайте OPTIMIZE TABLE автоматически на больших production-таблицах без окна обслуживания.',
    2 => 'Резервная копия считается рабочей только после пробного восстановления. Для согласованного дампа InnoDB используется mysqldump --single-transaction; копия должна храниться отдельно от сервера приложения. Восстановление проверяется вместе с совместимой версией кода и конфигурации.',
  ),
  'examples' => 
  array (
    0 => 
    array (
      'title' => 'Несколько логических проверок',
      'code' => '-- Зависшие импорты старше двух часов
SELECT id, original_filename, started_at
FROM import_batches
WHERE status = \'processing\'
  AND started_at < NOW() - INTERVAL 2 HOUR;

-- Неизвестные ключи разрешений нужно сравнить с Auth::permissionDefinitions()
SELECT DISTINCT permission_key
FROM user_permissions
ORDER BY permission_key;

-- Объём самых быстрорастущих таблиц
SELECT TABLE_NAME, TABLE_ROWS, DATA_LENGTH, INDEX_LENGTH
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()
ORDER BY DATA_LENGTH + INDEX_LENGTH DESC;',
    ),
  ),
);

