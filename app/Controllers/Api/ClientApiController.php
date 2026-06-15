<?php

class ClientApiController extends AbstractApiController
{
    private ClientApiService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new ClientApiService();
    }

    public function index(): void
    {
        $this->handle('clients:read', '/api/v1/clients', fn (): ApiResult => $this->service->index($_GET));
    }

    public function show(): void
    {
        $this->handle('clients:read', '/api/v1/clients/{id}', fn (): ApiResult => $this->service->show($this->routeId()));
    }

    public function create(): void
    {
        $this->handle('clients:write', '/api/v1/clients', fn (): ApiResult => $this->service->createBatch($this->jsonBatch()));
    }

    public function update(): void
    {
        $this->handle('clients:write', '/api/v1/clients/{id}', fn (): ApiResult => $this->service->update($this->routeId(), $this->jsonObject()));
    }

    public function destroy(): void
    {
        $this->handle('clients:write', '/api/v1/clients/{id}', fn (): ApiResult => $this->service->destroy($this->routeId()));
    }
}
