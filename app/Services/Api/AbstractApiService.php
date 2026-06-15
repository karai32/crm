<?php

abstract class AbstractApiService
{
    protected CustomFieldRepository $customFields;

    public function __construct()
    {
        $this->customFields = new CustomFieldRepository();
    }

    protected function batch(array $items, callable $processor): ApiResult
    {
        $results = [];

        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                $results[] = $this->batchError($index, 'validation_error', ['Item must be a JSON object']);
                continue;
            }

            $pdo = Database::connect();
            try {
                $pdo->beginTransaction();
                $data = $processor($item);
                $pdo->commit();
                $results[] = ['index' => $index, 'success' => true, 'data' => $data];
            } catch (ApiException $exception) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $details = $exception->details() ?: [$exception->getMessage()];
                $results[] = $this->batchError($index, $exception->errorCode(), $details);
            } catch (PDOException $exception) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $code = (string) $exception->getCode() === '23000' ? 'conflict' : 'server_error';
                $message = $code === 'conflict'
                    ? 'A record with these values already exists'
                    : 'Unable to process item';
                $results[] = $this->batchError($index, $code, [$message]);
            } catch (Throwable $exception) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log('API batch item failed: ' . $exception->getMessage());
                $results[] = $this->batchError($index, 'server_error', ['Unable to process item']);
            }
        }

        $created = count(array_filter($results, fn (array $result): bool => $result['success']));

        return new ApiResult(207, [
            'success' => true,
            'data' => [
                'processed' => count($results),
                'created' => $created,
                'failed' => count($results) - $created,
                'results' => $results,
            ],
        ], count($results));
    }

    protected function saveCustomFields(string $entityType, int $entityId, array $input, bool $applyDefaults = false): void
    {
        $fields = [];
        $values = [];
        $processedSlugs = [];

        foreach ($input as $slug => $value) {
            $field = $this->customFields->findByEntityAndSlug($entityType, (string) $slug);
            if ($field === null) {
                continue;
            }
            $fields[] = $field;
            $values[(int) $field['id']] = $value;
            $processedSlugs[] = $field['slug'];
        }

        // For creation: also apply defaults for fields not present in the input at all
        if ($applyDefaults) {
            foreach ($this->customFields->fieldsForEntity($entityType) as $field) {
                if (in_array($field['slug'], $processedSlugs, true)) {
                    continue;
                }
                $default = $field['default_value'] ?? null;
                if ($default === null || trim((string) $default) === '') {
                    continue;
                }
                $fields[] = $field;
                $values[(int) $field['id']] = $default;
            }
        }

        if ($fields !== []) {
            $this->customFields->saveValues($entityType, $entityId, $fields, $values);
        }
    }

    protected function customFieldData(string $entityType, int $entityId): array
    {
        $fields = $this->customFields->fieldsForEntity($entityType);
        $values = $this->customFields->valuesForEntity($entityType, $entityId);
        $result = [];

        foreach ($fields as $field) {
            $row = $values[(int) $field['id']] ?? null;
            $result[$field['slug']] = $row === null ? null : match ($field['field_type']) {
                'number' => $row['value_number'] !== null ? (float) $row['value_number'] : null,
                'date' => $row['value_date'],
                'boolean', 'checkbox' => $row['value_bool'] !== null ? (bool) $row['value_bool'] : null,
                default => $row['value_text'],
            };
        }

        return $result;
    }

    protected function requireRecord(?array $record, string $entity): array
    {
        if ($record === null) {
            throw new ApiException(404, 'not_found', ucfirst($entity) . ' not found');
        }

        return $record;
    }

    protected function nullableString(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));
        return $value === '' ? null : $value;
    }

    private function batchError(int $index, string $code, array $details): array
    {
        return [
            'index' => $index,
            'success' => false,
            'error' => ['code' => $code, 'details' => $details],
        ];
    }
}
