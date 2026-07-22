<?php

class AiRepository
{
    // Contacts eligible for AI company lookup: corporate email, no company name, not yet reviewed.
    private const MISSING_COMPANY_WHERE = "
        contacts.is_corporate_email = 1
        AND TRIM(contacts.company) = ''
        AND contacts.email IS NOT NULL
        AND contacts.email LIKE '%@%'
        AND contacts.company_change_date IS NULL
    ";

    // int
    public function countMissingCompany(): int
    {
        $pdo = Database::connect();
        $statement = $pdo->query('SELECT COUNT(*) FROM contacts WHERE ' . self::MISSING_COMPANY_WHERE);

        return (int) $statement->fetchColumn();
    }

    // int
    public function countMissingCompanyDomains(): int
    {
        $pdo = Database::connect();
        $statement = $pdo->query(
            "SELECT COUNT(DISTINCT SUBSTRING_INDEX(contacts.email, '@', -1)) FROM contacts WHERE " . self::MISSING_COMPANY_WHERE
        );

        return (int) $statement->fetchColumn();
    }

    // array<{id, full_name, email, domain, domain_contacts}>
    public function paginateMissingCompany(int $page, int $perPage, string $sort = 'domain', string $dir = 'asc'): array
    {
        $pdo = Database::connect();
        $offset = ($page - 1) * $perPage;

        $allowed = [
            'id'              => 'contacts.id',
            'full_name'       => 'contacts.full_name',
            'email'           => 'contacts.email',
            'domain'          => 'domain',
            'domain_contacts' => 'domain_contacts',
        ];
        $orderCol = $allowed[$sort] ?? 'domain';
        $orderDir = $dir === 'desc' ? 'DESC' : 'ASC';

        $where = self::MISSING_COMPANY_WHERE;

        $sql = "
            SELECT
                contacts.id,
                contacts.full_name,
                contacts.email,
                SUBSTRING_INDEX(contacts.email, '@', -1) AS domain,
                domains.contacts_count AS domain_contacts
            FROM contacts
            INNER JOIN (
                SELECT SUBSTRING_INDEX(email, '@', -1) AS domain, COUNT(*) AS contacts_count
                FROM contacts
                WHERE {$where}
                GROUP BY SUBSTRING_INDEX(email, '@', -1)
            ) AS domains ON domains.domain = SUBSTRING_INDEX(contacts.email, '@', -1)
            WHERE {$where}
            ORDER BY {$orderCol} {$orderDir}, contacts.id ASC
            LIMIT :limit OFFSET :offset
        ";

        $statement = $pdo->prepare($sql);
        $statement->bindValue('limit', $perPage, PDO::PARAM_INT);
        $statement->bindValue('offset', $offset, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    // Clients eligible for AI website/sector enrichment: missing a website or a sector.
    private const MISSING_CLIENT_ENRICHMENT_WHERE = "
        (clients.website IS NULL OR TRIM(clients.website) = '')
        OR clients.sector_id IS NULL
    ";

    // int
    public function countMissingClientEnrichment(): int
    {
        $pdo = Database::connect();
        $statement = $pdo->query('SELECT COUNT(*) FROM clients WHERE ' . self::MISSING_CLIENT_ENRICHMENT_WHERE);

        return (int) $statement->fetchColumn();
    }

    // array<{id, commercial_name, legal_name, website, sector_id, sector_name}>
    public function paginateMissingClientEnrichment(int $page, int $perPage, string $sort = 'commercial_name', string $dir = 'asc'): array
    {
        $pdo = Database::connect();
        $offset = ($page - 1) * $perPage;

        $allowed = [
            'id'              => 'clients.id',
            'commercial_name' => 'clients.commercial_name',
            'legal_name'      => 'clients.legal_name',
            'website'         => 'clients.website',
        ];
        $orderCol = $allowed[$sort] ?? 'clients.commercial_name';
        $orderDir = $dir === 'desc' ? 'DESC' : 'ASC';

        $sql = "
            SELECT clients.id, clients.commercial_name, clients.legal_name,
                   clients.website, clients.sector_id, sectors.name AS sector_name
            FROM clients
            LEFT JOIN sectors ON sectors.id = clients.sector_id
            WHERE " . self::MISSING_CLIENT_ENRICHMENT_WHERE . "
            ORDER BY {$orderCol} {$orderDir}, clients.id ASC
            LIMIT :limit OFFSET :offset
        ";

        $statement = $pdo->prepare($sql);
        $statement->bindValue('limit', $perPage, PDO::PARAM_INT);
        $statement->bindValue('offset', $offset, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }
}
