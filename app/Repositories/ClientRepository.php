<?php

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;

class ClientRepository
{
    // array<{id, commercial_name, legal_name, city, province, country, is_web_connected, is_active, created_at, updated_at, sector_id, sector_name, sector_icon}>
    public function paginate(int $page, int $perPage, array $filters = [], string $sort = 'id', string $dir = 'desc'): array
    {
        $allowed  = [
            'id'              => 'clients.id',
            'commercial_name' => 'clients.commercial_name',
            'legal_name'      => 'clients.legal_name',
            'sector_name'     => 'sectors.name',
            'city'            => 'clients.city',
            'country'         => 'clients.country',
            'created_at'      => 'clients.created_at',
            'is_active'       => 'clients.is_active',
        ];
        return Database::rows(
            $this->filtered($filters)
                ->leftJoin('sectors', 'sectors.id', '=', 'clients.sector_id')
                ->select([
                    'clients.id',
                    'clients.commercial_name',
                    'clients.legal_name',
                    'clients.city',
                    'clients.province',
                    'clients.country',
                    'clients.is_web_connected',
                    'clients.is_active',
                    'clients.created_at',
                    'clients.updated_at',
                    'clients.sector_id',
                    'sectors.name AS sector_name',
                    'sectors.icon AS sector_icon',
                ])
                ->orderBy($allowed[$sort] ?? 'clients.id', $dir === 'asc' ? 'asc' : 'desc')
                ->limit($perPage)
                ->offset(($page - 1) * $perPage)
        );
    }

    // int
    public function countAll(array $filters = []): int
    {
        return $this->filtered($filters)->count();
    }

    // ?{id, sector_id, commercial_name, legal_name, cif, address, postal_code, city, province, country, website, notes, is_web_connected, is_web_connected_date, is_active, is_active_date, created_by, updated_by, created_at, updated_at, sector_name}
    public function find(int $id): ?array
    {
        return Database::row(
            Database::table('clients')
                ->leftJoin('sectors', 'sectors.id', '=', 'clients.sector_id')
                ->select('clients.*')
                ->addSelect('sectors.name AS sector_name')
                ->where('clients.id', $id)
        );
    }

    // ?{id, sector_id, commercial_name, legal_name, cif, address, postal_code, city, province, country, website, notes, is_web_connected, is_web_connected_date, is_active, is_active_date, created_by, updated_by, created_at, updated_at}
    public function findByCommercialName(string $name): ?array
    {
        return Database::row(Database::table('clients')->where('commercial_name', trim($name)));
    }

    public function commercialNameTakenByOther(string $name, int $excludeId): bool
    {
        return Database::table('clients')
            ->where('commercial_name', trim($name))
            ->where('id', '!=', $excludeId)
            ->exists();
    }

    // array<{id, name}>
    public function distinctFieldValues(string $field, string $query = '', int $limit = 20, int $offset = 0): array
    {
        static $allowed = ['country', 'province', 'city'];
        if (!in_array($field, $allowed, true)) {
            return [];
        }

        return array_map(
            static fn (array $row): array => ['id' => $row['val'], 'name' => $row['val']],
            Database::rows(
                Database::table('clients')
                    ->distinct()
                    ->select("{$field} AS val")
                    ->whereNotNull($field)
                    ->where($field, '!=', '')
                    ->when($query !== '', fn (Builder $builder) => $builder->where($field, 'like', '%' . $query . '%'))
                    ->orderBy($field)
                    ->limit($limit)
                    ->offset($offset)
            )
        );
    }

    // array<{id, commercial_name, legal_name}>
    public function search(string $query, int $limit = 20, int $offset = 0): array
    {
        $like = '%' . $query . '%';

        return Database::rows(
            Database::table('clients')
                ->select(['id', 'commercial_name', 'legal_name'])
                ->where(fn (Builder $builder) => $builder
                    ->where('commercial_name', 'like', $like)
                    ->orWhere('legal_name', 'like', $like))
                ->orderBy('commercial_name')
                ->limit($limit)
                ->offset($offset)
        );
    }

    public function create(array $data): int
    {
        $data['is_web_connected'] = (int) (bool) ($data['is_web_connected'] ?? false);
        $data['is_active'] = (int) (bool) ($data['is_active'] ?? true);

        return Database::table('clients')->insertGetId($data);
    }

    public function update(int $id, array $data): void
    {
        $current = $this->find($id);
        $now     = Carbon::now()->toDateTimeString();

        $data['is_web_connected'] = (int) (bool) ($data['is_web_connected'] ?? ($current['is_web_connected'] ?? false));
        $data['is_active'] = (int) (bool) ($data['is_active'] ?? ($current['is_active'] ?? true));

        $data['is_web_connected_date'] = ($current && (int) ($data['is_web_connected'] ?? $current['is_web_connected']) !== (int) $current['is_web_connected'])
            ? $now : null;

        $data['is_active_date'] = ($current && (int) ($data['is_active'] ?? $current['is_active']) !== (int) $current['is_active'])
            ? $now : null;

        if ($data['is_web_connected_date'] === null) {
            unset($data['is_web_connected_date']);
        }
        if ($data['is_active_date'] === null) {
            unset($data['is_active_date']);
        }

        Database::table('clients')->where('id', $id)->update($data);
    }

    public function delete(int $id): void
    {
        Database::table('clients')->where('id', $id)->delete();
    }

    public function deleteMultiple(array $ids): void
    {
        $ids = IdList::normalize($ids);

        if (empty($ids)) {
            return;
        }

        Database::table('clients')->whereIn('id', $ids)->delete();
    }

    // array<{id, full_name, email, is_corporate_email, email_status, phone, company, created_by, updated_by, created_at, updated_at, relation_label, is_primary}>
    public function contactsForClient(int $clientId): array
    {
        return Database::rows(
            Database::table('contacts')
                ->join('client_contacts', 'client_contacts.contact_id', '=', 'contacts.id')
                ->select('contacts.*')
                ->addSelect(['client_contacts.relation_label', 'client_contacts.is_primary'])
                ->where('client_contacts.client_id', $clientId)
                ->orderBy('contacts.full_name')
        );
    }

    public function addContacts(int $clientId, array $contactIds): void
    {
        if (empty($contactIds)) {
            return;
        }

        Database::table('client_contacts')->insertOrIgnore(array_map(
            static fn (int $contactId): array => ['client_id' => $clientId, 'contact_id' => $contactId],
            $contactIds
        ));
    }

    private function filtered(array $filters): Builder
    {
        $query = Database::table('clients');

        if (isset($filters['is_web_connected']) && $filters['is_web_connected'] !== '') {
            $query->where('clients.is_web_connected', (int) $filters['is_web_connected']);
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $query->where('clients.is_active', (int) $filters['is_active']);
        }

        foreach (['commercial_name', 'legal_name', 'city', 'province', 'country', 'address', 'website'] as $field) {
            if (!empty($filters[$field])) {
                $query->where("clients.{$field}", 'like', '%' . $filters[$field] . '%');
            }
        }

        if (!empty($filters['sector_id'])) {
            $query->where('clients.sector_id', (int) $filters['sector_id']);
        }

        $tagIds = $this->cleanTagFilterIds($filters);

        if (!empty($tagIds)) {
            $query->whereExists(fn (Builder $tags) => $tags
                ->selectRaw('1')
                ->from('client_tags')
                ->whereColumn('client_tags.client_id', 'clients.id')
                ->whereIn('client_tags.tag_id', $tagIds));
        }

        if (!empty($filters['created_from'])) {
            $query->where('clients.created_at', '>=', $filters['created_from'] . ' 00:00:00');
        }

        if (!empty($filters['created_to'])) {
            $query->where('clients.created_at', '<=', $filters['created_to'] . ' 23:59:59');
        }

        if (!empty($filters['updated_from'])) {
            $query->where('clients.updated_at', '>=', $filters['updated_from'] . ' 00:00:00');
        }

        if (!empty($filters['updated_to'])) {
            $query->where('clients.updated_at', '<=', $filters['updated_to'] . ' 23:59:59');
        }

        foreach (($filters['custom_fields'] ?? []) as $fieldId => $value) {
            $fieldId = (int) $fieldId;
            $value   = trim((string) $value);

            if ($fieldId <= 0 || $value === '') {
                continue;
            }

            $like = '%' . $value . '%';
            $query->whereExists(fn (Builder $values) => $values
                ->selectRaw('1')
                ->from('custom_field_values')
                ->where('custom_field_values.entity_type', 'client')
                ->whereColumn('custom_field_values.entity_id', 'clients.id')
                ->where('custom_field_values.field_id', $fieldId)
                ->where(fn (Builder $match) => $match
                    ->where('custom_field_values.value_text', 'like', $like)
                    ->orWhereRaw('CAST(custom_field_values.value_number AS CHAR) LIKE ?', [$like])
                    ->orWhereRaw('CAST(custom_field_values.value_date AS CHAR) LIKE ?', [$like])
                    ->orWhereRaw('CAST(custom_field_values.value_bool AS CHAR) LIKE ?', [$like])));
        }

        return $query;
    }

    private function cleanTagFilterIds(array $filters): array
    {
        $tagIds = $filters['tag_ids'] ?? [];

        if (!is_array($tagIds)) {
            $tagIds = [];
        }

        if (!empty($filters['tag_id'])) {
            $tagIds[] = $filters['tag_id'];
        }

        return IdList::normalize($tagIds);
    }
}
