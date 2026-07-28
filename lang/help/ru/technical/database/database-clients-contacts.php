<?php

return array (
  'id' => 'database-clients-contacts',
  'title' => 'Клиенты и контакты',
  'paragraphs' => 
  array (
    0 => 'clients хранит организацию: коммерческое и юридическое название, CIF, адрес, сайт, сектор, заметки и два независимых состояния — активное сотрудничество и подключение сайта по API. Поля is_active_date и is_web_connected_date фиксируют момент изменения соответствующего состояния.',
    1 => 'contacts хранит человека и доступные способы связи. company — текстовое название компании, полученное вручную или через Gemini, и не заменяет связь с clients. is_corporate_email и email_status являются результатами классификации адреса; NULL означает отсутствие результата, unknown — классификацию без живой MX-проверки.',
    2 => 'created_by и updated_by указывают пользователя, выполнившего действие через интерфейс, когда этот контекст доступен. ON DELETE SET NULL сохраняет саму запись при удалении пользователя. Уникального ограничения на email контакта в схеме нет: проверку дубликатов выполняет прикладной код, поэтому прямой SQL способен создать повтор.',
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
);

