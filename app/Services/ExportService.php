<?php

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;

class ExportService
{
    public function fieldDefinitions(string $entity, array $customFields): array
    {
        return $entity === 'clients'
            ? $this->clientsFieldDefs($customFields)
            : $this->contactsFieldDefs($customFields);
    }

    public function query(string $entity, array $filters, array $fields, array $fieldDefs): array
    {
        [$query, $headers] = $entity === 'clients'
            ? $this->buildClientsQuery($filters, $fields, $fieldDefs)
            : $this->buildContactsQuery($filters, $fields, $fieldDefs);

        return ['query' => $query, 'headers' => $headers];
    }

    // ── Field definitions ──────────────────────────────────────────────────

    private function contactsFieldDefs(array $customFields): array
    {
        $fields = [
            'id'           => ['label' => 'ID',           'group' => 'Basic info'],
            'full_name'    => ['label' => 'Full name',    'group' => 'Basic info'],
            'email'        => ['label' => 'Email',        'group' => 'Basic info'],
            'phone'        => ['label' => 'Phone',        'group' => 'Basic info'],
            'company'      => ['label' => 'Company name', 'group' => 'Basic info'],
            'created_at'   => ['label' => 'Created',     'group' => 'Basic info'],
            'updated_at'   => ['label' => 'Updated',     'group' => 'Basic info'],
            'tags'         => ['label' => 'Tags',        'group' => 'Related data'],
            'client_names' => ['label' => 'Clients',     'group' => 'Related data'],
        ];

        foreach ($customFields as $cf) {
            $fields['cf_' . $cf['id']] = [
                'label' => $cf['name'],
                'group' => 'Custom fields',
            ];
        }

        return $fields;
    }

    private function clientsFieldDefs(array $customFields): array
    {
        $fields = [
            'id'              => ['label' => 'ID',              'group' => 'Basic info'],
            'commercial_name' => ['label' => 'Commercial name', 'group' => 'Basic info'],
            'legal_name'      => ['label' => 'Legal name',      'group' => 'Basic info'],
            'cif'             => ['label' => 'CIF / NIF',       'group' => 'Basic info'],
            'website'         => ['label' => 'Website',         'group' => 'Basic info'],
            'notes'           => ['label' => 'Notes',           'group' => 'Basic info'],
            'created_at'      => ['label' => 'Created',         'group' => 'Basic info'],
            'updated_at'      => ['label' => 'Updated',         'group' => 'Basic info'],
            'address'         => ['label' => 'Address',         'group' => 'Location'],
            'postal_code'     => ['label' => 'Postal code',     'group' => 'Location'],
            'city'            => ['label' => 'City',            'group' => 'Location'],
            'province'        => ['label' => 'Province',        'group' => 'Location'],
            'country'         => ['label' => 'Country',         'group' => 'Location'],
            'sector_name'     => ['label' => 'Sector',          'group' => 'Related data'],
            'tags'            => ['label' => 'Tags',            'group' => 'Related data'],
            'contact_count'   => ['label' => 'Contact count',   'group' => 'Related data'],
        ];

        foreach ($customFields as $cf) {
            $fields['cf_' . $cf['id']] = [
                'label' => $cf['name'],
                'group' => 'Custom fields',
            ];
        }

        return $fields;
    }

    // ── Field validation ───────────────────────────────────────────────────

    public function sanitizeFields(array $selected, array $fieldDefs): array
    {
        $valid = array_values(array_unique(array_intersect($selected, array_keys($fieldDefs))));
        return empty($valid) ? ['id'] : $valid;
    }

    // ── Query builders ─────────────────────────────────────────────────────

    private function buildContactsQuery(array $filters, array $fields, array $fieldDefs): array
    {
        $query = Database::table('contacts');
        $selects = [];
        $headers = [];
        $customFieldIds = [];
        $baseColumns = ['id', 'full_name', 'email', 'phone', 'company', 'created_at', 'updated_at'];

        foreach ($fields as $field) {
            if (!isset($fieldDefs[$field])) {
                continue;
            }

            $headers[] = $fieldDefs[$field]['label'];

            if ($field === 'tags') {
                $query->leftJoinSub(
                    $this->tagsQuery('contact_tags', 'contact_id'),
                    '_tags_agg',
                    fn (JoinClause $join) => $join->on('_tags_agg.contact_id', '=', 'contacts.id')
                );
                $selects[] = Database::raw("COALESCE(_tags_agg.tag_names, '') AS `tags`");
            } elseif ($field === 'client_names') {
                $query->leftJoinSub(
                    $this->contactClientsQuery(),
                    '_cl_agg',
                    fn (JoinClause $join) => $join->on('_cl_agg.contact_id', '=', 'contacts.id')
                );
                $selects[] = Database::raw("COALESCE(_cl_agg.client_names, '') AS `client_names`");
            } elseif (str_starts_with($field, 'cf_')) {
                $customFieldIds[] = $id = (int) substr($field, 3);
                $selects[] = Database::raw("COALESCE(_cfv_agg.cf_{$id}, '') AS `cf_{$id}`");
            } elseif (in_array($field, $baseColumns, true)) {
                $selects[] = 'contacts.' . $field;
            }
        }

        if ($customFieldIds !== []) {
            $query->leftJoinSub(
                $this->customFieldsQuery('contact', $customFieldIds),
                '_cfv_agg',
                fn (JoinClause $join) => $join->on('_cfv_agg.entity_id', '=', 'contacts.id')
            );
        }

        $query->select($selects ?: ['contacts.id']);
        $this->applyLikeFilters($query, 'contacts', $filters, ['full_name', 'email', 'phone']);

        if (($filters['company'] ?? '') === 'company') {
            $query->where('contacts.company', '!=', '');
        } elseif (($filters['company'] ?? '') === 'person') {
            $query->where('contacts.company', '');
        }

        if (!empty($filters['client_id'])) {
            $query->whereExists(fn (Builder $related) => $related
                ->selectRaw('1')
                ->from('client_contacts')
                ->whereColumn('client_contacts.contact_id', 'contacts.id')
                ->where('client_contacts.client_id', (int) $filters['client_id']));
        }

        $this->applyTagFilter($query, 'contacts', 'contact_tags', 'contact_id', $filters);

        return [$query->orderByDesc('contacts.id'), $headers];
    }

    private function buildClientsQuery(array $filters, array $fields, array $fieldDefs): array
    {
        $query = Database::table('clients');
        $selects = [];
        $headers = [];
        $customFieldIds = [];
        $baseColumns = ['id', 'commercial_name', 'legal_name', 'cif', 'address', 'postal_code', 'city', 'province', 'country', 'website', 'notes', 'created_at', 'updated_at'];

        foreach ($fields as $field) {
            if (!isset($fieldDefs[$field])) {
                continue;
            }

            $headers[] = $fieldDefs[$field]['label'];

            if ($field === 'sector_name') {
                $query->leftJoin('sectors as _sect', '_sect.id', '=', 'clients.sector_id');
                $selects[] = Database::raw("COALESCE(_sect.name, '') AS `sector_name`");
            } elseif ($field === 'tags') {
                $query->leftJoinSub(
                    $this->tagsQuery('client_tags', 'client_id'),
                    '_tags_agg',
                    fn (JoinClause $join) => $join->on('_tags_agg.client_id', '=', 'clients.id')
                );
                $selects[] = Database::raw("COALESCE(_tags_agg.tag_names, '') AS `tags`");
            } elseif ($field === 'contact_count') {
                $query->leftJoinSub(
                    $this->contactCountsQuery(),
                    '_cc_agg',
                    fn (JoinClause $join) => $join->on('_cc_agg.client_id', '=', 'clients.id')
                );
                $selects[] = Database::raw('COALESCE(_cc_agg.contact_count, 0) AS `contact_count`');
            } elseif (str_starts_with($field, 'cf_')) {
                $customFieldIds[] = $id = (int) substr($field, 3);
                $selects[] = Database::raw("COALESCE(_cfv_agg.cf_{$id}, '') AS `cf_{$id}`");
            } elseif (in_array($field, $baseColumns, true)) {
                $selects[] = 'clients.' . $field;
            }
        }

        if ($customFieldIds !== []) {
            $query->leftJoinSub(
                $this->customFieldsQuery('client', $customFieldIds),
                '_cfv_agg',
                fn (JoinClause $join) => $join->on('_cfv_agg.entity_id', '=', 'clients.id')
            );
        }

        $query->select($selects ?: ['clients.id']);
        $this->applyLikeFilters(
            $query,
            'clients',
            $filters,
            ['commercial_name', 'legal_name', 'city', 'country', 'province']
        );

        if (!empty($filters['sector_id'])) {
            $query->where('clients.sector_id', (int) $filters['sector_id']);
        }

        $this->applyTagFilter($query, 'clients', 'client_tags', 'client_id', $filters);

        return [$query->orderByDesc('clients.id'), $headers];
    }

    private function tagsQuery(string $pivotTable, string $entityColumn): Builder
    {
        return Database::table($pivotTable . ' as entity_tags')
            ->join('tags as export_tags', 'export_tags.id', '=', 'entity_tags.tag_id')
            ->select('entity_tags.' . $entityColumn)
            ->selectRaw("GROUP_CONCAT(DISTINCT export_tags.name ORDER BY export_tags.name SEPARATOR ', ') AS tag_names")
            ->groupBy('entity_tags.' . $entityColumn);
    }

    private function contactClientsQuery(): Builder
    {
        return Database::table('client_contacts as cc')
            ->join('clients as cl', 'cl.id', '=', 'cc.client_id')
            ->select('cc.contact_id')
            ->selectRaw("GROUP_CONCAT(DISTINCT cl.commercial_name ORDER BY cl.commercial_name SEPARATOR ', ') AS client_names")
            ->groupBy('cc.contact_id');
    }

    private function contactCountsQuery(): Builder
    {
        return Database::table('client_contacts')
            ->select('client_id')
            ->selectRaw('COUNT(*) AS contact_count')
            ->groupBy('client_id');
    }

    private function customFieldsQuery(string $entityType, array $fieldIds): Builder
    {
        $query = Database::table('custom_field_values')
            ->select('entity_id')
            ->where('entity_type', $entityType)
            ->groupBy('entity_id');

        foreach ($fieldIds as $fieldId) {
            $query->selectRaw(
                "MAX(CASE WHEN field_id = ? THEN CONVERT(COALESCE(value_text, CAST(value_number AS CHAR), CAST(value_date AS CHAR), CAST(value_bool AS CHAR)) USING utf8mb4) END) AS `cf_{$fieldId}`",
                [$fieldId]
            );
        }

        return $query;
    }

    private function applyLikeFilters(Builder $query, string $table, array $filters, array $fields): void
    {
        foreach ($fields as $field) {
            if (!empty($filters[$field])) {
                $query->where($table . '.' . $field, 'like', '%' . $filters[$field] . '%');
            }
        }
    }

    private function applyTagFilter(
        Builder $query,
        string $table,
        string $pivotTable,
        string $entityColumn,
        array $filters
    ): void {
        $tagIds = IdList::normalize($filters['tag_ids'] ?? []);
        if ($tagIds === []) {
            return;
        }

        $query->whereExists(fn (Builder $tags) => $tags
            ->selectRaw('1')
            ->from($pivotTable)
            ->whereColumn($pivotTable . '.' . $entityColumn, $table . '.id')
            ->whereIn($pivotTable . '.tag_id', $tagIds));
    }
}
