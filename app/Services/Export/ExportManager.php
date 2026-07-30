<?php

use Illuminate\Support\Carbon;

class ExportManager
{
    private ExportService $queries;
    private ExportRepository $exports;
    private CustomFieldRepository $customFields;
    private ExportWriter $writer;

    public function __construct()
    {
        $this->queries = new ExportService();
        $this->exports = new ExportRepository();
        $this->customFields = new CustomFieldRepository();
        $this->writer = new ExportWriter();
    }

    public function page(string $entity): array
    {
        $entity = $this->entity($entity);
        $fields = $this->fields($entity);

        return [
            'entity' => $entity,
            'fieldDefs' => $fields,
            'defaultFields' => $entity === 'contacts'
                ? ['id', 'full_name', 'email', 'phone', 'created_at']
                : ['id', 'commercial_name', 'legal_name', 'city', 'country', 'created_at'],
            'recentExports' => $this->exports->recentExports(12),
        ];
    }

    public function filename(string $entity, string $format): string
    {
        return $this->entity($entity) . '-' . Carbon::now()->format('Y-m-d-H-i-s') . '.' . $this->format($format);
    }

    public function writeCsv(
        string $entity,
        array $filters,
        array $selectedFields,
        ?int $userId,
        string $filename,
        $output
    ): int {
        return $this->export(
            $entity,
            'csv',
            $filters,
            $selectedFields,
            $userId,
            $filename,
            fn (array $plan, string $_entity): int => $this->writer->csv($plan, $output)
        );
    }

    public function writeXlsx(
        string $entity,
        array $filters,
        array $selectedFields,
        ?int $userId,
        string $filename,
        string $target = 'php://output'
    ): int {
        return $this->export(
            $entity,
            'xlsx',
            $filters,
            $selectedFields,
            $userId,
            $filename,
            fn (array $plan, string $normalizedEntity): int => $this->writer->xlsx(
                $plan,
                ucfirst($normalizedEntity),
                $target
            )
        );
    }

    private function export(
        string $entity,
        string $format,
        array $filters,
        array $selectedFields,
        ?int $userId,
        string $filename,
        callable $write
    ): int {
        $entity = $this->entity($entity);
        [$fields, $plan] = $this->plan($entity, $filters, $selectedFields);
        $batchId = $this->exports->start($userId, $entity, $format, $filters, $fields, $filename);

        try {
            $rows = $write($plan, $entity);
            $this->exports->complete($batchId, $rows);
            return $rows;
        } catch (Throwable $exception) {
            $this->exports->fail($batchId);
            throw $exception;
        }
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
