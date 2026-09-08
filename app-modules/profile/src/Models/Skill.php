<?php

declare(strict_types=1);

namespace He4rt\Profile\Models;

use Carbon\CarbonInterface;
use He4rt\Profile\Database\Factories\SkillFactory;
use He4rt\Profile\Enums\SkillCategory;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $slug
 * @property string $name
 * @property SkillCategory $category
 * @property string|null $icon
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 */
#[UseFactory(factoryClass: SkillFactory::class)]
#[Table(name: 'skills')]
final class Skill extends Model
{
    /** @use HasFactory<SkillFactory> */
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'slug',
        'name',
        'category',
        'icon',
    ];

    /**
     * Server-side search for the skill select. Returns "Category · Name" labels,
     * capped — only matches travel to the client, so the catalog can grow freely.
     *
     * @param  array<int, string>  $exclude  skill ids to omit (e.g. already chosen in a repeater)
     * @return array<string, string>
     */
    public static function search(string $search, array $exclude = []): array
    {
        $results = [];

        self::query()
            ->when($search !== '', static fn (Builder $query): Builder => $query->where(
                static function (Builder $inner) use ($search): void {
                    $inner->where('name', 'ilike', '%'.$search.'%')
                        ->orWhere('slug', 'ilike', '%'.$search.'%');
                },
            ))
            ->when($exclude !== [], static fn (Builder $query): Builder => $query->whereNotIn('id', $exclude))
            ->orderBy('name')
            ->limit(50)
            ->get(['id', 'name', 'category'])
            ->each(static function (self $skill) use (&$results): void {
                $results[$skill->id] = $skill->category->getLabel().' · '.$skill->name;
            });

        return $results;
    }

    /**
     * Map of skill id => display name, for rendering pivot rows without a join.
     *
     * @return array<string, string>
     */
    public static function labelsById(): array
    {
        return once(static function (): array {
            $labels = [];

            self::query()
                ->get(['id', 'name'])
                ->each(static function (self $skill) use (&$labels): void {
                    $labels[$skill->id] = $skill->name;
                });

            return $labels;
        });
    }

    /**
     * @return HasMany<ProfileSkill, $this>
     */
    public function profileSkills(): HasMany
    {
        return $this->hasMany(ProfileSkill::class);
    }

    protected function casts(): array
    {
        return [
            'category' => SkillCategory::class,
        ];
    }
}
