<?php

final class WriteException extends RuntimeException
{
    public function __construct(
        private string $reason,
        string $message,
        private int $status = 422,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function reason(): string
    {
        return $this->reason;
    }

    public function status(): int
    {
        return $this->status;
    }

    public function apiCode(): string
    {
        return match ($this->reason) {
            'contacts.email_already_used' => 'duplicate_contact',
            'contacts.internal_email' => 'internal_email',
            'clients.name_already_used' => 'duplicate_client',
            'contacts.not_found', 'clients.not_found' => 'not_found',
            default => 'validation_error',
        };
    }
}
