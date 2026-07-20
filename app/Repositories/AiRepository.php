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
}
