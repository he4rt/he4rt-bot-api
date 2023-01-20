<?php

namespace Heart\Meeting\Domain\Exceptions;

use Exception;
use Symfony\Component\HttpFoundation\Response;

class MeetingException extends Exception
{
    public static function nonexistentMeeting(): self
    {
        return new self('Nenhuma reunião acontecendo no momento', Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    public static function meetingTypeNotFound(): self
    {
        return new self('Tipo de reunião inexistente :/', Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
