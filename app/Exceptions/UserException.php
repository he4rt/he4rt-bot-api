<?php

namespace App\Exceptions;

use Exception;
use Symfony\Component\HttpFoundation\Response;

class UserException extends Exception
{
    public static function discordIdNotFound(int $id): self
    {
        return new self(sprintf('User with Discord id %s was not found', $id), Response::HTTP_NOT_FOUND);
    }

    public function render($request)
    {
        return response($this->getMessage(), $this->code);
    }
}
