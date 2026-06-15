<?php

class SectorApiController extends AbstractApiController
{
    private SectorApiService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new SectorApiService();
    }

    public function index(): void
    {
        $this->handle('sectors:read', '/api/v1/sectors', fn (): ApiResult => $this->service->index($_GET));
    }

    public function show(): void
    {
        $this->handle('sectors:read', '/api/v1/sectors/{id}', fn (): ApiResult => $this->service->show($this->routeId()));
    }

    public function create(): void
    {
        $this->handle('sectors:write', '/api/v1/sectors', fn (): ApiResult => $this->service->createBatch($this->jsonBatch()));
    }

    public function update(): void
    {
        $this->handle('sectors:write', '/api/v1/sectors/{id}', fn (): ApiResult => $this->service->update($this->routeId(), $this->jsonObject()));
    }

    public function destroy(): void
    {
        $this->handle('sectors:write', '/api/v1/sectors/{id}', fn (): ApiResult => $this->service->destroy($this->routeId()));
    }
}
