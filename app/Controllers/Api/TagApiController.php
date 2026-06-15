<?php

class TagApiController extends AbstractApiController
{
    private TagApiService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new TagApiService();
    }

    public function index(): void
    {
        $this->handle('tags:read', '/api/v1/tags', fn (): ApiResult => $this->service->index($_GET));
    }

    public function show(): void
    {
        $this->handle('tags:read', '/api/v1/tags/{id}', fn (): ApiResult => $this->service->show($this->routeId()));
    }

    public function create(): void
    {
        $this->handle('tags:write', '/api/v1/tags', fn (): ApiResult => $this->service->createBatch($this->jsonBatch()));
    }

    public function update(): void
    {
        $this->handle('tags:write', '/api/v1/tags/{id}', fn (): ApiResult => $this->service->update($this->routeId(), $this->jsonObject()));
    }

    public function destroy(): void
    {
        $this->handle('tags:write', '/api/v1/tags/{id}', fn (): ApiResult => $this->service->destroy($this->routeId()));
    }
}
