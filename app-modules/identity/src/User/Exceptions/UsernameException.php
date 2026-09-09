<?php

declare(strict_types=1);

namespace He4rt\Identity\User\Exceptions;

use Carbon\CarbonInterface;
use Exception;

final class UsernameException extends Exception
{
    public static function alreadyTaken(string $username): self
    {
        return new self(sprintf('O @%s já está em uso por outro membro.', $username), 422);
    }

    public static function invalidFormat(string $reason): self
    {
        return new self(sprintf('Formato de @ inválido: %s.', $reason), 422);
    }

    public static function cooldownActive(CarbonInterface $availableAt): self
    {
        $timezone = (string) config('app.display_timezone', 'America/Sao_Paulo');
        $formattedDate = $availableAt->timezone($timezone)->format('d/m/Y H:i');

        return new self(sprintf('Você só poderá alterar seu @ novamente a partir de %s.', $formattedDate), 422);
    }

    public static function sameAsCurrent(): self
    {
        return new self('O novo @ deve ser diferente do atual.', 422);
    }

    public static function reservedUsername(string $username): self
    {
        return new self(sprintf('O @%s está reservado para o sistema e não pode ser utilizado.', $username), 422);
    }
}
