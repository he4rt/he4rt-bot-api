<?php

declare(strict_types=1);

namespace He4rt\Identity\User\Exceptions;

use Carbon\CarbonInterface;
use Exception;
use Throwable;

final class UsernameException extends Exception
{
    /**
     * @param  array<string, mixed>  $translationParams
     */
    public function __construct(
        string $message = '',
        int $code = 422,
        ?Throwable $previous = null,
        public readonly ?string $translationKey = null,
        public readonly array $translationParams = [],
    ) {
        parent::__construct($message, $code, $previous);
    }

    public static function alreadyTaken(string $username): self
    {
        return new self(
            sprintf('O @%s já está em uso por outro membro.', $username),
            422,
            translationKey: 'panel-app::profile.validation.username_already_taken',
            translationParams: ['username' => $username],
        );
    }

    public static function invalidFormat(string $reason, ?string $reasonKey = null): self
    {
        return new self(
            sprintf('Formato de @ inválido: %s.', $reason),
            422,
            translationKey: 'panel-app::profile.validation.username_invalid_format',
            translationParams: ['reason' => $reason, 'reason_key' => $reasonKey],
        );
    }

    public static function cooldownActive(CarbonInterface $availableAt): self
    {
        $timezone = (string) config('app.display_timezone', 'America/Sao_Paulo');
        $formattedDate = $availableAt->timezone($timezone)->format('d/m/Y H:i');

        return new self(
            sprintf('Você só poderá alterar seu @ novamente a partir de %s.', $formattedDate),
            422,
            translationKey: 'panel-app::profile.validation.username_cooldown_active',
            translationParams: ['date' => $formattedDate],
        );
    }

    public static function sameAsCurrent(): self
    {
        return new self(
            'O novo @ deve ser diferente do atual.',
            422,
            translationKey: 'panel-app::profile.validation.username_same_as_current',
        );
    }

    public static function reservedUsername(string $username): self
    {
        return new self(
            sprintf('O @%s está reservado para o sistema e não pode ser utilizado.', $username),
            422,
            translationKey: 'panel-app::profile.validation.username_reserved',
            translationParams: ['username' => $username],
        );
    }

    public function getLocalizedMessage(): string
    {
        if ($this->translationKey !== null) {
            $params = $this->translationParams;
            if (isset($params['reason_key']) && is_string($params['reason_key'])) {
                $translatedReason = __($params['reason_key']);
                if ($translatedReason !== $params['reason_key']) {
                    $params['reason'] = $translatedReason;
                }

                unset($params['reason_key']);
            }

            $translated = __($this->translationKey, $params);
            if ($translated !== $this->translationKey) {
                return $translated;
            }
        }

        return $this->getMessage();
    }
}
