<?php

namespace App\Exceptions;

use Exception;
use Symfony\Component\HttpFoundation\Response;

class MeetingsException extends Exception
{
    public static function meetingEnded(): self
    {
        return new self(
            __('meetings.errors.meetingEnded'),
            Response::HTTP_UNPROCESSABLE_ENTITY
        );
    }
}
