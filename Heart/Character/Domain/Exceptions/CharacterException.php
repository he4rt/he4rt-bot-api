<?php

namespace Heart\Character\Domain\Exceptions;

use Exception;
use Heart\Character\Domain\Entities\DailyRewardEntity;
use Symfony\Component\HttpFoundation\Response;

class CharacterException extends Exception
{
    public static function alreadyClaimed(DailyRewardEntity $dailyReward): self
    {
        return new self(
            sprintf('Você já resgatou hoje! Faltam %s minutos para o próximo resgate.', $dailyReward->minutesUntilClaim()),
            Response::HTTP_FORBIDDEN
        );
    }
}
