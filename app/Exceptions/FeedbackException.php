<?php

namespace App\Exceptions;

use Exception;
use Symfony\Component\HttpFoundation\Response;

class FeedbackException extends Exception
{
    public static function idNotFound(int $id): self
    {
        return new self(sprintf('The feedback with id %s does not exists.', $id), Response::HTTP_NOT_FOUND);
    }

    public function render($request)
    {
        return response($this->getMessage(), $this->code);
    }
}
