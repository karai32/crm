<?php

class ClientApiController extends AbstractApiController
{
    protected function resource(): string
    {
        return 'clients';
    }

    protected function makeService(): AbstractApiService
    {
        return new ClientApiService();
    }
}
