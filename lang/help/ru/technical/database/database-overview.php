<?php

return array (
  'id' => 'database-overview',
  'title' => 'Назначение и устройство базы',
  'paragraphs' => 
  array (
    0 => 'ContactCore использует одну реляционную базу MySQL или MariaDB. Исходная схема находится в database/schema.sql и создаёт 21 таблицу. Все таблицы используют InnoDB, кодировку utf8mb4 и сравнение utf8mb4_unicode_ci: это обеспечивает транзакции, внешние ключи и корректное хранение многоязычного текста.',
    1 => 'В базе можно выделить пять областей: пользователи и доступ; клиенты, контакты и классификация; пользовательские поля; история импорта и экспорта; API и технические журналы. Это не отдельные базы и не независимые модули — между ними существуют внешние ключи и прикладные связи.',
    2 => 'Приложение не использует ORM. Репозитории выполняют SQL через PDO и возвращают ассоциативные массивы. PDO настроен с ATTR_STRINGIFY_FETCHES, поэтому числовые значения из SELECT могут приходить как строки; код явно приводит идентификаторы, счётчики и флаги к int там, где это важно.',
  ),
  'examples' => 
  array (
    0 => 
    array (
      'title' => 'Группы таблиц',
      'code' => 'Доступ
  roles, users, user_permissions, user_preferences

Основные данные
  sectors, clients, contacts
  tags, client_tags, contact_tags, client_contacts

Пользовательские поля
  custom_fields, custom_field_options, custom_field_values

Обмен данными
  import_batches, import_rows, import_errors, export_batches

Интеграции и история
  api_keys, api_logs, audit_logs',
    ),
  ),
);

