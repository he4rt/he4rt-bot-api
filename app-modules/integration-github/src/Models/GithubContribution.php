<?php

declare(strict_types=1);

namespace He4rt\IntegrationGithub\Models;

use Carbon\CarbonInterface;
use He4rt\Activity\Tracking\Contracts\ContributionDetail;
use He4rt\IntegrationGithub\Database\Factories\GithubContributionFactory;
use He4rt\IntegrationGithub\Enums\ContributionType;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $repo
 * @property string $actor_login
 * @property int|null $actor_id
 * @property ContributionType $type
 * @property string $external_ref
 * @property string|null $target_ref
 * @property CarbonInterface $occurred_at
 * @property array<string, mixed>|null $metadata
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 */
#[Table(name: 'github_contributions')]
final class GithubContribution extends Model implements ContributionDetail
{
    /** @use HasFactory<GithubContributionFactory> */
    use HasFactory;
    use HasUuids;

    /**
     * O lake só guarda título para PR e issue. Para o resto, o nome legível é a
     * própria referência — inventar um título aqui seria mentir com mais letras.
     */
    public function contributionTitle(): string
    {
        $title = $this->metadata['title'] ?? null;

        if (is_string($title) && $title !== '') {
            return $title;
        }

        return match ($this->type) {
            ContributionType::Commit => 'Commit '.mb_substr($this->localRef(), 0, 7),
            ContributionType::Review => 'Revisão submetida',
            ContributionType::ReviewComment => 'Comentário em linha',
            ContributionType::Comment => 'Comentário',
            default => $this->localRef(),
        };
    }

    public function contributionContext(): string
    {
        $target = $this->target_ref ?? ($this->type->carriesNumber() ? $this->external_ref : null);

        if ($target === null) {
            return $this->repo;
        }

        return $this->repo.' #'.$this->numberFrom($target);
    }

    public function contributionUrl(): ?string
    {
        $url = $this->metadata['url'] ?? null;

        return is_string($url) && $url !== '' ? $url : null;
    }

    protected static function newFactory(): GithubContributionFactory
    {
        return GithubContributionFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ContributionType::class,
            'actor_id' => 'integer',
            'occurred_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    private function localRef(): string
    {
        return explode(':', $this->external_ref, 2)[1] ?? $this->external_ref;
    }

    private function numberFrom(string $ref): string
    {
        return explode(':', $ref, 2)[1] ?? $ref;
    }
}
