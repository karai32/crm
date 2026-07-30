<?php

return [
    'title' => 'База данных',
    'description' => 'Схема MySQL, связи между сущностями, целостность данных и правила изменения модели.',
    'icon' => 'ph-arrow-elbow-down-right',
    'sections' => [
array (
  'id' => 'database-overview',
  'title' => 'Назначение и устройство базы',
  'paragraphs' =>
  array (
    0 => 'ContactCore использует одну реляционную базу MySQL или MariaDB. Исходная схема находится в database/schema.sql и создаёт 21 таблицу. Все таблицы используют InnoDB, кодировку utf8mb4 и сравнение utf8mb4_unicode_ci: это обеспечивает транзакции, внешние ключи и корректное хранение многоязычного текста.',
    1 => 'В базе можно выделить пять областей: пользователи и доступ; клиенты, контакты и классификация; пользовательские поля; история импорта и экспорта; API и технические журналы. Это не отдельные базы и не независимые модули — между ними существуют внешние ключи и прикладные связи.',
    2 => 'Приложение не использует ORM. Репозитории строят запросы через Illuminate Database Query Builder и возвращают ассоциативные массивы. Низкоуровневый SQL в отдельных потоковых и служебных операциях может получать тот же PDO через Database::connect(), поэтому оба способа используют одно подключение и общую транзакцию. PDO настроен с ATTR_STRINGIFY_FETCHES, поэтому числовые значения из SELECT могут приходить как строки; код явно приводит идентификаторы, счётчики и флаги к int там, где это важно.',
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
),
array (
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
),
array (
  'id' => 'database-conventions',
  'title' => 'Типы, идентификаторы и временные поля',
  'paragraphs' =>
  array (
    0 => 'Обычные сущности используют INT UNSIGNED AUTO_INCREMENT. Быстро растущие журналы, партии импорта и значения пользовательских полей используют BIGINT UNSIGNED. Внешний ключ должен иметь тот же размер и признак UNSIGNED, что и связанный первичный ключ; несовпадение типов не позволит создать constraint.',
    1 => 'Булевы значения хранятся как TINYINT(1), ограниченные наборы состояний — как ENUM, произвольные структуры параметров — как JSON. ENUM удобен для фиксированного статуса, но добавление нового значения требует изменения схемы. JSON применяется только там, где структура действительно переменная: mapping импорта, фильтры экспорта, scopes и снимки аудита.',
    2 => 'created_at обычно заполняется CURRENT_TIMESTAMP, updated_at меняется автоматически через ON UPDATE CURRENT_TIMESTAMP. Предметные даты вроде last_login_at, started_at и finished_at являются DATETIME и устанавливаются приложением. Время сервера, PHP и MySQL должно быть согласовано, иначе фильтры и отчёты будут давать смещённые периоды.',
  ),
  'examples' =>
  array (
    0 =>
    array (
      'title' => 'Типовой каркас таблицы',
      'code' => 'CREATE TABLE projects (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(190) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_projects_created_by
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_projects_name (name),
    INDEX idx_projects_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;',
    ),
  ),
),
array (
  'id' => 'database-users',
  'title' => 'Пользователи, роли и настройки',
  'paragraphs' =>
  array (
    0 => 'roles содержит системные роли admin и user. users хранит имя, уникальный email, password_hash, активность и дату последнего входа. Пароль никогда не хранится открытым текстом: PHP создаёт его через password_hash(), а вход проверяет password_verify(). Удаление используемой роли запрещено ON DELETE RESTRICT.',
    1 => 'user_permissions хранит индивидуальное решение по каждому permission_key. Составной PRIMARY KEY (user_id, permission_key) не позволяет создать два значения одного разрешения для пользователя. При удалении пользователя разрешения удаляются каскадно.',
    2 => 'user_preferences — расширяемое key-value хранилище настроек интерфейса. Сейчас приложение использует ключ per_page. Уникальная пара user_id + preference_key позволяет применять INSERT ... ON DUPLICATE KEY UPDATE. Настройки не следует смешивать с разрешениями: preference влияет на удобство интерфейса, permission — на доступ к операции.',
  ),
  'examples' =>
  array (
    0 =>
    array (
      'title' => 'Пользователь и его явные разрешения',
      'code' => 'SELECT
    u.id,
    u.name,
    u.email,
    r.name AS role,
    up.permission_key,
    up.is_allowed
FROM users u
INNER JOIN roles r ON r.id = u.role_id
LEFT JOIN user_permissions up ON up.user_id = u.id
WHERE u.id = :user_id
ORDER BY up.permission_key;',
    ),
  ),
),
array (
  'id' => 'database-clients-contacts',
  'title' => 'Клиенты и контакты',
  'paragraphs' =>
  array (
    0 => 'clients хранит организацию: коммерческое и юридическое название, CIF, адрес, сайт, сектор, заметки и два независимых состояния — активное сотрудничество и подключение сайта по API. Поля is_active_date и is_web_connected_date фиксируют момент изменения соответствующего состояния.',
    1 => 'contacts хранит человека и доступные способы связи. company — текстовое название компании, полученное вручную или через Gemini, и не заменяет связь с clients. is_corporate_email и email_status являются результатами классификации адреса; NULL означает отсутствие результата, unknown — классификацию без живой MX-проверки.',
    2 => 'created_by и updated_by указывают пользователя, выполнившего действие через интерфейс, когда этот контекст доступен. ON DELETE SET NULL сохраняет саму запись при удалении пользователя. Заполненный contacts.email и clients.commercial_name имеют UNIQUE-индексы; предварительные проверки приложения улучшают сообщение об ошибке, но окончательный инвариант обеспечивает MySQL.',
  ),
  'examples' =>
  array (
    0 =>
    array (
      'title' => 'Контакты выбранного клиента',
      'code' => 'SELECT
    c.id,
    c.full_name,
    c.email,
    c.phone,
    cc.relation_label,
    cc.is_primary
FROM client_contacts cc
INNER JOIN contacts c ON c.id = cc.contact_id
WHERE cc.client_id = :client_id
ORDER BY cc.is_primary DESC, c.full_name ASC;',
    ),
  ),
),
array (
  'id' => 'database-classification',
  'title' => 'Сектора, теги и связующие таблицы',
  'paragraphs' =>
  array (
    0 => 'sectors — справочник отраслей для клиентов. clients.sector_id допускает NULL, а удаление сектора выполняет ON DELETE SET NULL: клиент сохраняется без классификации. На практике используемый сектор репозиторий старается деактивировать, а не удалять, чтобы сохранять смысл исторических данных.',
    1 => 'tags — общий справочник гибких меток. Связи contact_tags и client_tags реализуют many-to-many. Их составные первичные ключи одновременно служат уникальным ограничением: одну и ту же метку нельзя назначить сущности дважды. Обратные индексы по tag_id ускоряют выбор всех клиентов или контактов с тегом.',
    2 => 'client_contacts также является many-to-many связью, но содержит свойства самой связи: relation_label и is_primary. PRIMARY KEY (client_id, contact_id) разрешает одну связь между конкретной парой. Если одна и та же персона должна иметь две роли у клиента, их пока приходится описывать одним relation_label либо менять модель.',
  ),
  'examples' =>
  array (
    0 =>
    array (
      'title' => 'Клиенты с тегами без дублирования строк',
      'code' => 'SELECT
    c.id,
    c.commercial_name,
    GROUP_CONCAT(DISTINCT t.name ORDER BY t.name SEPARATOR \', \') AS tags
FROM clients c
LEFT JOIN client_tags ct ON ct.client_id = c.id
LEFT JOIN tags t ON t.id = ct.tag_id
GROUP BY c.id, c.commercial_name
ORDER BY c.commercial_name;',
    ),
  ),
),
array (
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
),
array (
  'id' => 'database-import-export',
  'title' => 'Импорт и экспорт',
  'paragraphs' =>
  array (
    0 => 'import_batches — заголовок одной загрузки: пользователь, исходное и сохранённое имя файла, формат, тип сущности, status, счётчики и JSON-соответствие колонок. Статусы образуют жизненный цикл uploaded → previewed → processing → completed или partial; failed используется при общей ошибке. Условный UPDATE в claimForProcessing не позволяет двум запросам одновременно забрать одну партию.',
    1 => 'import_rows и import_errors содержат диагностические сведения по строкам. Текущий процесс записывает в import_rows прежде всего пропущенные и ошибочные строки вместе с raw_data, а import_errors даёт отдельный список сообщений. Удаление партии каскадно очищает строки и ошибки; удаление созданного контакта или клиента только обнуляет related_*_id.',
    2 => 'export_batches хранит историю формирования выгрузки: выбранные фильтры и поля в JSON, имя, формат, количество строк, статус и время завершения. Сейчас CSV/XLSX передаётся непосредственно в php://output; stored_filename является именем скачивания и записью истории, а не гарантией наличия готового файла на диске.',
  ),
  'examples' =>
  array (
    0 =>
    array (
      'title' => 'Состояния импорта',
      'code' => 'uploaded
   │
   ├── previewed ──┐
   │               │
   └───────────────┴── processing
                           │
                  ┌────────┼────────┐
                  ▼        ▼        ▼
              completed  partial  failed',
    ),
    1 =>
    array (
      'title' => 'Безопасный захват партии',
      'code' => 'UPDATE import_batches
SET status = \'processing\', started_at = NOW()
WHERE id = :id
  AND status IN (\'uploaded\', \'previewed\');

-- Обработку начинает только процесс, для которого rowCount() === 1.',
    ),
  ),
),
array (
  'id' => 'database-api',
  'title' => 'API-ключи и журнал запросов',
  'paragraphs' =>
  array (
    0 => 'api_keys хранит имя интеграции, уникальный client_id, SHA-256-хеш secret, JSON-массив scopes, активность и даты использования или отзыва. Открытый secret показывается только при создании и в таблицу не записывается. Проверка выполняется через hash_equals, поэтому восстановить потерянный secret из базы нельзя — нужно выпустить новый ключ.',
    1 => 'api_logs получает запись для каждого API-запроса, включая неуспешную аутентификацию. request_id уникален и возвращается клиенту в X-Request-Id. Журнал содержит метод, логический путь, статус, код ошибки, длительность, IP, origin и обрезанные тела запроса и ответа. Код ограничивает каждое тело примерно 64 КБ.',
    2 => 'При удалении API-ключа api_key_id в журнале становится NULL, но request_id и остальные сведения сохраняются. Автоматической политики удаления api_logs пока нет, поэтому на рабочей системе необходимо определить срок хранения с учётом объёма, диагностики и требований к персональным данным.',
  ),
  'examples' =>
  array (
    0 =>
    array (
      'title' => 'Ошибки API за последние сутки',
      'code' => 'SELECT
    request_id,
    method,
    path,
    response_status,
    error_code,
    duration_ms,
    created_at
FROM api_logs
WHERE response_status >= 400
  AND created_at >= NOW() - INTERVAL 1 DAY
ORDER BY id DESC;',
    ),
  ),
),
array (
  'id' => 'database-audit',
  'title' => 'Аудит изменений',
  'paragraphs' =>
  array (
    0 => 'Таблица audit_logs предусмотрена для истории действий пользователя: action, тип и ID сущности, старые и новые значения в JSON, IP, user agent и время. Внешний ключ на пользователя использует SET NULL, чтобы история переживала удаление учётной записи.',
    1 => 'Важно: в текущем коде нет AuditRepository или сервиса, который записывает строки в audit_logs. Само наличие таблицы не означает, что изменения клиентов и контактов уже аудируются. До реализации записи на эти данные нельзя опираться при расследовании действий пользователя.',
    2 => 'Правильная реализация должна записывать аудит в той же транзакции, что и изменение сущности, либо через гарантированную очередь. Следует хранить только нужные поля и маскировать пароли, API secrets и другие чувствительные значения. Ошибка аудита не должна незаметно создавать ложное ощущение полной истории.',
  ),
  'examples' =>
  array (
    0 =>
    array (
      'title' => 'Предполагаемая запись аудита',
      'code' => 'INSERT INTO audit_logs (
    user_id, action, entity_type, entity_id,
    old_values, new_values, ip_address, user_agent
) VALUES (
    :user_id, \'contact.updated\', \'contact\', :contact_id,
    :old_values_json, :new_values_json, :ip, :user_agent
);',
    ),
  ),
),
array (
  'id' => 'database-integrity',
  'title' => 'Внешние ключи и правила удаления',
  'paragraphs' =>
  array (
    0 => 'CASCADE используется для зависимых данных, которые не имеют смысла без владельца: user_permissions, user_preferences, связи тегов, client_contacts, варианты поля, импортированные строки и ошибки. SET NULL применяется к историческим ссылкам: автор записи, пользователь партии, сектор клиента, созданный импортом объект и ключ API в журнале.',
    1 => 'RESTRICT защищает системную роль, пока к ней привязаны пользователи. UNIQUE отвечает за бизнес-уникальность: email пользователя и контакта, commercial_name клиента, name и slug справочников, client_id API, request_id журнала и составные пары связей. CHECK запрещает пустые или неочищенные ключевые строки, недопустимые boolean-флаги и одновременную запись нескольких типизированных значений custom_field_values.',
    2 => 'Не все предметные правила находятся в constraints. Primary-контакт не ограничен одним на клиента, обязательность пользовательского поля проверяет приложение, а полиморфные custom_field_values не имеют внешнего ключа на сущность. При прямых SQL-изменениях или новых репозиториях эти инварианты нужно соблюдать явно.',
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
),
array (
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
),
array (
  'id' => 'database-transactions',
  'title' => 'Транзакции и конкурентные изменения',
  'paragraphs' =>
  array (
    0 => 'Транзакция должна охватывать весь бизнес-инвариант. Если создаётся контакт, затем его связи с клиентами, теги и пользовательские поля, commit допустим только после успешного выполнения всех шагов. Иначе исключение может оставить частично созданный объект.',
    1 => 'Query Builder через Database::table() и старый PDO-код через Database::connect() используют одно подключение. Составные операции следует выполнять через Database::transaction(): helper открывает и завершает транзакцию, если является её владельцем, либо присоединяет callback к уже открытой транзакции API-пакета или строки импорта. Исключение автоматически приводит к rollback владельца и пробрасывается дальше.',
    2 => 'Транзакция сама по себе не предотвращает два одновременных решения на основании устаревших данных. Для захвата работы используйте условный UPDATE и rowCount(), как в импорте; для строгого редактирования — SELECT ... FOR UPDATE или optimistic locking с версией/updated_at. Уникальность лучше закреплять UNIQUE-индексом, а конфликт обрабатывать как ожидаемую ошибку.',
  ),
  'examples' =>
  array (
    0 =>
    array (
      'title' => 'Граница транзакции в сервисе',
      'code' => 'Database::transaction(function (): int {
    $contactId = $this->contacts->create($contact);
    $this->contacts->syncClients($contactId, $clientIds);
    $this->entityTags->sync(\'contact\', $contactId, $tagIds);
    $this->customFields->saveValues(\'contact\', $contactId, $fields, $values);

    return $contactId;
});',
    ),
  ),
),
array (
  'id' => 'database-schema-changes',
  'title' => 'Изменение схемы и миграции',
  'paragraphs' =>
  array (
    0 => 'database/schema.sql — полный снимок для чистой установки. В начале он отключает проверку внешних ключей и выполняет DROP TABLE, поэтому запуск этого файла на рабочей базе уничтожит данные. Для обновления существующей системы используются отдельные последовательные SQL-файлы в database/migrations; автоматического runner и таблицы учёта применённых версий пока нет.',
    1 => 'Каждое изменение нужно оформлять отдельным SQL-файлом с уникальной датой и названием, применять сначала на копии базы и фиксировать факт выполнения во внешнем журнале развёртывания. Файл должен содержать только переход от одной версии схемы к следующей, а schema.sql после проверки обновляется для новых установок. Миграции 20260729 сначала переводят разрешения в fail-closed режим, затем добавляют UNIQUE- и CHECK-ограничения после диагностики существующих данных.',
    2 => 'Перед ALTER TABLE сделайте резервную копию, оцените размер и блокировку таблицы и подготовьте совместимость кода при поэтапном развёртывании. DDL в MySQL может выполнять неявный commit, поэтому нельзя считать, что обычный START TRANSACTION гарантированно откатит изменение схемы. Откат описывают отдельно и проверяют на тестовой базе.',
  ),
  'examples' =>
  array (
    0 =>
    array (
      'title' => 'Рекомендуемая структура миграций',
      'code' => 'database/
├── schema.sql
└── migrations/
    ├── 20260729_fail_closed_permissions.sql
    └── 20260729_enforce_database_constraints.sql',
    ),
    1 =>
    array (
      'title' => 'Пример прямой миграции',
      'code' => 'ALTER TABLE contacts
    ADD COLUMN source VARCHAR(100) NULL AFTER company,
    ADD INDEX idx_contacts_source (source);

-- После проверки приложения то же поле добавляется в актуальный schema.sql.',
    ),
  ),
),
array (
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
),
array (
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
),
    ],
];
