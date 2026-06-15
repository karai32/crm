<?php

class ContactApiController extends AbstractApiController
{
    private ContactApiService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new ContactApiService();
    }

    public function index(): void
    {
        $this->handle('contacts:read', '/api/v1/contacts', fn (): ApiResult => $this->service->index($_GET));
    }

    public function show(): void
    {
        $this->handle('contacts:read', '/api/v1/contacts/{id}', fn (): ApiResult => $this->service->show($this->routeId()));
    }

    public function create(): void
    {
        $this->handle('contacts:write', '/api/v1/contacts', fn (): ApiResult => $this->service->createBatch($this->jsonBatch()));
    }

    public function update(): void
    {
        $this->handle('contacts:write', '/api/v1/contacts/{id}', fn (): ApiResult => $this->service->update($this->routeId(), $this->jsonObject()));
    }

    public function destroy(): void
    {
        $this->handle('contacts:write', '/api/v1/contacts/{id}', fn (): ApiResult => $this->service->destroy($this->routeId()));
    }
}
