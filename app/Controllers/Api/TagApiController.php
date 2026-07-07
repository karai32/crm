<?php

class TagApiController extends AbstractApiController
{
    protected function resource(): string
    {
        return 'tags';
    }

    protected function makeService(): AbstractApiService
    {
        return new TagApiService();
    }
}
