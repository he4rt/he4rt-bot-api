<?php

namespace Heart\Shared\Domain\ValueObjects;

class IntValueObject
{
    public function __construct(protected int $value)
    {
    }

    public function value(): int
    {
        return $this->value;
    }
}
