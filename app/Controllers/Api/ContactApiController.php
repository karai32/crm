<?php

class ContactApiController extends AbstractApiController
{
    protected function resource(): string
    {
        return 'contacts';
    }

    protected function makeService(): AbstractApiService
    {
        return new ContactApiService();
    }
}
