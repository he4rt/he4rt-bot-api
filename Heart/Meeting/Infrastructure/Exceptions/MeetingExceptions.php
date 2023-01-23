<?php

namespace Heart\Meeting\Infrastructure\Exceptions;

use Symfony\Component\HttpFoundation\Response;

class MeetingExceptions extends \Exception
{
    public static function meetingTypeNotFound(): self
    {
        return new self("meeting type not found!!", Response::HTTP_NOT_FOUND);
    }
}
