<?php

class SectorApiController extends AbstractApiController
{
    protected function resource(): string
    {
        return 'sectors';
    }

    protected function makeService(): AbstractApiService
    {
        return new SectorApiService();
    }
}
