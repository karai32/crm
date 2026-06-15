<?php

class ApiException extends RuntimeException
{
    public function __construct(
        private int $status,
        private string $errorCode,
        string $message,
        private array $details = []
    ) {
        parent::__construct($message);
    }

    public function status(): int
    {
        return $this->status;
    }

    public function errorCode(): string
    {
        return $this->errorCode;
    }

    public function details(): array
    {
        return $this->details;
    }
}
