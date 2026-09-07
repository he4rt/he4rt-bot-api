<?php

declare(strict_types=1);

namespace He4rt\Live\Exceptions;

use DomainException;

final class CurrentLiveAlreadyExists extends DomainException
{
    public static function make(): self
    {
        return new self('Já existe uma live corrente. Encerre-a antes de criar outra.');
    }
}
