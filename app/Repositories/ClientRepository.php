<?php

class ClientRepository
{
    public function paginate(int $page, int $perPage, array $filters = [], string $sort = 'id', string $dir = 'desc'): array
    {
        $pdo = Database::connect();
        $offset = ($page - 1) * $perPage;
        [$whereSql, $params] = $this->buildFilterSql($filters);

        $allowed  = [
            'id'              => 'clients.id',
            'commercial_name' => 'clients.commercial_name',
            'legal_name'      => 'clients.legal_name',
            'sector_name'     => 'sectors.name',
            'city'            => 'clients.city',
            'country'         => 'clients.country',
            'created_at'      => 'clients.created_at',
        ];
        $orderCol = $allowed[$sort] ?? 'clients.id';
        $orderDir = $dir === 'asc' ? 'ASC' : 'DESC';

        $sql = "
            SELECT clients.id, clients.commercial_name, clients.legal_name, clients.city,
                   clients.province, clients.country, clients.is_web_connected,
                   clients.created_at, clients.updated_at,
                   sectors.name AS sector_name
            FROM clients
            LEFT JOIN sectors ON sectors.id = clients.sector_id
            {$whereSql}
            ORDER BY {$orderCol} {$orderDir}
            LIMIT :limit OFFSET :offset
        ";

        $statement = $pdo->prepare($sql);
        foreach ($params as $name => $value) {
            $statement->bindValue($name, $value);
        }
        $statement->bindValue('limit', $perPage, PDO::PARAM_INT);
        $statement->bindValue('offset', $offset, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function tagsForClients(array $clientIds): array
    {
        if (empty($clientIds)) {
            return [];
        }

        $pdo = Database::connect();
        $placeholders = implode(',', array_fill(0, count($clientIds), '?'));

        $statement = $pdo->prepare("
            SELECT ct.client_id, t.id, t.name, t.color
            FROM client_tags ct
            INNER JOIN tags t ON t.id = ct.tag_id
            WHERE ct.client_id IN ($placeholders)
            ORDER BY t.name ASC
        ");
        $statement->execute($clientIds);

        $result = [];
        foreach ($statement->fetchAll() as $row) {
            $result[(int) $row['client_id']][] = $row;
        }

        return $result;
    }

    public function countAll(array $filters = []): int
    {
        $pdo = Database::connect();
        [$whereSql, $params] = $this->buildFilterSql($filters);

        $statement = $pdo->prepare("SELECT COUNT(*) AS total FROM clients {$whereSql}");
        foreach ($params as $name => $value) {
            $statement->bindValue($name, $value);
        }
        $statement->execute();
        $row = $statement->fetch();

        return (int) ($row['total'] ?? 0);
    }

    public function find(int $id): ?array
    {
        $pdo = Database::connect();

        $sql = "
            SELECT clients.*, sectors.name AS sector_name
            FROM clients
            LEFT JOIN sectors ON sectors.id = clients.sector_id
            WHERE clients.id = :id
            LIMIT 1
        ";

        $statement = $pdo->prepare($sql);
        $statement->execute(['id' => $id]);
        $client = $statement->fetch();

        return $client ?: null;
    }

    public function findByCommercialName(string $name): ?array
    {
        $pdo = Database::connect();

        $statement = $pdo->prepare('SELECT * FROM clients WHERE commercial_name = :name LIMIT 1');
        $statement->execute(['name' => $name]);
        $client = $statement->fetch();

        return $client ?: null;
    }

    public function distinctFieldValues(string $field, string $query = '', int $limit = 20, int $offset = 0): array
    {
        static $allowed = ['country', 'province', 'city'];
        if (!in_array($field, $allowed, true)) {
            return [];
        }

        $pdo = Database::connect();
        $where = "{$field} IS NOT NULL AND {$field} != ''";
        $params = [];

        if ($query !== '') {
            $where .= " AND {$field} LIKE :query";
            $params[':query'] = '%' . $query . '%';
        }

        $sql = "SELECT DISTINCT {$field} AS val FROM clients WHERE {$where} ORDER BY {$field} ASC LIMIT :limit OFFSET :offset";
        $statement = $pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $statement->bindValue($k, $v);
        }
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
        $statement->execute();

        return array_map(
            fn ($row) => ['id' => $row['val'], 'name' => $row['val']],
            $statement->fetchAll()
        );
    }

    public function search(string $query, int $limit = 20, int $offset = 0): array
    {
        $pdo = Database::connect();

        $sql = "
            SELECT id, commercial_name, legal_name
            FROM clients
            WHERE commercial_name LIKE :query
               OR legal_name LIKE :query
            ORDER BY commercial_name ASC
            LIMIT :limit OFFSET :offset
        ";

        $statement = $pdo->prepare($sql);
        $statement->bindValue('query', '%' . $query . '%');
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->bindValue('offset', $offset, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function create(array $data): int
    {
        $pdo = Database::connect();

        $sql = "
            INSERT INTO clients (
                commercial_name, legal_name, cif, address, postal_code, city,
                province, country, sector_id, website, notes, is_web_connected
            ) VALUES (
                :commercial_name, :legal_name, :cif, :address, :postal_code, :city,
                :province, :country, :sector_id, :website, :notes, :is_web_connected
            )
        ";

        $statement = $pdo->prepare($sql);
        $statement->execute($data);

        return (int) $pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $pdo = Database::connect();

        $sql = "
            UPDATE clients
            SET commercial_name = :commercial_name,
                legal_name = :legal_name,
                cif = :cif,
                address = :address,
                postal_code = :postal_code,
                city = :city,
                province = :province,
                country = :country,
                sector_id = :sector_id,
                website = :website,
                notes = :notes,
                is_web_connected = :is_web_connected
            WHERE id = :id
        ";

        $data['id'] = $id;

        $statement = $pdo->prepare($sql);
        $statement->execute($data);
    }

    public function delete(int $id): void
    {
        $pdo = Database::connect();

        $statement = $pdo->prepare('DELETE FROM clients WHERE id = :id');
        $statement->execute(['id' => $id]);
    }

    public function deleteMultiple(array $ids): void
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), fn ($id) => $id > 0)));

        if (empty($ids)) {
            return;
        }

        $pdo = Database::connect();
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $statement = $pdo->prepare("DELETE FROM clients WHERE id IN ($placeholders)");
        $statement->execute($ids);
    }

    public function contactsForClient(int $clientId): array
    {
        $pdo = Database::connect();

        $sql = "
            SELECT contacts.*, client_contacts.relation_label, client_contacts.is_primary
            FROM contacts
            INNER JOIN client_contacts ON client_contacts.contact_id = contacts.id
            WHERE client_contacts.client_id = :client_id
            ORDER BY contacts.full_name ASC
        ";

        $statement = $pdo->prepare($sql);
        $statement->execute(['client_id' => $clientId]);

        return $statement->fetchAll();
    }

    public function firstTags(int $limit = 50): array
    {
        $pdo = Database::connect();

        $statement = $pdo->prepare('SELECT * FROM tags ORDER BY name ASC LIMIT :limit');
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function tagsForClient(int $clientId): array
    {
        $pdo = Database::connect();

        $sql = "
            SELECT tags.*
            FROM tags
            INNER JOIN client_tags ON client_tags.tag_id = tags.id
            WHERE client_tags.client_id = :client_id
            ORDER BY tags.name ASC
        ";

        $statement = $pdo->prepare($sql);
        $statement->execute(['client_id' => $clientId]);

        return $statement->fetchAll();
    }

    public function syncTags(int $clientId, array $tagIds): void
    {
        $pdo = Database::connect();

        $delete = $pdo->prepare('DELETE FROM client_tags WHERE client_id = :client_id');
        $delete->execute(['client_id' => $clientId]);

        if (empty($tagIds)) {
            return;
        }

        $insert = $pdo->prepare('
            INSERT INTO client_tags (client_id, tag_id)
            VALUES (:client_id, :tag_id)
        ');

        foreach ($tagIds as $tagId) {
            $insert->execute([
                'client_id' => $clientId,
                'tag_id' => $tagId,
            ]);
        }
    }

    public function addTags(int $clientId, array $tagIds): void
    {
        if (empty($tagIds)) {
            return;
        }

        $pdo = Database::connect();
        $insert = $pdo->prepare('
            INSERT IGNORE INTO client_tags (client_id, tag_id)
            VALUES (:client_id, :tag_id)
        ');

        foreach ($tagIds as $tagId) {
            $insert->execute([
                'client_id' => $clientId,
                'tag_id' => $tagId,
            ]);
        }
    }

    public function addContacts(int $clientId, array $contactIds): void
    {
        if (empty($contactIds)) {
            return;
        }

        $pdo = Database::connect();
        $insert = $pdo->prepare('
            INSERT IGNORE INTO client_contacts (client_id, contact_id)
            VALUES (:client_id, :contact_id)
        ');

        foreach ($contactIds as $contactId) {
            $insert->execute([
                'client_id' => $clientId,
                'contact_id' => $contactId,
            ]);
        }
    }

    public function addTagsToClients(array $clientIds, array $tagIds): void
    {
        $clientIds = array_values(array_unique(array_filter(array_map('intval', $clientIds), fn ($id) => $id > 0)));
        $tagIds = array_values(array_unique(array_filter(array_map('intval', $tagIds), fn ($id) => $id > 0)));

        if (empty($clientIds) || empty($tagIds)) {
            return;
        }

        $pdo = Database::connect();
        $insert = $pdo->prepare('
            INSERT IGNORE INTO client_tags (client_id, tag_id)
            VALUES (:client_id, :tag_id)
        ');

        foreach ($clientIds as $clientId) {
            foreach ($tagIds as $tagId) {
                $insert->execute([
                    'client_id' => $clientId,
                    'tag_id' => $tagId,
                ]);
            }
        }
    }

    public function removeTagsFromClients(array $clientIds, array $tagIds): void
    {
        $clientIds = array_values(array_unique(array_filter(array_map('intval', $clientIds), fn ($id) => $id > 0)));
        $tagIds = array_values(array_unique(array_filter(array_map('intval', $tagIds), fn ($id) => $id > 0)));

        if (empty($clientIds) || empty($tagIds)) {
            return;
        }

        $pdo = Database::connect();
        $clientPlaceholders = implode(',', array_fill(0, count($clientIds), '?'));
        $tagPlaceholders = implode(',', array_fill(0, count($tagIds), '?'));
        $statement = $pdo->prepare("
            DELETE FROM client_tags
            WHERE client_id IN ($clientPlaceholders)
            AND tag_id IN ($tagPlaceholders)
        ");
        $statement->execute(array_merge($clientIds, $tagIds));
    }

    private function buildFilterSql(array $filters): array
    {
        $where = [];
        $params = [];

        if (isset($filters['is_web_connected']) && $filters['is_web_connected'] !== '') {
            $where[] = 'clients.is_web_connected = :is_web_connected';
            $params['is_web_connected'] = (int) $filters['is_web_connected'];
        }

        foreach (['commercial_name', 'legal_name', 'cif', 'city', 'province', 'country', 'address', 'postal_code', 'website', 'notes'] as $field) {
            if (!empty($filters[$field])) {
                $where[] = "clients.{$field} LIKE :{$field}";
                $params[$field] = '%' . $filters[$field] . '%';
            }
        }

        if (!empty($filters['contact_name'])) {
            $where[] = "
                EXISTS (
                    SELECT 1
                    FROM client_contacts cc
                    INNER JOIN contacts con ON con.id = cc.contact_id
                    WHERE cc.client_id = clients.id
                    AND (
                        con.full_name LIKE :contact_name
                    )
                )
            ";
            $params['contact_name'] = '%' . $filters['contact_name'] . '%';
        }

        if (!empty($filters['sector_id'])) {
            $where[] = 'clients.sector_id = :sector_id';
            $params['sector_id'] = (int) $filters['sector_id'];
        }

        $tagIds = $this->cleanTagFilterIds($filters);

        if (!empty($tagIds)) {
            $tagPlaceholders = [];

            foreach ($tagIds as $index => $tagId) {
                $paramName = 'tag_id_' . $index;
                $tagPlaceholders[] = ':' . $paramName;
                $params[$paramName] = $tagId;
            }

            $where[] = '
                EXISTS (
                    SELECT 1
                    FROM client_tags
                    WHERE client_tags.client_id = clients.id
                    AND client_tags.tag_id IN (' . implode(',', $tagPlaceholders) . ')
                )
            ';
        }

        if (!empty($filters['created_from'])) {
            $where[] = 'clients.created_at >= :created_from';
            $params['created_from'] = $filters['created_from'] . ' 00:00:00';
        }

        if (!empty($filters['created_to'])) {
            $where[] = 'clients.created_at <= :created_to';
            $params['created_to'] = $filters['created_to'] . ' 23:59:59';
        }

        if (!empty($filters['updated_from'])) {
            $where[] = 'clients.updated_at >= :updated_from';
            $params['updated_from'] = $filters['updated_from'] . ' 00:00:00';
        }

        if (!empty($filters['updated_to'])) {
            $where[] = 'clients.updated_at <= :updated_to';
            $params['updated_to'] = $filters['updated_to'] . ' 23:59:59';
        }

        return [
            empty($where) ? '' : 'WHERE ' . implode(' AND ', $where),
            $params,
        ];
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

        $tagIds = array_map('intval', $tagIds);
        $tagIds = array_filter($tagIds, fn ($id) => $id > 0);

        return array_values(array_unique($tagIds));
    }
}
