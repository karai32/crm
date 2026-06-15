<?php

class TagApiService extends AbstractApiService
{
    private TagRepository $tags;

    public function __construct()
    {
        parent::__construct();
        $this->tags = new TagRepository();
    }

    public function createBatch(array $items): ApiResult
    {
        return $this->batch($items, function (array $item): array {
            $name = trim((string) ($item['name'] ?? ''));
            if ($name === '') {
                throw new ApiException(422, 'validation_error', 'Tag validation failed', ['name is required']);
            }

            $id = $this->tags->create($name, $this->nullableString($item['color'] ?? null));
            return $this->format($this->requireRecord($this->tags->find($id), 'tag'));
        });
    }

    public function index(array $query): ApiResult
    {
        $items = $this->tags->filter(trim((string) ($query['name'] ?? '')));
        return new ApiResult(200, [
            'success' => true,
            'data' => [
                'items' => array_map(fn (array $tag): array => $this->format($tag), $items),
                'total' => count($items),
            ],
        ], count($items));
    }

    public function show(int $id): ApiResult
    {
        $tag = $this->requireRecord($this->tags->find($id), 'tag');
        return new ApiResult(200, ['success' => true, 'data' => $this->format($tag)], 1);
    }

    public function update(int $id, array $body): ApiResult
    {
        $tag = $this->requireRecord($this->tags->find($id), 'tag');
        if (array_key_exists('name', $body) && trim((string) ($body['name'] ?? '')) === '') {
            throw new ApiException(422, 'validation_error', 'name cannot be empty');
        }

        $name = array_key_exists('name', $body) ? trim((string) $body['name']) : $tag['name'];
        $color = array_key_exists('color', $body)
            ? $this->nullableString($body['color'])
            : $tag['color'];

        $this->tags->update($id, $name, $color);
        $updated = $this->requireRecord($this->tags->find($id), 'tag');

        return new ApiResult(200, ['success' => true, 'data' => $this->format($updated)], 1);
    }

    public function destroy(int $id): ApiResult
    {
        $this->requireRecord($this->tags->find($id), 'tag');
        $this->tags->delete($id);
        return new ApiResult(200, ['success' => true, 'data' => ['id' => $id]], 1);
    }

    private function format(array $tag): array
    {
        return [
            'id' => (int) $tag['id'],
            'name' => $tag['name'],
            'slug' => $tag['slug'],
            'color' => $tag['color'],
        ];
    }
}
