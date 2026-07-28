<?php

return array (
  'id' => 'database-relations-map',
  'title' => 'Карта основных связей',
  'paragraphs' => 
  array (
    0 => 'Центр предметной модели — clients и contacts. Клиент представляет организацию, а контакт — человека, оставившего заявку. Один контакт может быть связан с несколькими клиентами, и один клиент может иметь несколько контактов, поэтому связь хранится в отдельной таблице client_contacts.',
    1 => 'Сектор назначается непосредственно клиенту отношением «один ко многим». Теги являются общими для клиентов и контактов, но используют две отдельные связующие таблицы. Пользовательские поля определяются отдельно, а значения связываются с клиентом или контактом через пару entity_type + entity_id.',
    2 => 'Пользователь может создавать и изменять основные записи, запускать импорт и экспорт. При удалении пользователя деловые данные и история не удаляются: внешние ключи created_by, updated_by и user_id в журнальных таблицах переходят в NULL.',
  ),
  'examples' => 
  array (
    0 => 
    array (
      'title' => 'Упрощённая ER-схема',
      'code' => 'roles 1 ─────── N users
                    ├── N user_permissions
                    └── N user_preferences

sectors 1 ───── N clients
                    │
                    N
              client_contacts
                    N
                    │
                contacts

clients  N ── client_tags  ── N tags
contacts N ── contact_tags ── N tags

custom_fields 1 ── N custom_field_options
custom_fields 1 ── N custom_field_values

users 1 ── N import_batches ── N import_rows ── N import_errors
users 1 ── N export_batches
api_keys 1 ── N api_logs',
    ),
  ),
);

