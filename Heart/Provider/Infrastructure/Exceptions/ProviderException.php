<?php

namespace Heart\Provider\Infrastructure\Exceptions;

use Exception;
use Symfony\Component\HttpFoundation\Response;

class ProviderException extends Exception
{
    public static function notFound(string $provider, string $providerId): self
    {
        $message = sprintf(
            'Provider %s has not candidate for ID \'%s\'',
            $provider,
            $providerId
        );

        return new self($message, Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
