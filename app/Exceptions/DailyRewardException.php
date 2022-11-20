<?php

namespace App\Exceptions;

use Carbon\Carbon;
use Exception;
use Symfony\Component\HttpFoundation\Response;

class DailyRewardException extends Exception
{
    public static function alreadyRedeemed(Carbon $daily)
    {
        throw new self('Comando já utilizado hoje.', Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
