<?php

namespace App\Exceptions;

use Exception;
use Symfony\Component\HttpFoundation\Response;

class MeetingsException extends Exception
{
    public static function noActiveMeetings(): self
    {
        return new self(
            __('meetings.errors.noActiveMeetings'),
            Response::HTTP_UNPROCESSABLE_ENTITY
        );
    }

    public static function alreadyAttended(): self
    {
        return new self(
            __('meetings.errors.alreadyAttended'),
            Response::HTTP_UNPROCESSABLE_ENTITY
        );
    }
}
