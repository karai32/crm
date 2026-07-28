<?php

return array (
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
);

