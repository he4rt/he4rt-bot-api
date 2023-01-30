<?php

namespace Heart\Core\Exceptions;

use Exception;

class DomainExtendException extends Exception
{
    public static function abstractClassNotExtended(): self
    {
        return new self('The domain must extend the abstract class \Heart\Core\Contracts\DomainInterface');
    }
}
