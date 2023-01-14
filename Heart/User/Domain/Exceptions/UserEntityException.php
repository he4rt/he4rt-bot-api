<?php

namespace Heart\User\Domain\Exceptions;

use Exception;

class UserEntityException extends Exception
{
    public static function failedToCreateEntity(): self
    {
        return new self("Failed to create entity", 422);
    }
}
