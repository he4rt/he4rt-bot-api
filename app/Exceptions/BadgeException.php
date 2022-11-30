<?php

namespace App\Exceptions;

use Exception;
use Symfony\Component\HttpFoundation\Response;

class BadgeException extends Exception
{
    public static function alreadyClaimed(): self
    {
        return new self(
            __('badges.errors.alreadyClaimed'),
            Response::HTTP_BAD_REQUEST
        );
    }

    public static function inactiveBadge(): self
    {
        return new self(
            __('badges.errors.inactive'),
            Response::HTTP_BAD_REQUEST
        );
    }
}
