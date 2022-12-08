<?php

namespace App\Exceptions;

use Exception;
use Symfony\Component\HttpFoundation\Response;

class MeetingsException extends Exception
{
    public static function noAtiveMeetings(): self
    {
        return new self(
            __('meetings.errors.noAtiveMeetings'),
            Response::HTTP_UNPROCESSABLE_ENTITY
        );
    }
}
