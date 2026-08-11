<?php

abstract class AbstractApiService
{
    protected CustomFieldRepository $customFields;
    protected TagRepository $tags;
    protected EntityTagRepository $entityTags;

    public function __construct()
    {
        $this->customFields = new CustomFieldRepository();
        $this->tags = new TagRepository();
        $this->entityTags = new EntityTagRepository();
    }

    // The uniform CRUD surface ApiController dispatches to.
    abstract public function index(array $query): ApiResult;

    abstract public function show(int $id): ApiResult;

    abstract public function createBatch(array $items): ApiResult;

    abstract public function update(int $id, array $body): ApiResult;

    abstract public function destroy(int $id): ApiResult;

    protected function batch(array $items, callable $processor): ApiResult
    {
        $results = [];

        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                $results[] = $this->batchError($index, 'validation_error', ['Item must be a JSON object']);
                continue;
            }

            try {
                $data = Database::transaction(fn (): array => $processor($item));
                $results[] = ['index' => $index, 'success' => true, 'data' => $data];
            } catch (ApiException $exception) {
                $details = $exception->details() ?: [$exception->getMessage()];
                $results[] = $this->batchError($index, $exception->errorCode(), $details);
            } catch (WriteException $exception) {
                $results[] = $this->batchError(
                    $index,
                    $exception->apiCode(),
                    [$exception->getMessage()]
                );
            } catch (PDOException $exception) {
                $code = Database::isIntegrityViolation($exception) ? 'conflict' : 'server_error';
                $message = $code === 'conflict'
                    ? 'A record with these values already exists'
                    : 'Unable to process item';
                $results[] = $this->batchError($index, $code, [$message]);
            } catch (Throwable $exception) {
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

    /**
     * Converts the API slug => value object into the repository field/value
     * contract consumed by the shared write services.
     *
     * @return array{0: array, 1: array}
     */
    protected function customFieldWriteData(string $entityType, array $input, bool $applyDefaults = false): array
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

        return [$fields, $values];
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
        return Strings::nullable($value);
    }

    // Accepts a single name, a comma-separated string of names, or a JSON array of names.
    // Non-scalar array elements are skipped instead of triggering an array-to-string cast.
    protected function splitNames(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        $items = is_array($value) ? $value : explode(',', (string) $value);

        $names = [];
        foreach ($items as $item) {
            if (!is_scalar($item)) {
                continue;
            }
            $name = trim((string) $item);
            if ($name !== '') {
                $names[] = $name;
            }
        }

        return $names;
    }

    // Resolves tag names to ids, auto-creating any that don't exist yet (with no color).
    // Returns [int[] $ids, bool $anyCreated].
    protected function resolveTagIds(array $names): array
    {
        $ids = [];
        $created = false;

        foreach ($names as $name) {
            $tag = $this->tags->findByName($name);
            if ($tag === null) {
                $ids[] = $this->tags->create($name, null);
                $created = true;
            } else {
                $ids[] = (int) $tag['id'];
            }
        }

        return [array_values(array_unique($ids)), $created];
    }

    protected function formatTags(array $tags): array
    {
        return array_map(fn (array $tag): array => [
            'id' => (int) $tag['id'],
            'name' => $tag['name'],
        ], $tags);
    }

    // Folds top-level "custom_fields.<slug>" keys into a nested custom_fields array, so flat
    // dot-notation works the same on every create/update endpoint that accepts custom fields.
    protected function expandCustomFieldKeys(array $item): array
    {
        foreach ($item as $key => $value) {
            if (!is_string($key) || !str_starts_with($key, 'custom_fields.')) {
                continue;
            }

            $slug = substr($key, 14);
            if (!isset($item['custom_fields']) || !is_array($item['custom_fields'])) {
                $item['custom_fields'] = [];
            }
            $item['custom_fields'][$slug] = $value;
            unset($item[$key]);
        }

        return $item;
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
