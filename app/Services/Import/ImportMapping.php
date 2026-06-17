<?php

class ImportMapping
{
    private const FIELD_TYPES = ['text', 'textarea', 'number', 'date', 'email', 'url', 'select', 'checkbox'];

    public function fields(string $entityType): array
    {
        return $entityType === 'clients'
            ? [
                'commercial_name' => 'commercial_name',
                'legal_name' => 'legal_name',
                'cif' => 'cif',
                'address' => 'address',
                'postal_code' => 'postal_code',
                'city' => 'city',
                'province' => 'province',
                'country' => 'country',
                'website' => 'website',
                'notes' => 'notes',
                'sector' => 'sector',
                'tags' => 'tags',
                'contact' => 'contact',
            ]
            : [
                'full_name' => 'full_name',
                'email' => 'email',
                'phone' => 'phone',
                'company' => 'company',
                'client' => 'client',
                'sector' => 'sector',
                'tags' => 'tags',
            ];
    }

    public function suggest(array $headers, string $entityType): array
    {
        $aliases = $entityType === 'clients'
            ? [
                'commercial name' => 'commercial_name', 'commercial_name' => 'commercial_name',
                'company' => 'commercial_name', 'client' => 'commercial_name',
                'legal name' => 'legal_name', 'legal_name' => 'legal_name',
                'cif' => 'cif', 'nif' => 'cif', 'address' => 'address',
                'postal code' => 'postal_code', 'postal_code' => 'postal_code',
                'city' => 'city', 'province' => 'province', 'country' => 'country',
                'website' => 'website', 'notes' => 'notes', 'sector' => 'sector',
                'sector_name' => 'sector', 'tags' => 'tags', 'tag' => 'tags',
                'contact' => 'contact', 'contact name' => 'contact', 'contact_name' => 'contact',
            ]
            : [
                'name' => 'full_name', 'full name' => 'full_name', 'full_name' => 'full_name',
                'first name' => 'full_name', 'first_name' => 'full_name',
                'email' => 'email', 'e-mail' => 'email', 'phone' => 'phone',
                'is_company' => 'company', 'company indicator' => 'company', 'company name' => 'company',
                'company' => 'client', 'client' => 'client', 'client_name' => 'client',
                'client name' => 'client', 'sector' => 'sector', 'sector_name' => 'sector',
                'tags' => 'tags', 'tag' => 'tags',
            ];

        $mapping = [];
        foreach ($headers as $header) {
            $mapping[$header] = $aliases[$this->lower(trim((string) $header))] ?? '';
        }
        return $mapping;
    }

    public function clean(array $mapping, string $entityType): array
    {
        $allowed = array_keys($this->fields($entityType));
        $clean = [];

        foreach ($mapping as $column => $field) {
            $column = trim((string) $column);
            $field = trim((string) $field);
            if ($column !== '' && (in_array($field, $allowed, true) || $field === '__custom')) {
                $clean[$column] = $field;
            }
        }
        return $clean;
    }

    public function cleanCustomFields(array $settings): array
    {
        $clean = [];
        foreach ($settings as $column => $fieldSettings) {
            if (!is_array($fieldSettings)) {
                continue;
            }
            $type = (string) ($fieldSettings['field_type'] ?? 'text');
            $clean[$column] = in_array($type, self::FIELD_TYPES, true) ? $type : 'text';
        }
        return $clean;
    }

    public function mapRow(array $row, array $mapping): array
    {
        $mapped = [];
        foreach ($mapping as $column => $field) {
            if ($field !== '__custom') {
                $mapped[$field] = trim((string) ($row[$column] ?? ''));
            }
        }
        return $mapped;
    }

    private function lower(string $value): string
    {
        return function_exists('mb_strtolower') ? mb_strtolower($value) : strtolower($value);
    }
}
