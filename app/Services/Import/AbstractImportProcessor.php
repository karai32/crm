<?php

abstract class AbstractImportProcessor
{
    protected TagRepository $tags;
    protected SectorRepository $sectors;
    protected ContactRepository $contacts;
    protected CustomFieldRepository $customFields;
    private array $tagCache = [];
    private array $sectorCache = [];
    private array $contactCache = [];
    private array $customFieldCache = [];

    public function __construct()
    {
        $this->tags = new TagRepository();
        $this->sectors = new SectorRepository();
        $this->contacts = new ContactRepository();
        $this->customFields = new CustomFieldRepository();
    }

    abstract public function process(array $mapped, array $raw, array $mapping, array $customFieldTypes): void;

    protected function tagIds(string $value): array
    {
        $ids = [];
        foreach (preg_split('/[,;|]/', $value) ?: [] as $name) {
            $name = trim($name);
            if ($name === '') {
                continue;
            }

            $key = $this->lower($name);
            if (!isset($this->tagCache[$key])) {
                $tag = $this->tags->findByName($name);
                $this->tagCache[$key] = $tag ? (int) $tag['id'] : $this->tags->create($name, null);
            }
            $ids[] = $this->tagCache[$key];
        }
        return array_values(array_unique($ids));
    }

    // Strict lookup: unlike tags/sectors, contacts are never auto-created from a bare name --
    // the whole row fails if a referenced contact doesn't already exist.
    protected function contactIds(string $value): array
    {
        $ids = [];
        foreach (preg_split('/[,;|]/', $value) ?: [] as $name) {
            $name = trim($name);
            if ($name === '') {
                continue;
            }

            $key = $this->lower($name);
            if (!isset($this->contactCache[$key])) {
                $contact = $this->contacts->findByName($name);
                if ($contact === null) {
                    throw new ImportRowException("Contact '{$name}' not found.");
                }
                $this->contactCache[$key] = (int) $contact['id'];
            }
            $ids[] = $this->contactCache[$key];
        }
        return array_values(array_unique($ids));
    }

    protected function sectorId(string $name): ?int
    {
        $name = trim($name);
        if ($name === '') {
            return null;
        }

        $key = $this->lower($name);
        if (!isset($this->sectorCache[$key])) {
            $sector = $this->sectors->findByName($name);
            $this->sectorCache[$key] = $sector ? (int) $sector['id'] : $this->sectors->create($name);
        }
        return $this->sectorCache[$key];
    }

    protected function saveCustomFields(
        string $entity,
        int $entityId,
        array $raw,
        array $mapping,
        array $types
    ): void {
        $fields = [];
        $values = [];

        foreach ($mapping as $column => $field) {
            if ($field !== '__custom') {
                continue;
            }

            $type = $types[$column] ?? 'text';
            $customField = $this->customField($column, $entity, $type);
            $fields[] = $customField;
            $value = $raw[$column] ?? '';
            $values[(int) $customField['id']] = $type === 'checkbox'
                ? $this->boolValue((string) $value)
                : $value;
        }

        if ($fields !== []) {
            $this->customFields->saveValues($entity, $entityId, $fields, $values);
        }
    }

    protected function nullable(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));
        return $value === '' ? null : $value;
    }

    protected function boolValue(string $value): int
    {
        return in_array($this->lower(trim($value)), ['1', 'yes', 'true', 'si'], true) ? 1 : 0;
    }

    private function customField(string $column, string $entity, string $type): array
    {
        $slug = $this->slug($column);
        $key = $entity . ':' . $slug;
        if (isset($this->customFieldCache[$key])) {
            return $this->customFieldCache[$key];
        }

        $field = $this->customFields->findByEntityAndSlug($entity, $slug);
        if ($field === null) {
            $id = $this->customFields->create([
                'entity_type' => $entity,
                'name' => $column,
                'slug' => $slug,
                'field_type' => $type,
                'is_required' => 0,
                'is_filterable' => 1,
                'sort_order' => 0,
            ]);
            $field = $this->customFields->find($id);
        }

        if ($field === null) {
            throw new RuntimeException('Could not create custom field.');
        }
        return $this->customFieldCache[$key] = $field;
    }

    private function slug(string $value): string
    {
        $slug = preg_replace('/[^a-z0-9]+/i', '-', $this->lower(trim($value)));
        $slug = trim((string) $slug, '-');
        return $slug !== '' ? $slug : 'custom-field-' . substr(hash('sha256', $value), 0, 12);
    }

    private function lower(string $value): string
    {
        return function_exists('mb_strtolower') ? mb_strtolower($value) : strtolower($value);
    }
}
