<?php

class ExportService
{
    // ── Field definitions ──────────────────────────────────────────────────

    public function contactsFieldDefs(array $customFields): array
    {
        $fields = [
            'id'           => ['label' => 'ID',          'group' => 'Basic info'],
            'first_name'   => ['label' => 'First name',  'group' => 'Basic info'],
            'last_name'    => ['label' => 'Last name',   'group' => 'Basic info'],
            'email'        => ['label' => 'Email',       'group' => 'Basic info'],
            'phone'        => ['label' => 'Phone',       'group' => 'Basic info'],
            'is_company'   => ['label' => 'Is company',  'group' => 'Basic info'],
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

    public function clientsFieldDefs(array $customFields = []): array
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
        $valid = array_values(array_intersect($selected, array_keys($fieldDefs)));
        return empty($valid) ? ['id'] : $valid;
    }

    // ── Row count ─────────────────────────────────────────────────────────
    // Use own filter builders so the count matches what we actually export.

    public function countContacts(array $filters): int
    {
        $pdo = Database::connect();
        [$whereSql, $params] = $this->buildContactsFilterSql($filters);
        $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM contacts $whereSql");
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        return (int) ($stmt->fetch()['total'] ?? 0);
    }

    public function countClients(array $filters): int
    {
        $pdo = Database::connect();
        [$whereSql, $params] = $this->buildClientsFilterSql($filters);
        $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM clients $whereSql");
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        return (int) ($stmt->fetch()['total'] ?? 0);
    }

    // ── CSV streaming ──────────────────────────────────────────────────────

    public function streamContactsCsv(array $filters, array $fields, array $fieldDefs, $output): int
    {
        [$sql, $params, $headers] = $this->buildContactsSql($filters, $fields, $fieldDefs);
        return $this->streamSql($sql, $params, $headers, $output);
    }

    public function streamClientsCsv(array $filters, array $fields, array $fieldDefs, $output): int
    {
        [$sql, $params, $headers] = $this->buildClientsSql($filters, $fields, $fieldDefs);
        return $this->streamSql($sql, $params, $headers, $output);
    }

    // ── XLSX building ──────────────────────────────────────────────────────

    public function buildContactsXlsx(array $filters, array $fields, array $fieldDefs): \PhpOffice\PhpSpreadsheet\Spreadsheet
    {
        [$sql, $params, $headers] = $this->buildContactsSql($filters, $fields, $fieldDefs);
        return $this->buildXlsx($sql, $params, $headers, 'Contacts');
    }

    public function buildClientsXlsx(array $filters, array $fields, array $fieldDefs): \PhpOffice\PhpSpreadsheet\Spreadsheet
    {
        [$sql, $params, $headers] = $this->buildClientsSql($filters, $fields, $fieldDefs);
        return $this->buildXlsx($sql, $params, $headers, 'Clients');
    }

    // ── Template CSV ───────────────────────────────────────────────────────

    public function contactTemplateCsv(): string
    {
        $columns = ['first_name', 'last_name', 'email', 'phone', 'is_company', 'tags', 'client'];
        $handle  = fopen('php://temp', 'r+');
        fputcsv($handle, $columns);
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);
        return $csv;
    }

    public function clientTemplateCsv(): string
    {
        $columns = ['commercial_name', 'legal_name', 'cif', 'address', 'postal_code', 'city', 'province', 'country', 'website', 'notes', 'sector', 'tags'];
        $handle  = fopen('php://temp', 'r+');
        fputcsv($handle, $columns);
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);
        return $csv;
    }

    // ── Internal SQL builders ──────────────────────────────────────────────

    private function buildContactsSql(array $filters, array $fields, array $fieldDefs): array
    {
        $selectClauses  = [];
        $subqueryJoins  = [];
        $needTags       = false;
        $needClients    = false;
        $needCfv        = false;
        $cfIds          = [];
        $baseColumns    = ['id', 'first_name', 'last_name', 'email', 'phone', 'is_company', 'created_at', 'updated_at'];
        $headers        = [];

        foreach ($fields as $field) {
            if (!isset($fieldDefs[$field])) {
                continue;
            }
            $headers[] = $fieldDefs[$field]['label'];

            if ($field === 'tags') {
                $needTags        = true;
                $selectClauses[] = 'COALESCE(_tags_agg.tag_names, \'\') AS `tags`';
            } elseif ($field === 'client_names') {
                $needClients     = true;
                $selectClauses[] = 'COALESCE(_cl_agg.client_names, \'\') AS `client_names`';
            } elseif (str_starts_with($field, 'cf_')) {
                $cfId          = (int) substr($field, 3);
                $cfIds[]       = $cfId;
                $needCfv       = true;
                $selectClauses[] = 'COALESCE(_cfv_agg.cf_' . $cfId . ', \'\') AS `cf_' . $cfId . '`';
            } elseif (in_array($field, $baseColumns, true)) {
                $selectClauses[] = 'contacts.' . $field;
            }
        }

        if ($needTags) {
            $subqueryJoins[] = "LEFT JOIN (
                SELECT ct.contact_id,
                       GROUP_CONCAT(DISTINCT t.name ORDER BY t.name SEPARATOR ', ') AS tag_names
                FROM contact_tags ct
                INNER JOIN tags t ON t.id = ct.tag_id
                GROUP BY ct.contact_id
            ) _tags_agg ON _tags_agg.contact_id = contacts.id";
        }

        if ($needClients) {
            $subqueryJoins[] = "LEFT JOIN (
                SELECT cc.contact_id,
                       GROUP_CONCAT(DISTINCT cl.commercial_name ORDER BY cl.commercial_name SEPARATOR ', ') AS client_names
                FROM client_contacts cc
                INNER JOIN clients cl ON cl.id = cc.client_id
                GROUP BY cc.contact_id
            ) _cl_agg ON _cl_agg.contact_id = contacts.id";
        }

        if ($needCfv && !empty($cfIds)) {
            $cfCases = [];
            foreach ($cfIds as $cfId) {
                $cfCases[] = "MAX(CASE WHEN field_id = " . $cfId . " THEN COALESCE(value_text, CAST(value_number AS CHAR), CAST(value_date AS CHAR)) END) AS cf_" . $cfId;
            }
            $subqueryJoins[] = "LEFT JOIN (
                SELECT entity_id, " . implode(', ', $cfCases) . "
                FROM custom_field_values
                WHERE entity_type = 'contact'
                GROUP BY entity_id
            ) _cfv_agg ON _cfv_agg.entity_id = contacts.id";
        }

        [$whereSql, $params] = $this->buildContactsFilterSql($filters);

        $sql = "
            SELECT " . implode(', ', $selectClauses ?: ['contacts.id']) . "
            FROM contacts
            " . implode("\n", $subqueryJoins) . "
            " . $whereSql . "
            ORDER BY contacts.id DESC
        ";

        return [$sql, $params, $headers];
    }

    private function buildClientsSql(array $filters, array $fields, array $fieldDefs): array
    {
        $selectClauses  = [];
        $subqueryJoins  = [];
        $needSector     = false;
        $needTags       = false;
        $needContacts   = false;
        $needCfv        = false;
        $cfIds          = [];
        $baseColumns    = ['id', 'commercial_name', 'legal_name', 'cif', 'address', 'postal_code', 'city', 'province', 'country', 'website', 'notes', 'created_at', 'updated_at'];
        $headers        = [];

        foreach ($fields as $field) {
            if (!isset($fieldDefs[$field])) {
                continue;
            }
            $headers[] = $fieldDefs[$field]['label'];

            if ($field === 'sector_name') {
                $needSector      = true;
                $selectClauses[] = 'COALESCE(_sect.name, \'\') AS `sector_name`';
            } elseif ($field === 'tags') {
                $needTags        = true;
                $selectClauses[] = 'COALESCE(_tags_agg.tag_names, \'\') AS `tags`';
            } elseif ($field === 'contact_count') {
                $needContacts    = true;
                $selectClauses[] = 'COALESCE(_cc_agg.contact_count, 0) AS `contact_count`';
            } elseif (str_starts_with($field, 'cf_')) {
                $cfId          = (int) substr($field, 3);
                $cfIds[]       = $cfId;
                $needCfv       = true;
                $selectClauses[] = 'COALESCE(_cfv_agg.cf_' . $cfId . ', \'\') AS `cf_' . $cfId . '`';
            } elseif (in_array($field, $baseColumns, true)) {
                $selectClauses[] = 'clients.' . $field;
            }
        }

        if ($needSector) {
            $subqueryJoins[] = 'LEFT JOIN sectors _sect ON _sect.id = clients.sector_id';
        }

        if ($needTags) {
            $subqueryJoins[] = "LEFT JOIN (
                SELECT ct.client_id,
                       GROUP_CONCAT(DISTINCT t.name ORDER BY t.name SEPARATOR ', ') AS tag_names
                FROM client_tags ct
                INNER JOIN tags t ON t.id = ct.tag_id
                GROUP BY ct.client_id
            ) _tags_agg ON _tags_agg.client_id = clients.id";
        }

        if ($needContacts) {
            $subqueryJoins[] = "LEFT JOIN (
                SELECT client_id, COUNT(*) AS contact_count
                FROM client_contacts
                GROUP BY client_id
            ) _cc_agg ON _cc_agg.client_id = clients.id";
        }

        if ($needCfv && !empty($cfIds)) {
            $cfCases = [];
            foreach ($cfIds as $cfId) {
                $cfCases[] = "MAX(CASE WHEN field_id = " . $cfId . " THEN COALESCE(value_text, CAST(value_number AS CHAR), CAST(value_date AS CHAR)) END) AS cf_" . $cfId;
            }
            $subqueryJoins[] = "LEFT JOIN (
                SELECT entity_id, " . implode(', ', $cfCases) . "
                FROM custom_field_values
                WHERE entity_type = 'client'
                GROUP BY entity_id
            ) _cfv_agg ON _cfv_agg.entity_id = clients.id";
        }

        [$whereSql, $params] = $this->buildClientsFilterSql($filters);

        $sql = "
            SELECT " . implode(', ', $selectClauses ?: ['clients.id']) . "
            FROM clients
            " . implode("\n", $subqueryJoins) . "
            " . $whereSql . "
            ORDER BY clients.id DESC
        ";

        return [$sql, $params, $headers];
    }

    // ── Filter SQL builders (mirrors repository logic) ─────────────────────

    private function buildContactsFilterSql(array $filters): array
    {
        $where  = [];
        $params = [];

        foreach (['first_name', 'last_name', 'email', 'phone'] as $field) {
            if (!empty($filters[$field])) {
                $where[]         = "contacts.{$field} LIKE :{$field}";
                $params[$field]  = '%' . $filters[$field] . '%';
            }
        }

        if (isset($filters['is_company']) && $filters['is_company'] !== '' && $filters['is_company'] !== null) {
            $where[]             = 'contacts.is_company = :is_company';
            $params['is_company'] = (int) $filters['is_company'];
        }

        if (!empty($filters['client_id'])) {
            $where[] = 'EXISTS (SELECT 1 FROM client_contacts WHERE client_contacts.contact_id = contacts.id AND client_contacts.client_id = :client_id)';
            $params['client_id'] = (int) $filters['client_id'];
        }

        $tagIds = $this->cleanTagIds($filters);
        if (!empty($tagIds)) {
            $placeholders = [];
            foreach ($tagIds as $i => $tagId) {
                $p              = 'tag_id_' . $i;
                $placeholders[] = ':' . $p;
                $params[$p]     = $tagId;
            }
            $where[] = 'EXISTS (SELECT 1 FROM contact_tags WHERE contact_tags.contact_id = contacts.id AND contact_tags.tag_id IN (' . implode(',', $placeholders) . '))';
        }

        $whereSql = empty($where) ? '' : 'WHERE ' . implode(' AND ', $where);

        return [$whereSql, $params];
    }

    private function buildClientsFilterSql(array $filters): array
    {
        $where  = [];
        $params = [];

        foreach (['commercial_name', 'legal_name', 'city', 'country', 'province'] as $field) {
            if (!empty($filters[$field])) {
                $where[]        = "clients.{$field} LIKE :{$field}";
                $params[$field] = '%' . $filters[$field] . '%';
            }
        }

        if (!empty($filters['sector_id'])) {
            $where[]           = 'clients.sector_id = :sector_id';
            $params['sector_id'] = (int) $filters['sector_id'];
        }

        $tagIds = $this->cleanTagIds($filters);
        if (!empty($tagIds)) {
            $placeholders = [];
            foreach ($tagIds as $i => $tagId) {
                $p              = 'tag_id_' . $i;
                $placeholders[] = ':' . $p;
                $params[$p]     = $tagId;
            }
            $where[] = 'EXISTS (SELECT 1 FROM client_tags WHERE client_tags.client_id = clients.id AND client_tags.tag_id IN (' . implode(',', $placeholders) . '))';
        }

        $whereSql = empty($where) ? '' : 'WHERE ' . implode(' AND ', $where);

        return [$whereSql, $params];
    }

    private function cleanTagIds(array $source): array
    {
        $ids = array_map('intval', (array) ($source['tag_ids'] ?? []));
        return array_values(array_unique(array_filter($ids, fn ($id) => $id > 0)));
    }

    // ── Common streaming/building helpers ─────────────────────────────────

    private function streamSql(string $sql, array $params, array $headers, $output): int
    {
        $pdo  = Database::connect();
        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();

        fputcsv($output, $headers);
        $count = 0;

        while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
            fputcsv($output, $row);
            $count++;
        }

        return $count;
    }

    private function buildXlsx(string $sql, array $params, array $headers, string $sheetTitle): \PhpOffice\PhpSpreadsheet\Spreadsheet
    {
        $pdo  = Database::connect();
        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle($sheetTitle);

        foreach ($headers as $colIndex => $header) {
            $cell = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 1) . '1';
            $sheet->setCellValue($cell, $header);
        }

        $rowIndex = 2;
        while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
            foreach ($row as $colIndex => $value) {
                $cell = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 1) . $rowIndex;
                $sheet->setCellValue($cell, $value ?? '');
            }
            $rowIndex++;
        }

        return $spreadsheet;
    }
}
