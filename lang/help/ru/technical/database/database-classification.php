<?php

return array (
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
);

