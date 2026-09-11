<?php

declare(strict_types=1);

namespace He4rt\IntegrationGithub\Models;

use Carbon\CarbonInterface;
use He4rt\IntegrationGithub\Database\Factories\GithubRepositoryFactory;
use He4rt\IntegrationGithub\Enums\PurposeType;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * @property string $id
 * @property string $full_name
 * @property bool $enabled
 * @property PurposeType $purpose
 * @property CarbonInterface|null $last_backfilled_at
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 */
#[Table(name: 'github_repositories')]
final class GithubRepository extends Model
{
    /** @use HasFactory<GithubRepositoryFactory> */
    use HasFactory;
    use HasUuids;

    protected static function newFactory(): GithubRepositoryFactory
    {
        return GithubRepositoryFactory::new();
    }

    /**
     * GitHub trata owner/repo como case-insensitive; guardamos sempre em minúsculas
     * (e sem espaços) para o matching com o payload do webhook e a retrospectiva.
     *
     * @return Attribute<string, string>
     */
    protected function fullName(): Attribute
    {
        return Attribute::make(
            set: fn (string $value): string => Str::lower(Str::trim($value)),
        );
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    #[Scope]
    protected function enabled(Builder $query): Builder
    {
        return $query->where('enabled', operator: true);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'last_backfilled_at' => 'datetime',
            'purpose' => PurposeType::class,
        ];
    }
}
