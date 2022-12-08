<?php

namespace App\Exceptions;

use Exception;

class FeedbackException extends Exception
{
    public static function idNotFound(int $id): self
    {
        return new self(sprintf('The feedback with id %s does not exists.', $id));
    }
}
