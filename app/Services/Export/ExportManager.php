<?php

class ExportManager
{
    private ExportService $queries;
    private ExportRepository $exports;
    private CustomFieldRepository $customFields;
    private ExportCsvWriter $csv;
    private ExportXlsxWriter $xlsx;

    public function __construct()
    {
        $this->queries = new ExportService();
        $this->exports = new ExportRepository();
        $this->customFields = new CustomFieldRepository();
        $this->csv = new ExportCsvWriter();
        $this->xlsx = new ExportXlsxWriter();
    }

    public function page(string $entity): array
    {
        $entity = $this->entity($entity);
        $fields = $this->fields($entity);

        return [
            'entity' => $entity,
            'fieldDefs' => $fields,
            'defaultFields' => $entity === 'contacts'
                ? ['id', 'first_name', 'last_name', 'email', 'phone', 'created_at']
                : ['id', 'commercial_name', 'legal_name', 'city', 'country', 'created_at'],
            'recentExports' => $this->exports->recentExports(12),
        ];
    }

    public function filename(string $entity, string $format): string
    {
        return $this->entity($entity) . '-' . date('Y-m-d-H-i-s') . '.' . $this->format($format);
    }

    public function writeCsv(
        string $entity,
        array $filters,
        array $selectedFields,
        ?int $userId,
        string $filename,
        $output
    ): int {
        $entity = $this->entity($entity);
        [$fields, $plan] = $this->plan($entity, $filters, $selectedFields);
        $batchId = $this->exports->start($userId, $entity, 'csv', $filters, $fields, $filename);

        try {
            $rows = $this->csv->write($plan['sql'], $plan['params'], $plan['headers'], $output);
            $this->exports->complete($batchId, $rows);
            return $rows;
        } catch (Throwable $exception) {
            $this->exports->fail($batchId);
            throw $exception;
        }
    }

    public function writeXlsx(
        string $entity,
        array $filters,
        array $selectedFields,
        ?int $userId,
        string $filename,
        string $target = 'php://output'
    ): int {
        $entity = $this->entity($entity);
        [$fields, $plan] = $this->plan($entity, $filters, $selectedFields);
        $batchId = $this->exports->start($userId, $entity, 'xlsx', $filters, $fields, $filename);

        try {
            $rows = $this->xlsx->write(
                $plan['sql'],
                $plan['params'],
                $plan['headers'],
                $entity === 'contacts' ? 'Contacts' : 'Clients',
                $target
            );
            $this->exports->complete($batchId, $rows);
            return $rows;
        } catch (Throwable $exception) {
            $this->exports->fail($batchId);
            throw $exception;
        }
    }

    public function template(string $entity): string
    {
        return $this->queries->template($this->entity($entity));
    }

    private function plan(string $entity, array $filters, array $selected): array
    {
        $definitions = $this->fields($entity);
        $fields = $this->queries->sanitizeFields($selected, $definitions);
        return [$fields, $this->queries->query($entity, $filters, $fields, $definitions)];
    }

    private function fields(string $entity): array
    {
        $customFields = $this->customFields->fieldsForEntity($entity === 'contacts' ? 'contact' : 'client');
        return $this->queries->fieldDefinitions($entity, $customFields);
    }

    private function entity(string $entity): string
    {
        return in_array($entity, ['contacts', 'clients'], true) ? $entity : 'contacts';
    }

    private function format(string $format): string
    {
        return in_array($format, ['csv', 'xlsx'], true) ? $format : 'csv';
    }
}
