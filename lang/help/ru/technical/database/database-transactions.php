<?php

return array (
  'id' => 'database-transactions',
  'title' => 'Транзакции и конкурентные изменения',
  'paragraphs' => 
  array (
    0 => 'Транзакция должна охватывать весь бизнес-инвариант. Если создаётся контакт, затем его связи с клиентами, теги и пользовательские поля, commit допустим только после успешного выполнения всех шагов. Иначе исключение может оставить частично созданный объект.',
    1 => 'Все репозитории получают один и тот же PDO через Database::connect(), поэтому открытая сервисом транзакция распространяется на их запросы в рамках текущего PHP-запроса. В catch нужно проверять inTransaction(), выполнять rollBack и пробрасывать исходное исключение дальше.',
    2 => 'Транзакция сама по себе не предотвращает два одновременных решения на основании устаревших данных. Для захвата работы используйте условный UPDATE и rowCount(), как в импорте; для строгого редактирования — SELECT ... FOR UPDATE или optimistic locking с версией/updated_at. Уникальность лучше закреплять UNIQUE-индексом, а конфликт обрабатывать как ожидаемую ошибку.',
  ),
  'examples' => 
  array (
    0 => 
    array (
      'title' => 'Граница транзакции в сервисе',
      'code' => '$pdo = Database::connect();
$pdo->beginTransaction();

try {
    $contactId = $this->contacts->create($contact);
    $this->contacts->syncClients($contactId, $clientIds);
    $this->entityTags->sync(\'contact\', $contactId, $tagIds);
    $this->customFields->saveValues(\'contact\', $contactId, $fields, $values);
    $pdo->commit();
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    throw $exception;
}',
    ),
  ),
);

