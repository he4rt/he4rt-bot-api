<?php

namespace Heart\Core\Contracts;

abstract class DomainInterface
{
    private bool $disabled;

    public function __construct($disabled = false)
    {
        $this->disabled = $disabled;
    }

    public function isDisabled(): bool
    {
        return $this->disabled;
    }

    abstract public function registerProvider(): array;
}
