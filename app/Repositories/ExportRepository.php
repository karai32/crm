<?php

use Illuminate\Database\Query\Builder;

class ExportRepository
{
    public function start(
        ?int $userId,
        string $entityType,
        string $fileType,
        array $filters,
        array $fields,
        string $fileName
    ): int
    {
        return Database::table('export_batches')->insertGetId([
            'user_id'         => $userId,
            'entity_type'     => $entityType,
            'file_type'       => $fileType,
            'stored_filename' => $fileName,
            'filters'         => json_encode($filters),
            'selected_fields' => json_encode($fields),
            'status'          => 'processing',
        ]);
    }

    public function complete(int $id, int $totalRows): void
    {
        Database::table('export_batches')->where('id', $id)->update([
            'status' => 'completed',
            'total_rows' => $totalRows,
            'finished_at' => Database::raw('CURRENT_TIMESTAMP'),
        ]);
    }

    public function fail(int $id): void
    {
        Database::table('export_batches')->where('id', $id)->update([
            'status' => 'failed',
            'finished_at' => Database::raw('CURRENT_TIMESTAMP'),
        ]);
    }

    // int
    public function count(): int
    {
        return Database::table('export_batches')->count();
    }

    // array<{id, user_id, file_type, entity_type, stored_filename, filters, selected_fields, total_rows, status, created_at, finished_at, user_name}>
    public function paginate(int $page, int $perPage, string $sort = 'id', string $dir = 'desc'): array
    {
        return Database::rows(
            $this->ordered($sort, $dir)
                ->limit($perPage)
                ->offset(($page - 1) * $perPage)
        );
    }

    // array<{id, user_id, file_type, entity_type, stored_filename, filters, selected_fields, total_rows, status, created_at, finished_at, user_name}>
    public function recentExports(int $limit = 15, string $sort = 'id', string $dir = 'desc'): array
    {
        return Database::rows($this->ordered($sort, $dir)->limit($limit));
    }

    private function ordered(string $sort, string $dir): Builder
    {
        $allowed = [
            'id' => 'eb.id',
            'entity_type' => 'eb.entity_type',
            'stored_filename' => 'eb.stored_filename',
        ];

        return Database::table('export_batches as eb')
            ->leftJoin('users as u', 'u.id', '=', 'eb.user_id')
            ->select('eb.*')
            ->addSelect('u.name AS user_name')
            ->orderBy($allowed[$sort] ?? 'eb.id', $dir === 'asc' ? 'asc' : 'desc');
    }
}
