<?php

class DashboardRepository
{
    // int
    public function countContacts(): int
    {
        return $this->countTable('contacts');
    }

    // int
    public function countClients(): int
    {
        return $this->countTable('clients');
    }

    // int
    public function countSectors(): int
    {
        return $this->countTable('sectors');
    }

    // int
    public function countTags(): int
    {
        return $this->countTable('tags');
    }

    // array<{id, full_name, email, phone, company, created_at}>
    public function latestContacts(int $limit = 25): array
    {
        return Database::rows(
            Database::table('contacts')
                ->select(['id', 'full_name', 'email', 'phone', 'company', 'created_at'])
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->limit($limit)
        );
    }

    // array<{id, commercial_name, sector_name, contacts_count}>
    public function clientsWithMostContacts(int $limit = 10): array
    {
        return Database::rows(
            Database::table('clients')
                ->leftJoin('client_contacts', 'client_contacts.client_id', '=', 'clients.id')
                ->leftJoin('sectors', 'sectors.id', '=', 'clients.sector_id')
                ->select(['clients.id', 'clients.commercial_name'])
                ->selectRaw("COALESCE(sectors.name, '') AS sector_name")
                ->selectRaw('COUNT(client_contacts.contact_id) AS contacts_count')
                ->groupBy(['clients.id', 'clients.commercial_name', 'sectors.name'])
                ->orderByDesc('contacts_count')
                ->orderBy('clients.commercial_name')
                ->limit($limit)
        );
    }

    // array<{sector_name, clients_count}>
    public function clientsBySector(int $limit = 20): array
    {
        return Database::rows(
            Database::table('clients')
                ->leftJoin('sectors', 'sectors.id', '=', 'clients.sector_id')
                ->selectRaw("COALESCE(sectors.name, 'No sector') AS sector_name")
                ->selectRaw('COUNT(clients.id) AS clients_count')
                ->groupBy(['sectors.id', 'sectors.name'])
                ->orderByDesc('clients_count')
                ->orderBy('sector_name')
                ->limit($limit)
        );
    }

    // int
    private function countTable(string $table): int
    {
        // Table names are fixed in code, not user input.
        return Database::table($table)->count();
    }
}
