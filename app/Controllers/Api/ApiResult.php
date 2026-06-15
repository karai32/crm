<?php

class ApiResult
{
    public function __construct(
        public int $status,
        public array $data,
        public ?int $itemsCount = null
    ) {
    }
}
