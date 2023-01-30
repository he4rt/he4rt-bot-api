<?php

namespace Heart\Core\Exceptions;

use Exception;

class DomainNotExistsException extends Exception
{
    public static function domainNotInstantiable(string $domain): self
    {
        return new DomainNotExistsException('Domain '.$domain.' could not be instantiated.');
    }

    public static function pathNotFound(string $path): self
    {
        return new self('Path '.$path.' not exists.');
    }
}
