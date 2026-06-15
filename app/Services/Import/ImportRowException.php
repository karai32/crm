<?php

class ImportRowException extends RuntimeException
{
    public function __construct(string $message, private string $rowStatus = 'error')
    {
        parent::__construct($message);
    }

    public function rowStatus(): string
    {
        return $this->rowStatus;
    }
}
