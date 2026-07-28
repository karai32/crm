<?php

return array (
  'id' => 'database-custom-fields',
  'title' => 'Модель пользовательских полей',
  'paragraphs' => 
  array (
    0 => 'Пользовательские поля реализованы как типизированная EAV-модель. custom_fields описывает поле, его сущность, slug, тип, обязательность, фильтруемость, значение по умолчанию и порядок. UNIQUE (entity_type, slug) позволяет иметь одинаковый slug у клиента и контакта, но не дважды внутри одной сущности.',
    1 => 'custom_field_options хранит допустимые варианты select. При удалении определения поля варианты и все значения удаляются каскадно. custom_field_values содержит одну строку на сочетание field_id + entity_type + entity_id. В зависимости от field_type заполняется только одна колонка: value_text, value_number, value_date или value_bool. Репозиторий сохраняет значение через ON DUPLICATE KEY UPDATE.',
    2 => 'entity_type + entity_id является полиморфной ссылкой: одна колонка entity_id может означать contacts.id или clients.id. MySQL не может создать один внешний ключ сразу на две таблицы, поэтому constraint на саму сущность отсутствует. База не предотвращает несовпадение типа поля и типа значения, а также осиротевшие значения после удаления клиента или контакта — это обязанность сервисного кода и периодических проверок.',
    3 => 'is_filterable не создаёт отдельный индекс автоматически. Флаг только разрешает показать поле в фильтрах интерфейса; скорость обеспечивают составные индексы field_id + typed value. Текстовый FULLTEXT существует, но текущие фильтры в репозиториях используют LIKE.',
  ),
  'examples' => 
  array (
    0 => 
    array (
      'title' => 'Как хранится поле language для контакта',
      'code' => '-- Определение
INSERT INTO custom_fields
    (entity_type, name, slug, field_type, is_filterable)
VALUES
    (\'contact\', \'Язык\', \'language\', \'text\', 1);

-- Значение для contacts.id = 125
INSERT INTO custom_field_values
    (field_id, entity_type, entity_id, value_text)
VALUES
    (:language_field_id, \'contact\', 125, \'ru\')
ON DUPLICATE KEY UPDATE value_text = VALUES(value_text);',
    ),
    1 => 
    array (
      'title' => 'Поиск осиротевших значений',
      'code' => 'SELECT cfv.*
FROM custom_field_values cfv
LEFT JOIN contacts c
    ON cfv.entity_type = \'contact\' AND c.id = cfv.entity_id
LEFT JOIN clients cl
    ON cfv.entity_type = \'client\' AND cl.id = cfv.entity_id
WHERE (cfv.entity_type = \'contact\' AND c.id IS NULL)
   OR (cfv.entity_type = \'client\' AND cl.id IS NULL);',
    ),
  ),
);

