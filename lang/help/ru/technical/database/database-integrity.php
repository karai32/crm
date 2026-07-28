<?php

return array (
  'id' => 'database-integrity',
  'title' => 'Внешние ключи и правила удаления',
  'paragraphs' => 
  array (
    0 => 'CASCADE используется для зависимых данных, которые не имеют смысла без владельца: user_permissions, user_preferences, связи тегов, client_contacts, варианты поля, импортированные строки и ошибки. SET NULL применяется к историческим ссылкам: автор записи, пользователь партии, сектор клиента, созданный импортом объект и ключ API в журнале.',
    1 => 'RESTRICT защищает системную роль, пока к ней привязаны пользователи. UNIQUE отвечает за бизнес-уникальность там, где она действительно зафиксирована схемой: email пользователя, name и slug справочников, client_id API, request_id журнала и составные пары связей.',
    2 => 'Не все предметные правила находятся в constraints. Email контакта не уникален на уровне MySQL, primary-контакт не ограничен одним на клиента, а полиморфные custom_field_values не имеют внешнего ключа на сущность. При прямых SQL-изменениях или новых репозиториях эти инварианты нужно соблюдать явно.',
  ),
  'examples' => 
  array (
    0 => 
    array (
      'title' => 'Ключевые последствия удаления',
      'code' => 'DELETE users
  → CASCADE: user_permissions, user_preferences
  → SET NULL: created_by, updated_by, import/export user_id, audit user_id

DELETE clients или contacts
  → CASCADE: client_contacts и соответствующие tag-связи
  → custom_field_values НЕ очищаются внешним ключом

DELETE custom_fields
  → CASCADE: custom_field_options, custom_field_values

DELETE api_keys
  → SET NULL: api_logs.api_key_id, журнал сохраняется',
    ),
  ),
);

