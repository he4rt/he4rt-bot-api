<?php

namespace Heart\Core\DTO;

class DomainDTO
{
    public function __construct(
        public readonly string $namespace,
        public readonly string $filePath,
        public readonly string $fileName
    )
    {
    }

    public static function make(string $namespace, array $domainPayload): self
    {
        return new self($namespace, $domainPayload['filePath'], $domainPayload['fileName']);
    }
}
