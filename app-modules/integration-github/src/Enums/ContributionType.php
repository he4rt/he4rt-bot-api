<?php

declare(strict_types=1);

namespace He4rt\IntegrationGithub\Enums;

use App\Enums\Concerns\StringifyEnum;

enum ContributionType: string
{
    use StringifyEnum;

    case Pr = 'pr';
    case Review = 'review';
    case Issue = 'issue';
    case Comment = 'comment';
    case ReviewComment = 'review_comment';
    case Commit = 'commit';

    /**
     * Constrói o ref namespaced da contribuição (ex.: "pr:123", "review_comment:42").
     * O valor do enum é o próprio prefixo, então cada tipo conhece o seu.
     */
    public function ref(int|string $id): string
    {
        return $this->value.':'.$id;
    }

    /**
     * Se a própria referência da contribuição é o número que a identifica no repo.
     * Comentários e reviews penduram-se num número alheio, guardado em target_ref;
     * commit não tem número nenhum.
     */
    public function carriesNumber(): bool
    {
        return match ($this) {
            self::Pr, self::Issue => true,
            self::Review, self::Comment, self::ReviewComment, self::Commit => false,
        };
    }
}
