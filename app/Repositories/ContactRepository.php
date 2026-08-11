<?php

use Illuminate\Database\Query\Builder;

class ContactRepository
{
    // array<{id, full_name, email, phone, created_at, is_corporate_email, email_status}>
    public function paginate(int $page, int $perPage, array $filters = [], string $sort = 'id', string $dir = 'desc'): array
    {
        $allowed  = [
            'id'         => 'contacts.id',
            'full_name'  => 'contacts.full_name',
            'email'      => 'contacts.email',
            'created_at' => 'contacts.created_at',
        ];
        $order = $sort === 'clients'
            ? Database::table('client_contacts as cc')
                ->join('clients as cl', 'cl.id', '=', 'cc.client_id')
                ->whereColumn('cc.contact_id', 'contacts.id')
                ->selectRaw('MIN(cl.commercial_name)')
            : ($allowed[$sort] ?? 'contacts.id');

        return Database::rows(
            $this->filtered($filters)
                ->select(['id', 'full_name', 'email', 'phone', 'created_at', 'is_corporate_email', 'email_status'])
                ->orderBy($order, $dir === 'asc' ? 'asc' : 'desc')
                ->limit($perPage)
                ->offset(($page - 1) * $perPage)
        );
    }

    // int
    public function countAll(array $filters = []): int
    {
        return $this->filtered($filters)->count();
    }

    // ?{id, full_name, email, is_corporate_email, email_status, phone, company, created_by, updated_by, created_at, updated_at}
    public function find(int $id): ?array
    {
        return Database::row(Database::table('contacts')->where('id', $id));
    }

    // ?{id, full_name, email, is_corporate_email, email_status, phone, company, created_by, updated_by, created_at, updated_at}
    public function findByName(string $name): ?array
    {
        return Database::row(Database::table('contacts')->where('full_name', trim($name)));
    }

    // bool
    public function emailExists(string $email): bool
    {
        return Database::table('contacts')->where('email', trim($email))->exists();
    }

    // bool
    public function emailTakenByOther(string $email, int $excludeId): bool
    {
        return Database::table('contacts')
            ->where('email', trim($email))
            ->where('id', '!=', $excludeId)
            ->exists();
    }

    // array<{id, full_name, email, phone}>
    public function search(string $query, int $limit = 10): array
    {
        $like = '%' . $query . '%';

        return Database::rows(
            Database::table('contacts')
                ->select(['id', 'full_name', 'email', 'phone'])
                ->where(fn (Builder $builder) => $builder
                    ->where('full_name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('phone', 'like', $like))
                ->orderBy('full_name')
                ->limit($limit)
        );
    }

    public function create(array $data): int
    {
        return Database::table('contacts')->insertGetId($data);
    }

    public function update(int $id, array $data): void
    {
        Database::table('contacts')->where('id', $id)->update($data);
    }

    public function delete(int $id): void
    {
        Database::table('contacts')->where('id', $id)->delete();
    }

    public function deleteMultiple(array $ids): void
    {
        $ids = IdList::normalize($ids);

        if (empty($ids)) {
            return;
        }

        Database::table('contacts')->whereIn('id', $ids)->delete();
    }

    public function addClientsToContacts(array $contactIds, array $clientIds): void
    {
        $contactIds = IdList::normalize($contactIds);
        $clientIds  = IdList::normalize($clientIds);

        if (empty($contactIds) || empty($clientIds)) {
            return;
        }

        $rows = [];
        foreach ($contactIds as $contactId) {
            foreach ($clientIds as $clientId) {
                $rows[] = ['client_id' => $clientId, 'contact_id' => $contactId];
            }
        }

        Database::table('client_contacts')->insertOrIgnore($rows);
    }

    // array<contact_id, array<{contact_id, id, commercial_name, sector_icon, sector_name}>>
    public function clientsForContacts(array $contactIds): array
    {
        if (empty($contactIds)) {
            return [];
        }

        $rows = Database::rows(
            Database::table('client_contacts as cc')
                ->join('clients as cl', 'cl.id', '=', 'cc.client_id')
                ->leftJoin('sectors as s', 's.id', '=', 'cl.sector_id')
                ->select(['cc.contact_id', 'cl.id', 'cl.commercial_name'])
                ->addSelect(['s.icon AS sector_icon', 's.name AS sector_name'])
                ->whereIn('cc.contact_id', $contactIds)
                ->orderBy('cl.commercial_name')
        );

        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row['contact_id']][] = $row;
        }

        return $result;
    }

    // array<{id, commercial_name, legal_name}>
    public function firstClients(int $limit = 50): array
    {
        return Database::rows(
            Database::table('clients')
                ->select(['id', 'commercial_name', 'legal_name'])
                ->orderBy('commercial_name')
                ->limit($limit)
        );
    }

    // array<{id, name}>
    public function allSectors(): array
    {
        return Database::rows(Database::table('sectors')->select(['id', 'name'])->orderBy('name'));
    }

    // array<{id, sector_id, commercial_name, legal_name, cif, address, postal_code, city, province, country, website, notes, is_web_connected, is_web_connected_date, is_active, is_active_date, created_by, updated_by, created_at, updated_at, relation_label, is_primary}>
    public function clientsForContact(int $contactId): array
    {
        return Database::rows(
            Database::table('clients')
                ->join('client_contacts', 'client_contacts.client_id', '=', 'clients.id')
                ->select('clients.*')
                ->addSelect(['client_contacts.relation_label', 'client_contacts.is_primary'])
                ->where('client_contacts.contact_id', $contactId)
                ->orderBy('clients.commercial_name')
        );
    }

    public function syncClients(int $contactId, array $clientIds): void
    {
        Database::table('client_contacts')->where('contact_id', $contactId)->delete();

        if (empty($clientIds)) {
            return;
        }

        Database::table('client_contacts')->insert(array_map(
            static fn (int $clientId): array => ['client_id' => $clientId, 'contact_id' => $contactId],
            $clientIds
        ));
    }

    // int
    public function countUninspected(): int
    {
        return $this->uninspected()->count();
    }

    // array<{id, email}>
    public function uninspectedBatch(int $limit): array
    {
        return Database::rows($this->uninspected()->select(['id', 'email'])->orderBy('id')->limit($limit));
    }

    public function updateEmailInspection(int $id, ?int $isCorporate, ?string $status): void
    {
        Database::table('contacts')->where('id', $id)->update([
            'is_corporate_email' => $isCorporate,
            'email_status' => $status,
        ]);
    }

    public function updateCompany(int $id, string $company): void
    {
        Database::table('contacts')->where('id', $id)->update([
            'company' => $company,
            'company_change_date' => Database::raw('CURRENT_TIMESTAMP'),
        ]);
    }

    public function markCompanyReviewed(int $id): void
    {
        Database::table('contacts')->where('id', $id)->update([
            'company_change_date' => Database::raw('CURRENT_TIMESTAMP'),
        ]);
    }

    private function filtered(array $filters): Builder
    {
        $query = Database::table('contacts');

        foreach (['full_name', 'email', 'phone'] as $field) {
            if (!empty($filters[$field])) {
                $query->where("contacts.{$field}", 'like', '%' . $filters[$field] . '%');
            }
        }

        if (!empty($filters['client_id'])) {
            $query->whereExists(fn (Builder $clients) => $clients
                ->selectRaw('1')
                ->from('client_contacts')
                ->whereColumn('client_contacts.contact_id', 'contacts.id')
                ->where('client_contacts.client_id', (int) $filters['client_id']));
        }

        $tagIds = $this->cleanTagFilterIds($filters);

        if (!empty($tagIds)) {
            $query->whereExists(fn (Builder $tags) => $tags
                ->selectRaw('1')
                ->from('contact_tags')
                ->whereColumn('contact_tags.contact_id', 'contacts.id')
                ->whereIn('contact_tags.tag_id', $tagIds));
        }

        if (!empty($filters['sector_id'])) {
            $query->whereExists(fn (Builder $clients) => $clients
                ->selectRaw('1')
                ->from('client_contacts')
                ->join('clients', 'clients.id', '=', 'client_contacts.client_id')
                ->whereColumn('client_contacts.contact_id', 'contacts.id')
                ->where('clients.sector_id', (int) $filters['sector_id']));
        }

        if ($filters['is_corporate_email'] !== '' && $filters['is_corporate_email'] !== null) {
            $query->where('contacts.is_corporate_email', (int) $filters['is_corporate_email']);
        }

        if (!empty($filters['email_status'])) {
            $query->where('contacts.email_status', $filters['email_status']);
        }

        if (!empty($filters['created_from'])) {
            $query->where('contacts.created_at', '>=', $filters['created_from'] . ' 00:00:00');
        }

        if (!empty($filters['created_to'])) {
            $query->where('contacts.created_at', '<=', $filters['created_to'] . ' 23:59:59');
        }

        if (!empty($filters['updated_from'])) {
            $query->where('contacts.updated_at', '>=', $filters['updated_from'] . ' 00:00:00');
        }

        if (!empty($filters['updated_to'])) {
            $query->where('contacts.updated_at', '<=', $filters['updated_to'] . ' 23:59:59');
        }

        foreach (($filters['custom_fields'] ?? []) as $fieldId => $value) {
            $fieldId = (int) $fieldId;
            $value = trim((string) $value);

            if ($fieldId <= 0 || $value === '') {
                continue;
            }

            $like = '%' . $value . '%';
            $query->whereExists(fn (Builder $values) => $values
                ->selectRaw('1')
                ->from('custom_field_values')
                ->where('custom_field_values.entity_type', 'contact')
                ->whereColumn('custom_field_values.entity_id', 'contacts.id')
                ->where('custom_field_values.field_id', $fieldId)
                ->where(fn (Builder $match) => $match
                    ->where('custom_field_values.value_text', 'like', $like)
                    ->orWhereRaw('CAST(custom_field_values.value_number AS CHAR) LIKE ?', [$like])
                    ->orWhereRaw('CAST(custom_field_values.value_date AS CHAR) LIKE ?', [$like])
                    ->orWhereRaw('CAST(custom_field_values.value_bool AS CHAR) LIKE ?', [$like])));
        }

        return $query;
    }

    private function uninspected(): Builder
    {
        return Database::table('contacts')
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->whereNull('is_corporate_email');
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
