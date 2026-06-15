<?php

class SectorApiService extends AbstractApiService
{
    private SectorRepository $sectors;

    public function __construct()
    {
        parent::__construct();
        $this->sectors = new SectorRepository();
    }

    public function createBatch(array $items): ApiResult
    {
        return $this->batch($items, function (array $item): array {
            $name = trim((string) ($item['name'] ?? ''));
            if ($name === '') {
                throw new ApiException(422, 'validation_error', 'Sector validation failed', ['name is required']);
            }

            $id = $this->sectors->create($name);
            return $this->format($this->requireRecord($this->sectors->find($id), 'sector'));
        });
    }

    public function index(array $query): ApiResult
    {
        $name = trim((string) ($query['name'] ?? ''));
        $isActive = $query['is_active'] ?? '';
        $items = $this->sectors->filter($name, $isActive);

        return new ApiResult(200, [
            'success' => true,
            'data' => [
                'items' => array_map(fn (array $sector): array => $this->format($sector), $items),
                'total' => count($items),
            ],
        ], count($items));
    }

    public function show(int $id): ApiResult
    {
        $sector = $this->requireRecord($this->sectors->find($id), 'sector');
        return new ApiResult(200, [
            'success' => true,
            'data' => $this->format($sector, $this->sectors->clientsCount($id)),
        ], 1);
    }

    public function update(int $id, array $body): ApiResult
    {
        $sector = $this->requireRecord($this->sectors->find($id), 'sector');
        if (array_key_exists('name', $body) && trim((string) ($body['name'] ?? '')) === '') {
            throw new ApiException(422, 'validation_error', 'name cannot be empty');
        }

        $name = array_key_exists('name', $body) ? trim((string) $body['name']) : $sector['name'];
        $isActive = array_key_exists('is_active', $body)
            ? ($body['is_active'] ? 1 : 0)
            : (int) $sector['is_active'];

        $this->sectors->update($id, $name, $isActive);
        $updated = $this->requireRecord($this->sectors->find($id), 'sector');

        return new ApiResult(200, [
            'success' => true,
            'data' => $this->format($updated, $this->sectors->clientsCount($id)),
        ], 1);
    }

    public function destroy(int $id): ApiResult
    {
        $this->requireRecord($this->sectors->find($id), 'sector');
        $hasClients = $this->sectors->clientsCount($id) > 0;
        $this->sectors->deleteOrDeactivate($id);

        return new ApiResult(200, [
            'success' => true,
            'data' => [
                'id' => $id,
                'action' => $hasClients ? 'deactivated' : 'deleted',
            ],
        ], 1);
    }

    private function format(array $sector, ?int $clientsCount = null): array
    {
        $data = [
            'id' => (int) $sector['id'],
            'name' => $sector['name'],
            'slug' => $sector['slug'],
            'is_active' => (bool) $sector['is_active'],
        ];
        if ($clientsCount !== null) {
            $data['clients_count'] = $clientsCount;
        }
        return $data;
    }
}
