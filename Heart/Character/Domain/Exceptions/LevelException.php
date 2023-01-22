<?php

namespace Heart\Character\Domain\Exceptions;

use Exception;
use Symfony\Component\HttpFoundation\Response;

class LevelException extends Exception
{
    public static function notExists(string $experience): self
    {
        return new self(
            sprintf('Failed to convert %s to level.', $experience),
            Response::HTTP_UNPROCESSABLE_ENTITY
        );
    }
}
