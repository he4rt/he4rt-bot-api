<?php

namespace Heart\Season\Application\Exceptions;

use Exception;
use Symfony\Component\HttpFoundation\Response;

class SeasonException extends Exception
{
    public static function noneSeasonFound(): self
    {
        return new self('None season found :(', Response::HTTP_NOT_FOUND);
    }
}
