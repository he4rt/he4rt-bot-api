<?php

declare(strict_types=1);

namespace He4rt\Activity\Tracking\Enums;

use App\Enums\Concerns\StringifyEnum;
use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;

/**
 * Cada caso tem um produtor. Não adicione caso sem quem o produza — o enum é lido
 * como inventário do que a plataforma rastreia, não como roadmap.
 */
enum ActivityType: string implements HasColor, HasDescription, HasLabel
{
    use StringifyEnum;

    case Article = 'article';
    case PrOpened = 'pr_opened';
    case PrMerged = 'pr_merged';
    case Review = 'review';
    case ReviewComment = 'review_comment';
    case Comment = 'comment';
    case Commit = 'commit';
    case Issue = 'issue';

    public function getLabel(): string
    {
        return match ($this) {
            self::Article => 'Artigo',
            self::PrOpened => 'PR aberto',
            self::PrMerged => 'PR mergeado',
            self::Review => 'Review',
            self::ReviewComment => 'Comentário de review',
            self::Comment => 'Comentário',
            self::Commit => 'Commit',
            self::Issue => 'Issue',
        };
    }

    /**
     * @return array<int, string>
     */
    public function getColor(): array
    {
        return match ($this) {
            self::Article => Color::Violet,
            self::PrOpened => Color::Sky,
            self::PrMerged => Color::Emerald,
            self::Review => Color::Amber,
            self::ReviewComment => Color::Orange,
            self::Comment => Color::Slate,
            self::Commit => Color::Cyan,
            self::Issue => Color::Rose,
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Article => 'Artigo publicado numa plataforma de conteúdo',
            self::PrOpened => 'Pull request aberto num repositório rastreado',
            self::PrMerged => 'Pull request incorporado à base',
            self::Review => 'Revisão submetida num pull request',
            self::ReviewComment => 'Comentário em linha durante uma revisão',
            self::Comment => 'Comentário numa issue ou pull request',
            self::Commit => 'Commit enviado para um repositório rastreado',
            self::Issue => 'Issue aberta num repositório rastreado',
        };
    }
}
