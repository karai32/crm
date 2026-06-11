<?php

class DashboardRepository
{
    public function countContacts(): int
    {
        return $this->countTable('contacts');
    }

    public function countClients(): int
    {
        return $this->countTable('clients');
    }

    public function countSectors(): int
    {
        return $this->countTable('sectors');
    }

    public function countTags(): int
    {
        return $this->countTable('tags');
    }

    public function latestContacts(int $limit = 5): array
    {
        $pdo = Database::connect();

        $sql = "
            SELECT id, first_name, last_name, email, phone, is_company, created_at
            FROM contacts
            ORDER BY created_at DESC, id DESC
            LIMIT :limit
        ";

        $statement = $pdo->prepare($sql);
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function clientsWithMostContacts(int $limit = 10): array
    {
        $pdo = Database::connect();

        $sql = "
            SELECT clients.id, clients.commercial_name,
                   COALESCE(sectors.name, '') AS sector_name,
                   COUNT(client_contacts.contact_id) AS contacts_count
            FROM clients
            LEFT JOIN client_contacts ON client_contacts.client_id = clients.id
            LEFT JOIN sectors ON sectors.id = clients.sector_id
            GROUP BY clients.id, clients.commercial_name, sectors.name
            ORDER BY contacts_count DESC, clients.commercial_name ASC
            LIMIT :limit
        ";

        $statement = $pdo->prepare($sql);
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function clientsBySector(int $limit = 20): array
    {
        $pdo = Database::connect();

        $sql = "
            SELECT COALESCE(sectors.name, 'No sector') AS sector_name, COUNT(clients.id) AS clients_count
            FROM clients
            LEFT JOIN sectors ON sectors.id = clients.sector_id
            GROUP BY sectors.id, sectors.name
            ORDER BY clients_count DESC, sector_name ASC
            LIMIT :limit
        ";

        $statement = $pdo->prepare($sql);
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    private function countTable(string $table): int
    {
        $pdo = Database::connect();

        // Table names are fixed in code, not user input.
        $statement = $pdo->prepare("SELECT COUNT(*) AS total FROM {$table}");
        $statement->execute();
        $row = $statement->fetch();

        return (int) ($row['total'] ?? 0);
    }
}
