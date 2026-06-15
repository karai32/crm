<?php

class ContactRepository
{
    public function paginate(int $page, int $perPage, array $filters = []): array
    {
        $pdo = Database::connect();
        $offset = ($page - 1) * $perPage;
        [$whereSql, $params] = $this->buildFilterSql($filters);

        $sql = "
            SELECT id, first_name, last_name, email, phone, created_at
            FROM contacts
            {$whereSql}
            ORDER BY id DESC
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

    public function countAll(array $filters = []): int
    {
        $pdo = Database::connect();
        [$whereSql, $params] = $this->buildFilterSql($filters);

        $statement = $pdo->prepare("SELECT COUNT(*) AS total FROM contacts {$whereSql}");
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

        $statement = $pdo->prepare('SELECT * FROM contacts WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $contact = $statement->fetch();

        return $contact ?: null;
    }

    public function emailExists(string $email): bool
    {
        $pdo = Database::connect();

        $statement = $pdo->prepare('SELECT id FROM contacts WHERE email = :email LIMIT 1');
        $statement->execute(['email' => $email]);

        return (bool) $statement->fetch();
    }

    public function emailTakenByOther(string $email, int $excludeId): bool
    {
        $pdo = Database::connect();

        $statement = $pdo->prepare('SELECT id FROM contacts WHERE email = :email AND id != :id LIMIT 1');
        $statement->execute(['email' => $email, 'id' => $excludeId]);

        return (bool) $statement->fetch();
    }

    public function search(string $query, int $limit = 10): array
    {
        $pdo = Database::connect();

        $sql = "
            SELECT id, first_name, last_name, email, phone
            FROM contacts
            WHERE first_name LIKE :query
               OR last_name LIKE :query
               OR email LIKE :query
               OR phone LIKE :query
            ORDER BY first_name ASC, last_name ASC
            LIMIT :limit
        ";

        $statement = $pdo->prepare($sql);
        $statement->bindValue('query', '%' . $query . '%');
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function create(array $data): int
    {
        $pdo = Database::connect();

        $sql = "
            INSERT INTO contacts (first_name, last_name, email, phone, is_company)
            VALUES (:first_name, :last_name, :email, :phone, :is_company)
        ";

        $statement = $pdo->prepare($sql);
        $statement->execute($data);

        return (int) $pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $pdo = Database::connect();

        $sql = "
            UPDATE contacts
            SET first_name = :first_name,
                last_name  = :last_name,
                email      = :email,
                phone      = :phone,
                is_company = :is_company
            WHERE id = :id
        ";

        $data['id'] = $id;

        $statement = $pdo->prepare($sql);
        $statement->execute($data);
    }

    public function delete(int $id): void
    {
        $pdo = Database::connect();

        $statement = $pdo->prepare('DELETE FROM contacts WHERE id = :id');
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
        $statement = $pdo->prepare("DELETE FROM contacts WHERE id IN ($placeholders)");
        $statement->execute($ids);
    }

    public function addClientsToContacts(array $contactIds, array $clientIds): void
    {
        $contactIds = array_values(array_unique(array_filter(array_map('intval', $contactIds), fn ($id) => $id > 0)));
        $clientIds  = array_values(array_unique(array_filter(array_map('intval', $clientIds),  fn ($id) => $id > 0)));

        if (empty($contactIds) || empty($clientIds)) {
            return;
        }

        $pdo = Database::connect();
        $insert = $pdo->prepare('
            INSERT IGNORE INTO client_contacts (client_id, contact_id)
            VALUES (:client_id, :contact_id)
        ');

        foreach ($contactIds as $contactId) {
            foreach ($clientIds as $clientId) {
                $insert->execute(['client_id' => $clientId, 'contact_id' => $contactId]);
            }
        }
    }

    public function tagsForContacts(array $contactIds): array
    {
        if (empty($contactIds)) {
            return [];
        }

        $pdo = Database::connect();
        $placeholders = implode(',', array_fill(0, count($contactIds), '?'));

        $statement = $pdo->prepare("
            SELECT ct.contact_id, t.id, t.name, t.color
            FROM contact_tags ct
            INNER JOIN tags t ON t.id = ct.tag_id
            WHERE ct.contact_id IN ($placeholders)
            ORDER BY t.name ASC
        ");
        $statement->execute($contactIds);

        $result = [];
        foreach ($statement->fetchAll() as $row) {
            $result[(int) $row['contact_id']][] = $row;
        }

        return $result;
    }

    public function clientsForContacts(array $contactIds): array
    {
        if (empty($contactIds)) {
            return [];
        }

        $pdo = Database::connect();
        $placeholders = implode(',', array_fill(0, count($contactIds), '?'));

        $statement = $pdo->prepare("
            SELECT cc.contact_id, cl.id, cl.commercial_name
            FROM client_contacts cc
            INNER JOIN clients cl ON cl.id = cc.client_id
            WHERE cc.contact_id IN ($placeholders)
            ORDER BY cl.commercial_name ASC
        ");
        $statement->execute($contactIds);

        $result = [];
        foreach ($statement->fetchAll() as $row) {
            $result[(int) $row['contact_id']][] = $row;
        }

        return $result;
    }

    public function firstTags(int $limit = 50): array
    {
        $pdo = Database::connect();

        $statement = $pdo->prepare('SELECT * FROM tags ORDER BY name ASC LIMIT :limit');
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function tagsForContact(int $contactId): array
    {
        $pdo = Database::connect();

        $sql = "
            SELECT tags.*
            FROM tags
            INNER JOIN contact_tags ON contact_tags.tag_id = tags.id
            WHERE contact_tags.contact_id = :contact_id
            ORDER BY tags.name ASC
        ";

        $statement = $pdo->prepare($sql);
        $statement->execute(['contact_id' => $contactId]);

        return $statement->fetchAll();
    }

    public function syncTags(int $contactId, array $tagIds): void
    {
        $pdo = Database::connect();

        $delete = $pdo->prepare('DELETE FROM contact_tags WHERE contact_id = :contact_id');
        $delete->execute(['contact_id' => $contactId]);

        if (empty($tagIds)) {
            return;
        }

        $insert = $pdo->prepare('
            INSERT INTO contact_tags (contact_id, tag_id)
            VALUES (:contact_id, :tag_id)
        ');

        foreach ($tagIds as $tagId) {
            $insert->execute([
                'contact_id' => $contactId,
                'tag_id' => $tagId,
            ]);
        }
    }

    public function addTags(array $contactIds, array $tagIds): void
    {
        $contactIds = array_values(array_unique(array_filter(array_map('intval', $contactIds), fn ($id) => $id > 0)));
        $tagIds = array_values(array_unique(array_filter(array_map('intval', $tagIds), fn ($id) => $id > 0)));

        if (empty($contactIds) || empty($tagIds)) {
            return;
        }

        $pdo = Database::connect();
        $insert = $pdo->prepare('
            INSERT IGNORE INTO contact_tags (contact_id, tag_id)
            VALUES (:contact_id, :tag_id)
        ');

        foreach ($contactIds as $contactId) {
            foreach ($tagIds as $tagId) {
                $insert->execute([
                    'contact_id' => $contactId,
                    'tag_id' => $tagId,
                ]);
            }
        }
    }

    public function removeTags(array $contactIds, array $tagIds): void
    {
        $contactIds = array_values(array_unique(array_filter(array_map('intval', $contactIds), fn ($id) => $id > 0)));
        $tagIds = array_values(array_unique(array_filter(array_map('intval', $tagIds), fn ($id) => $id > 0)));

        if (empty($contactIds) || empty($tagIds)) {
            return;
        }

        $pdo = Database::connect();
        $contactPlaceholders = implode(',', array_fill(0, count($contactIds), '?'));
        $tagPlaceholders = implode(',', array_fill(0, count($tagIds), '?'));
        $statement = $pdo->prepare("
            DELETE FROM contact_tags
            WHERE contact_id IN ($contactPlaceholders)
            AND tag_id IN ($tagPlaceholders)
        ");
        $statement->execute(array_merge($contactIds, $tagIds));
    }

    public function firstClients(int $limit = 50): array
    {
        $pdo = Database::connect();

        $statement = $pdo->prepare('
            SELECT id, commercial_name, legal_name
            FROM clients
            ORDER BY commercial_name ASC
            LIMIT :limit
        ');
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function allSectors(): array
    {
        $pdo = Database::connect();

        $statement = $pdo->prepare('SELECT id, name FROM sectors ORDER BY name ASC');
        $statement->execute();

        return $statement->fetchAll();
    }

    public function clientsForContact(int $contactId): array
    {
        $pdo = Database::connect();

        $sql = "
            SELECT clients.*, client_contacts.relation_label, client_contacts.is_primary
            FROM clients
            INNER JOIN client_contacts ON client_contacts.client_id = clients.id
            WHERE client_contacts.contact_id = :contact_id
            ORDER BY clients.commercial_name ASC
        ";

        $statement = $pdo->prepare($sql);
        $statement->execute(['contact_id' => $contactId]);

        return $statement->fetchAll();
    }

    public function syncClients(int $contactId, array $clientIds): void
    {
        $pdo = Database::connect();

        $delete = $pdo->prepare('DELETE FROM client_contacts WHERE contact_id = :contact_id');
        $delete->execute(['contact_id' => $contactId]);

        if (empty($clientIds)) {
            return;
        }

        $insert = $pdo->prepare('
            INSERT INTO client_contacts (client_id, contact_id)
            VALUES (:client_id, :contact_id)
        ');

        foreach ($clientIds as $clientId) {
            $insert->execute([
                'client_id' => $clientId,
                'contact_id' => $contactId,
            ]);
        }
    }

    private function buildFilterSql(array $filters): array
    {
        $where = [];
        $params = [];

        foreach (['first_name', 'last_name', 'email', 'phone'] as $field) {
            if (!empty($filters[$field])) {
                $where[] = "contacts.{$field} LIKE :{$field}";
                $params[$field] = '%' . $filters[$field] . '%';
            }
        }

        if ($filters['is_company'] !== '' && $filters['is_company'] !== null) {
            $where[] = 'contacts.is_company = :is_company';
            $params['is_company'] = (int) $filters['is_company'];
        }

        if (!empty($filters['client_id'])) {
            $where[] = '
                EXISTS (
                    SELECT 1
                    FROM client_contacts
                    WHERE client_contacts.contact_id = contacts.id
                    AND client_contacts.client_id = :client_id
                )
            ';
            $params['client_id'] = (int) $filters['client_id'];
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
                    FROM contact_tags
                    WHERE contact_tags.contact_id = contacts.id
                    AND contact_tags.tag_id IN (' . implode(',', $tagPlaceholders) . ')
                )
            ';
        }

        foreach (['sector_id', 'country', 'province'] as $field) {
            if (!empty($filters[$field])) {
                $operator = $field === 'sector_id' ? '= :sector_id' : "LIKE :{$field}";
                $value = $field === 'sector_id' ? (int) $filters[$field] : '%' . $filters[$field] . '%';

                $where[] = "
                    EXISTS (
                        SELECT 1
                        FROM client_contacts
                        INNER JOIN clients ON clients.id = client_contacts.client_id
                        WHERE client_contacts.contact_id = contacts.id
                        AND clients.{$field} {$operator}
                    )
                ";
                $params[$field] = $value;
            }
        }

        if (!empty($filters['created_from'])) {
            $where[] = 'contacts.created_at >= :created_from';
            $params['created_from'] = $filters['created_from'] . ' 00:00:00';
        }

        if (!empty($filters['created_to'])) {
            $where[] = 'contacts.created_at <= :created_to';
            $params['created_to'] = $filters['created_to'] . ' 23:59:59';
        }

        if (!empty($filters['updated_from'])) {
            $where[] = 'contacts.updated_at >= :updated_from';
            $params['updated_from'] = $filters['updated_from'] . ' 00:00:00';
        }

        if (!empty($filters['updated_to'])) {
            $where[] = 'contacts.updated_at <= :updated_to';
            $params['updated_to'] = $filters['updated_to'] . ' 23:59:59';
        }

        foreach (($filters['custom_fields'] ?? []) as $fieldId => $value) {
            $fieldId = (int) $fieldId;
            $value = trim((string) $value);

            if ($fieldId <= 0 || $value === '') {
                continue;
            }

            $fieldParam = 'custom_field_' . $fieldId;
            $textParam = 'custom_value_text_' . $fieldId;
            $numberParam = 'custom_value_number_' . $fieldId;
            $dateParam = 'custom_value_date_' . $fieldId;
            $boolParam = 'custom_value_bool_' . $fieldId;

            $where[] = "
                EXISTS (
                    SELECT 1
                    FROM custom_field_values
                    WHERE custom_field_values.entity_type = 'contact'
                    AND custom_field_values.entity_id = contacts.id
                    AND custom_field_values.field_id = :{$fieldParam}
                    AND (
                        custom_field_values.value_text LIKE :{$textParam}
                        OR CAST(custom_field_values.value_number AS CHAR) LIKE :{$numberParam}
                        OR CAST(custom_field_values.value_date AS CHAR) LIKE :{$dateParam}
                        OR CAST(custom_field_values.value_bool AS CHAR) LIKE :{$boolParam}
                    )
                )
            ";
            $params[$fieldParam] = $fieldId;
            $params[$textParam] = '%' . $value . '%';
            $params[$numberParam] = '%' . $value . '%';
            $params[$dateParam] = '%' . $value . '%';
            $params[$boolParam] = '%' . $value . '%';
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
