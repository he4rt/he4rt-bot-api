<?php

namespace Heart\Shared\Domain\ValueObjects;

class StringValueObject
{
    public function __construct(protected readonly string $value)
    {
    }
}
