<?php

namespace App\Exceptions;

use Exception;

class UserException extends Exception
{
    public static function discordIdNotFound(int $id): self
    {
        return new self(sprintf('User with Discord id %s was not found', $id));
    }
}
