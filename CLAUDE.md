<laravel-boost-guidelines>
=== .ai/domain/01-domain-docs rules ===

# Domain Docs

How to consume this repo's domain documentation when exploring the codebase.

## Before exploring, read these

- **`CONTEXT-MAP.md`** at the repo root — it points at one `CONTEXT.md` per module. Read each one relevant to the topic.
- **`docs/adr/`** — read ADRs that touch the area you're about to work in. Also check `app-modules/<module>/docs/adr/` for module-scoped decisions.

If any of these files don't exist, **proceed silently**. Don't flag their absence; don't suggest creating them upfront. The producer skill (`/grill-with-docs`) creates them lazily when terms or decisions actually get resolved.

## File structure

This is a multi-context repo (modular monorepo via `internachi/modular`):

```
/
├── CONTEXT-MAP.md                         <- system-wide context map
├── docs/adr/                              <- system-wide decisions
└── app-modules/
    ├── moderation/
    │   ├── CONTEXT.md
    │   └── docs/adr/                      <- module-specific decisions
    ├── bot-discord/
    │   ├── CONTEXT.md
    │   └── docs/adr/
    ├── identity/
    │   ├── CONTEXT.md
    │   └── docs/adr/
    └── ...
```

## Use the glossary's vocabulary

When your output names a domain concept (in an issue title, a refactor proposal, a hypothesis, a test name), use the term as defined in `CONTEXT.md`. Don't drift to synonyms the glossary explicitly avoids.

If the concept you need isn't in the glossary yet, that's a signal — either you're inventing language the project doesn't use (reconsider) or there's a real gap (note it for `/grill-with-docs`).

## Flag ADR conflicts

If your output contradicts an existing ADR, surface it explicitly rather than silently overriding:

> _Contradicts ADR-0007 — but worth reopening because..._

=== .ai/domain/02-module-architecture rules ===

# Module Architecture

This monorepo uses `internachi/modular`. Each module lives under `app-modules/{kebab-case}/` with namespace `He4rt\{PascalCase}\`.

Exception: `he4rt` module uses namespace `He4rt\Core`.

## Module types

| Type             | Prefix / Names                       | Contains                                      |
| ---------------- | ------------------------------------ | --------------------------------------------- |
| **Domain**       | `identity`, `moderation`, `economy`… | Business logic: Models, Actions, DTOs, Enums  |
| **Integration**  | `integration-*`, `bot-discord`       | External APIs: Transport, OAuth, ETL, Console |
| **Presentation** | `panel-*`, `portal`                  | UI: Filament Resources, Livewire, Blade, CSS  |

Presentation modules own UI concerns only. Domain logic belongs in domain modules — see `presentation/core` guideline.

## Canonical structure

```
app-modules/{module}/
├── composer.json
├── phpstan.neon
├── config/{module}.php                       (optional)
├── database/
│   ├── factories/
│   ├── migrations/
│   └── seeders/
├── lang/{en,pt_BR}/                          (optional)
├── routes/{topic}-routes.php                 (optional, auto-discovered)
├── resources/views/                          (optional, presentation only)
├── src/
│   ├── {ModuleName}ServiceProvider.php       <- always at src/ root, never in Providers/
│   ├── Actions/
│   ├── Models/
│   ├── DTOs/
│   ├── Enums/
│   ├── Exceptions/
│   ├── Concerns/
│   ├── Contracts/
│   └── ...
├── tests/
│   ├── Feature/
│   └── Unit/
├── CONTEXT.md                                (optional)
└── docs/adr/                                 (optional)
```

## Sub-namespace strategies

**Flat layers** — simple modules (economy, profile):
`src/Actions/`, `src/Models/`, `src/DTOs/`

**Sub-domain grouping** — complex modules (identity, moderation, activity):
`src/{SubDomain}/Actions/`, `src/{SubDomain}/Models/`

Examples: `identity` → `Auth/`, `User/`, `Tenant/`, `ExternalIdentity/`; `moderation` → `Cases/`, `Classification/`, `Enforcement/`, `Appeals/`.

## ServiceProvider

Always at `src/{ModuleName}ServiceProvider.php`. Minimal pattern:

<code-snippet name="Module ServiceProvider" lang="php">
namespace He4rt\{ModuleName};

class {ModuleName}ServiceProvider extends ServiceProvider
{
public function boot(): void
{
$this->loadMigrationsFrom(**DIR**.'/../database/migrations');

        Relation::morphMap([
            'some_class' => SomeClass::class,
            'another_class' => AnotherClass::class,
        ]);
    }

}
</code-snippet>

Add `mergeConfigFrom()`, `loadTranslationsFrom()`, `Event::listen()`, `Relation::morphMap()` as needed. Check a sibling module's ServiceProvider for the full pattern.

## Module composer.json

<code-snippet name="Module composer.json" lang="json">
{
    "name": "he4rt/{module-slug}",
    "autoload": {
        "psr-4": {
            "He4rt\\{ModuleName}\\": "src/",
            "He4rt\\{ModuleName}\\Database\\Factories\\": "database/factories/",
            "He4rt\\{ModuleName}\\Database\\Seeders\\": "database/seeders/"
        }
    }
}
</code-snippet>

## Version constraints — mandatory `^1.0.0` style

Every intra-repo `he4rt/*` module dependency (in the root `composer.json` and in any
module's `composer.json`) MUST be declared with the caret style `^1.0.0`. Never use
loose constraints like `>=1`, `*`, `dev-main`, or a truncated `^1.0`.

<code-snippet name="he4rt/* module constraints" lang="json">
{
    "require": {
        // GOOD — caret with full three-part version:
        "he4rt/identity": "^1.0.0",
        "he4rt/moderation": "^1.0.0",

        // BAD — loose or truncated constraints:
        "he4rt/identity": ">=1",
        "he4rt/moderation": "^1.0",
        "he4rt/economy": "*"
    }

}
</code-snippet>

This keeps every module pinned to a predictable, SemVer-compatible range and avoids
accidental major upgrades. When you add a new module dependency, match this style.

## Dependency rules

- **Domain** modules never import from Presentation or Integration.
- **Integration** modules may depend on Domain (e.g., Identity for user resolution).
- **Presentation** imports from Domain and Integration, never the reverse.
- Check `CONTEXT-MAP.md` for cross-context constraints.

## Registering a new module — mandatory label sync

Every `app-modules/<slug>/` has exactly one `mod:<slug>` triage label, and vice
versa. When you scaffold a **new** module you MUST, in the same change:

1. **Add a row** to the module table in `workflow/triage-labels` (`mod:<slug>` → `<slug>`).
2. **Create the label on GitHub** so the issue tracker can use it:
   `gh label create "mod:<slug>" --color "c2e0c6" --description "<short description>"`

Do not leave the guideline table and the live GitHub labels out of sync — an issue
that can't be tagged with its module's label means triage and routing break.

=== .ai/domain/04-model-phpdoc-sync rules ===

# Model PHPDoc Sync — Mandatory on Schema Changes

**Priority: HIGH** — This rule is non-negotiable. Every schema change MUST update the corresponding model PHPDoc.

## Rule

When you **add, remove, rename, or change the type** of any database column (via migration, manual SQL, or schema dump), the `@property` PHPDoc block on the affected Model class **MUST be updated in the same commit**.

## What triggers this rule

- `php artisan make:migration` that adds/removes/alters columns
- Any edit to an existing migration file
- Any raw SQL that changes table structure
- Renaming a column
- Changing a column type (e.g., `string` → `text`, `timestamp` → `timestampTz`)
- Adding/removing nullable
- Adding/removing a default value that changes the PHPDoc type (e.g., nullable → non-nullable)

## PHPDoc format

<code-snippet name="Model PHPDoc block" lang="php">
/**
 * @property string $id
 * @property int $position
 * @property string $name
 * @property string|null $description
 * @property bool $active
 * @property Carbon|null $starts_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Table(name: 'example_table')]
final class Example extends Model
</code-snippet>

## Type mapping

| Column type                            | PHPDoc type                  |
| -------------------------------------- | ---------------------------- |
| `uuid`                                 | `string`                     |
| `string`, `text`                       | `string`                     |
| `integer`, `bigInteger`                | `int`                        |
| `boolean`                              | `bool`                       |
| `timestamp`, `datetime`, `timestampTz` | `Carbon\|null`               |
| `json`, `jsonb`                        | `array<string, mixed>\|null` |
| `decimal`, `float`                     | `float`                      |
| `enum` (backed)                        | `EnumClass`                  |

- Add `|null` when the column is nullable.
- Use the cast type for enums and custom casts, not the raw DB type.
- `created_at` and `updated_at` are always `Carbon|null`.

## Explicit class-level attributes — mandatory

Every Eloquent model MUST declare these attributes explicitly, even when values match Laravel's convention:

- `#[Table(name: '...')]` — explicit table name, always required.
- `#[UseFactory(XxxFactory::class)]` — explicit factory binding, replaces `newFactory()` overrides. The `HasFactory` trait is still required (provides `factory()`).

<code-snippet name="Explicit model attributes" lang="php">
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;

// GOOD — always explicit:
#[UseFactory(UserFactory::class)]
#[Table(name: 'identity_users')]
final class User extends Model
{
/** @use HasFactory<UserFactory> */
use HasFactory;
}

// BAD — implicit table name or missing factory attribute:
final class User extends Model
{
/** @use HasFactory<UserFactory> */
use HasFactory;

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }

}
</code-snippet>

## Verification

Before marking a migration task as done, confirm:

1. The model file has a `/** @property ... */` block above the class
2. Every column in the table has a corresponding `@property` line
3. Types match the column definition and any explicit `casts()`
4. The model has `#[Table(name: '...')]` with the explicit table name
5. The model has `#[UseFactory(XxxFactory::class)]` if it has a factory
6. PHPStan passes (`vendor/bin/phpstan analyse`)

=== .ai/domain/05-timezone-aware-dates rules ===

# Migrations & Timezone-Aware Dates

**Priority: HIGH** — These rules are non-negotiable. Every migration MUST be created via Artisan with the correct module flag, and every date/time column MUST use timezone-aware types.

## Creating migrations

Always use `php artisan make:migration` to create migration files. Never create migration files manually.

This is a modular monorepo (`internachi/modular`). Every migration MUST target its module with the `--module` flag:

<code-snippet name="Creating a migration for a module" lang="bash">

# Always specify the module:

php artisan make:migration create_example_table --module=identity
php artisan make:migration add_expires_at_to_tokens --module=identity --table=tokens

# NEVER create migrations without --module (they end up in the wrong directory):

# BAD: php artisan make:migration create_example_table

# GOOD: php artisan make:migration create_example_table --module=identity

</code-snippet>

The `--module` flag places the migration in `app-modules/{module}/database/migrations/`, which is where the module's ServiceProvider loads migrations from.

## Timezone-aware date columns — mandatory

When creating or altering any database column that stores a date, time, or datetime value, you MUST use the timezone-aware (`Tz`) variant. Never use the non-tz variants.

### Required mappings

| NEVER use                      | ALWAYS use instead           |
| ------------------------------ | ---------------------------- |
| `$table->timestamp('col')`     | `$table->timestampTz('col')` |
| `$table->timestamps()`         | `$table->timestampsTz()`     |
| `$table->softDeletes()`        | `$table->softDeletesTz()`    |
| `$table->dateTime('col')`      | `$table->dateTimeTz('col')`  |
| `$table->nullableTimestamps()` | `$table->timestampsTz()`     |

### Context

This project uses PostgreSQL with `APP_TIMEZONE=UTC` and `display_timezone=America/Sao_Paulo`. The `timestamptz` type stores absolute UTC timestamps, allowing PostgreSQL to handle timezone conversion correctly. The non-tz `timestamp` type stores naive datetimes that lose timezone context and cause ±3h display bugs.

### Display timezone

When displaying dates to users, always use `config('app.display_timezone')`:

<code-snippet name="Display timezone conversion" lang="php">
// In Blade/Livewire:
$date->timezone(config('app.display_timezone'))->format('d/m/Y H:i')

// In Filament table columns:
TextColumn::make('created_at')
->dateTime('d/m/Y H:i')
->timezone(config('app.display_timezone'))
</code-snippet>

### In raw SQL queries

When converting timestamps for display in raw SQL, use `AT TIME ZONE` with the display timezone:

<code-snippet name="SQL timezone conversion" lang="sql">
-- Convert timestamptz to display timezone:
SELECT occurred_at AT TIME ZONE 'America/Sao_Paulo' AS local_time
FROM events;

-- NEVER use double AT TIME ZONE (causes +3h shift):
-- BAD: occurred_at AT TIME ZONE 'UTC' AT TIME ZONE 'America/Sao_Paulo'
-- GOOD: occurred_at AT TIME ZONE 'America/Sao_Paulo'
</code-snippet>

### Carbon usage

<code-snippet name="UTC timestamp creation" lang="php">
// Correct — uses app timezone (UTC):
now()
Carbon::now()

// For explicit UTC:
now()->utc()

// NEVER hardcode timezone in application logic:
// BAD: now()->timezone('America/Sao_Paulo')
// GOOD: now() (app is UTC, display converts later)
</code-snippet>

### PostgreSQL session timezone

Do NOT set `'timezone' => 'UTC'` in `config/database.php` pgsql connection. This causes double-conversion on `timestamptz` columns. Let PostgreSQL use its server default.

## What triggers this guideline

- `php artisan make:migration` — always use `--module=<module>`
- Any migration that adds date/time columns — always use `Tz` variants
- Any edit to an existing migration that touches date/time columns
- Creating a model with `php artisan make:model` — ensure generated migration uses `timestampsTz()`
- Display code showing dates — use `config('app.display_timezone')`
- Raw SQL with timestamp conversion — use single `AT TIME ZONE`

## Verification

Before marking any migration task as done, confirm:

1. Migration was created via `php artisan make:migration` with `--module=<module>`
2. Migration file lives in `app-modules/{module}/database/migrations/`
3. All new date/time columns use the `Tz` variant
4. No `timestamps()`, `timestamp()`, `softDeletes()`, or `dateTime()` without `Tz`
5. Display code uses `config('app.display_timezone')` for user-facing dates
6. Raw SQL queries use single `AT TIME ZONE` (never double)

=== .ai/domain/06-typed-json-casts rules ===

# Typed JSON Casts — Ban the Loose Array Cast

**Priority: HIGH.** Every JSON/jsonb column with a known or semi-structured shape MUST be
cast to a typed value object. A loose `'metadata' => 'array'` is an untyped, unvalidated,
refactor-hostile blind spot: PHPStan collapses to `mixed`, malformed payloads become
silent missing keys, and renaming a key is find-and-pray.

The same rule applies beyond casts: **prefer DTOs/VOs over associative arrays and
`stdClass` anywhere a shape is predictable** — command payloads, event properties,
integration Response Objects (Saloon), service returns.

## Banned casts (mechanically enforced)

Banned inside any model's `casts()`/`$casts`: `'array'`, `'json'`, `'object'`,
`'collection'`, `encrypted:array|collection|object`, `AsArrayObject`, `AsCollection`,
`AsEnumArrayObject`, `AsEnumCollection`, `AsEncryptedArrayObject`,
`AsEncryptedCollection`, and `SchemalessAttributes`.

The gate is `tests/Unit/NoLooseArrayCastsTest.php` — it reflects over every concrete
model (`app/Models` + `app-modules/*/src`), reads the real merged `getCasts()`, and fails
on any banned cast not explicitly allowlisted there. The allowlist is the documented
escape hatch for **genuinely polymorphic** JSON (each entry carries a reason); keep it
trending toward empty.

## The pattern

<code-snippet name="Before / after" lang="php">
// BEFORE — blind
'metadata' => 'array',
$model->metadata['last_projects_sync_at'] ?? null;   // mixed · magic string

// AFTER — typed VO cast (see AsCredentials in the identity module for a live example)
'metadata' => AsIdentityMetadata::class,
$model->metadata->lastSyncAt(Capability::Projects); // ?CarbonImmutable · verified
</code-snippet>

The VO owns shape, validation and accessors (`fromArray()` / `toArray()` / immutable
`withX()` copies). The cast is only the JSON ↔ VO bridge.

## Rules of thumb

- **Key/format conventions live on ONE owner** (usually the enum:
  `$capability->lastSyncedAtKey()`) — never reverse-parse strings to recover a key.
- **The cast generic is a contract for the setter too**: declare
  `CastsAttributes<TGet, TGet|array<array-key, mixed>>` so legacy array assignment stays
  a reachable, typed branch (`match (true)`).
- `json_decode` returns `array<array-key, mixed>`, never your shape — guard keys or
  iterate `EnumClass::cases()` instead of touching arbitrary keys.
- Genuinely polymorphic/freeform JSON → allowlist it **with a reason** and revisit.
- Never introduce `stdClass` in domain code; defensive layers (e.g. the kernel's
  `CanonicalJson`) may normalize it, but no domain object produces one.

=== .ai/domain/07-enum-filament-contracts rules ===

# Enums — Implement the Filament Contracts Immediately

**Priority: HIGH.** Every time you create a **new** backed enum, implement the Filament
"enum trick" contracts in the **same change** — never as a follow-up pass. Retrofitting
later means touching every enum (and every call site that assumed a bare `->value`) twice.

Domain enums implementing Filament contracts is the established precedent here (the
moderation enums — `CaseStatus`, `Severity`, `AppealStatus`, `ModerationType`, … — all do
it). The panel leans on these contracts for badges, table/infolist columns, `SelectFilter`
options, select descriptions and state-gated UI.

## Mandatory contracts

| Contract                                    | Method                      | Always?                                   |
| ------------------------------------------- | --------------------------- | ----------------------------------------- |
| `Filament\Support\Contracts\HasLabel`       | `getLabel(): string`        | Yes                                       |
| `Filament\Support\Contracts\HasColor`       | `getColor()`                | Yes                                       |
| `Filament\Support\Contracts\HasDescription` | `getDescription(): ?string` | Yes                                       |
| `Filament\Support\Contracts\HasIcon`        | `getIcon()`                 | Only when an icon is genuinely meaningful |

Every getter is a `match ($this)` over **all** cases — no `default` arm, so adding a case
is a compile-time reminder to fill in every trick.

## HasColor — ordered enums ramp light → red

When the enum encodes an **ordered scale** (access level, seniority, severity, risk, SLA
health), `getColor()` MUST form a monotonic light→red heat ramp: the least/lowest case gets
the lightest color, the most/highest gets `danger` (red). The six semantic strings
(`gray`, `info`, `primary`, `success`, `warning`, `danger`) cover short scales; for a longer
monotonic gradient use the `Filament\Support\Colors\Color` palette.

For an **unordered** enum (e.g. document kinds), pick distinct semantic colors per case —
the ramp rule does not apply.

## The pattern

<code-snippet name="New enum with the full contract set" lang="php">
use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum AccessTier: string implements HasColor, HasDescription, HasIcon, HasLabel
{
case None = 'none';
case Analyst = 'analyst';
case Admin = 'admin';

    public function getLabel(): string
    {
        return match ($this) {
            self::None => 'No access',
            self::Analyst => 'Analyst',
            self::Admin => 'Administration',
        };
    }

    // Ordered scale → light (no access) ramps to red (apex authority).
    public function getColor(): string|array
    {
        return match ($this) {
            self::None => 'gray',
            self::Analyst => Color::Blue,
            self::Admin => Color::Red,
        };
    }

    public function getDescription(): ?string
    {
        return match ($this) {
            self::None => 'Notification audience — no panel access',
            self::Analyst => 'Day-to-day operator: triage, review, moderation',
            self::Admin => 'Final authority: configures the panel, decides cases',
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::None => Heroicon::OutlinedNoSymbol,
            self::Analyst => Heroicon::OutlinedMagnifyingGlass,
            self::Admin => Heroicon::OutlinedScale,
        };
    }

}
</code-snippet>

## Verification

Before marking an enum task done, confirm:

1. The enum `implements HasColor, HasDescription, HasLabel` (and `HasIcon` when meaningful).
2. Every getter `match`es **all** cases with no `default`.
3. Ordered enums ramp light → `danger`; the apex case is red.
4. `vendor/bin/pint` and PHPStan pass.

=== .ai/presentation/01-core rules ===

# Presentation Layer

Modules with prefix `panel-*` and the `portal` module are **presentation layer**. They own UI concerns: Filament Resources, Pages, Widgets, Livewire components, and Blade views.

## Rule

Domain logic (Actions, Models, DTOs, business rules) belongs in domain modules (`identity`, `moderation`, `economy`, etc.), never in presentation modules.

Presentation modules import from domain modules.
Domain modules never import from presentation modules.

## Livewire

Whenever you're doing something with presentation layer, activate your `livewire-specialist` skill.

## Filament Rules

Research about Filament 5.x before implementing using `search-docs` MCP tool or `context7` if available.

Whenever you're in Filament Pages, Resources, Widgets, or Livewire components, use the `use` statement to import domain classes. This ensures that your presentation layer remains decoupled from domain logic.

If you're using Filament Actions, create a new action class to match the Domain Action. This keeps your presentation layer focused on UI logic.

<code-snippet name="Filament Action wrapping a Domain Action" lang="php">
class RegisterSubscriptionsAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('panel-admin::subfeature.actions.some_action.title-or-whatever'))
            ->icon(Heroicon::PlusCircle)
            ->color(Color::Sky)
            ->modalHeading(__('panel-admin::subfeature.actions.some_action.title-or-whatever'))
            ->modalSubmitActionLabel(__('panel-admin::subfeature.actions.some_action'))
            ->modalWidth(Width::ThreeExtraLarge)
            ->modalContent(function (): View {
                /** @var Tenant|null $tenant */
                $tenant = filament()->getTenant();

                return view('panel-admin::some-view', []);
            })
            ->action(function ($data): void {
                resolve(SomeAction::class)->execute(SomeDto::fromRequest($data));

                Notification::make()
                    ->success()
                    ->send();
            });
    }

}
</code-snippet>

=== .ai/presentation/02-knowledge-base rules ===

# Knowledge Base Documentation

This project uses `guava/filament-knowledge-base` for embedded docs inside the Filament admin panel. Docs are Markdown files rendered in the sidebar.

## Structure

All files live in `docs/admin/{lang}/`:

```
docs/admin/{lang}/
├── introduction.md
├── getting-started.md              (type: group)
│   └── getting-started/
│       ├── navigating-the-panel.md
│       ├── dashboard.md
│       └── profile.md
├── users.md                        (type: group)
│   └── users/
│       ├── managing-users.md
│       ├── roles.md
│       ├── teams.md
│       └── authentication.md
└── system.md                       (type: group)
    └── system/
        ├── activity-logs.md
        ├── emails.md
        └── configuration.md
```

### Rules

- Maximum **3 levels** of nesting.
- Group directories require a matching `.md` file at the same level with `type: group` in front matter.
- All files require YAML front matter: `title`, `icon`, `order`.
- Use `heroicon-o-*` icons (Heroicons outlined set).

### Front Matter

<code-snippet name="Page front matter" lang="yaml">
---
title: Page Title
icon: heroicon-o-document
order: 1
---
</code-snippet>

For groups, add `type: group`:

<code-snippet name="Group front matter" lang="yaml">
---
title: Group Name
icon: heroicon-o-folder
order: 2
type: group
---
</code-snippet>

## Keeping Docs in Sync

When changes affect user-facing behavior, update `docs/admin/en/`:

- **New resource/page** — add a doc file under the appropriate group.
- **Changed nav groups/labels** — update the group `.md` and children.
- **Added/removed/renamed form fields** — update the resource's doc page.
- **Auth/authorization changes** — update `users/authentication.md` and `users/roles.md`.
- **System features** (logs, emails, config) — update under `system/`.

## Key Files

- `app/Filament/Plugins/BetterKnowledgeBase.php` — sidebar navigation builder
- `config/filament-knowledge-base.php` — plugin config (cache TTL, icons, model)
- `resources/views/vendor/filament-knowledge-base/livewire/help-menu.blade.php` — contextual help popover
- `lang/{en,pt_BR}/knowledge_base.php` — KB UI translations

## Contextual Help (HasKnowledgeBase)

Resources can implement `HasKnowledgeBase` for per-resource sidebar help:

<code-snippet name="HasKnowledgeBase implementation" lang="php">
use Guava\FilamentKnowledgeBase\Contracts\HasKnowledgeBase;

class UserResource extends Resource implements HasKnowledgeBase
{
public static function getDocumentation(): array
{
return ['users.managing-users', 'users.roles'];
}
}
</code-snippet>

Doc IDs follow `{group}.{file-slug}` matching paths under `docs/admin/en/`.

=== .ai/tooling/01-phpstan rules ===

# PHPStan — ignoreErrors Conventions

When adding entries to `ignoreErrors` in any `phpstan.neon` file, always use
the **indented block style**: a lone `-` on its own line, with keys indented
beneath it. Never use the inline `- { ... }` style, as it requires horizontal
scrolling and hurts readability.

## Correct format

<code-snippet name="ignoreErrors block style" lang="neon">
parameters:
    ignoreErrors:
        -
            message: '#^Error message regex here#'
            identifier: error.identifier
            count: 1
            path: src/Path/To/File.php
</code-snippet>

## Rules

- `message` must be a regex wrapped in `#` delimiters.
- Always scope errors to a specific `path` — never leave an entry without one.
- Always include `count` so PHPStan warns if the number of occurrences changes.
- Always include `identifier` when PHPStan provides one (e.g. `property.notFound`).
- Do not escape spaces with `\ ` inside `#...#` regex patterns.
- Prefer fixing the root cause over ignoring. Only ignore when:
    - The error comes from a third-party or generated code.
    - The false positive is a known PHPStan/Larastan limitation (e.g. Livewire `$form`).

## Third-party stubs

Some vendor libraries ship imprecise stubs that report types wider than the
runtime guarantees. The canonical example is the Discord library
(`team-reflex/discord-php`), which types repository collections as
`array<T>|(Discord\Helpers\ExCollectionInterface&iterable<T>)` even though the
runtime value is always the collection. Calling `->find()` / `->get()` on it is
valid at runtime but PHPStan flags `method.nonObject` because the `array<T>`
branch has no such method.

Policy for these:

- Do **not** rewrite working runtime code to satisfy a wrong stub.
- Suppress the error in the **module-scoped** `phpstan.ignore.neon`
  (e.g. `app-modules/bot-discord/phpstan.ignore.neon`), never in the root config,
  so the suppression stays close to the affected module.
- Use the indented block style, scope to the `path`, pin the `count`, and include
  the `identifier` — exactly as for any other entry above.

## Baseline

Prefer running `vendor/bin/phpstan analyse --generate-baseline` for bulk
legacy errors. Manual `ignoreErrors` entries are reserved for intentional,
documented suppressions only.

=== .ai/workflow/01-issue-tracker rules ===

# Issue tracker: GitHub

Issues and PRDs for this repo live as GitHub issues on `he4rt/heartdevs.com`. Use the
`gh` CLI for all operations — `gh` infers the repo from `git remote -v`, so never
hard-code the `owner/repo`.

## When a skill says "publish to the issue tracker"

Create a real GitHub issue with `gh`, applying the triage/type/module/difficulty
labels from `workflow/triage-labels`, then show the user the created issue URL:

<code-snippet name="Create an issue" lang="bash">
gh issue create \
    --title "<type>(<module>): <short description>" \
    --body "<markdown body>" \
    --label "type:feat,mod:panel-admin,needs-triage"
</code-snippet>

Use a heredoc for multi-line bodies.

## When a skill says "fetch the relevant ticket"

Read it from GitHub rather than asking the user to paste it. Always include
`--comments` — the resolution usually lives in the thread (reporter clarifications,
the answer to a `needs-info` question, "dupe of #50"), not the opening post:

<code-snippet name="Read / list issues" lang="bash">
gh issue view <number> --comments
gh issue list --state open --label "needs-triage"
</code-snippet>

## Pull requests as a triage surface

The triage queue is **GitHub Issues** — `/triage` does not pull pull requests; PRs go
through normal code review, not triage.

Because issues and PRs share one number space, a bare `#42` may be either — resolve
with `gh pr view 42` and fall back to `gh issue view 42`.

## Triage labels

The taxonomy in `workflow/triage-labels` maps to **live GitHub labels**. Apply them
with `gh issue edit <number> --add-label "..."`. If a label does not exist yet,
create it first:

<code-snippet name="Create a missing label" lang="bash">
gh label create "mod:<name>" --description "<short description>" --color "c2e0c6"
</code-snippet>

## Conventions

- **Create an issue**: `gh issue create --title "..." --body "..."`. Use a heredoc for multi-line bodies.
- **Read an issue**: `gh issue view <number> --comments`, filtering comments by `jq` and also fetching labels.
- **List issues**: `gh issue list --state open --json number,title,body,labels,comments --jq '[.[] | {number, title, body, labels: [.labels[].name], comments: [.comments[].body]}]'` with appropriate `--label` and `--state` filters.
- **Comment on an issue**: `gh issue comment <number> --body "..."`
- **Apply / remove labels**: `gh issue edit <number> --add-label "..."` / `--remove-label "..."`
- **Close**: `gh issue close <number> --comment "..."`
- Confirm with the user before **bulk** operations or **closing/deleting** issues — those are outward-facing and harder to undo.

Infer the repo from `git remote -v` — `gh` does this automatically when run inside a clone.

=== .ai/workflow/02-triage-labels rules ===

# Triage Labels

The skills speak in terms of five canonical triage roles. This file maps those roles to the actual label strings used in this repo's issue tracker.

| Label in skills   | Label in our tracker | Meaning                                  |
| ----------------- | -------------------- | ---------------------------------------- |
| `needs-triage`    | `needs-triage`       | Maintainer needs to evaluate this issue  |
| `needs-info`      | `needs-info`         | Waiting on reporter for more information |
| `ready-for-agent` | `ready-for-agent`    | Fully specified, ready for an AFK agent  |
| `ready-for-human` | `ready-for-human`    | Requires human implementation            |
| `wontfix`         | `wontfix`            | Will not be actioned                     |

When a skill mentions a role (e.g. "apply the AFK-ready triage label"), use the corresponding label string from this table.

---

# Type Labels

Issue type follows conventional commit prefixes.

| Label           | Meaning                       |
| --------------- | ----------------------------- |
| `type:feat`     | New feature                   |
| `type:fix`      | Bug fix                       |
| `type:refactor` | Code refactoring              |
| `type:docs`     | Documentation                 |
| `type:prd`      | Product Requirements Document |
| `type:chore`    | Maintenance / tooling         |

---

# Module Labels

Every issue must be tagged with the module(s) it affects. Labels follow the pattern `mod:<module-name>`, matching the directory name under `app-modules/`.

| Label                     | Module directory      | Description                 |
| ------------------------- | --------------------- | --------------------------- |
| `mod:activity`            | `activity`            | Activity tracking           |
| `mod:bot-discord`         | `bot-discord`         | Discord bot                 |
| `mod:community`           | `community`           | Community features          |
| `mod:contents`            | `contents`            | Published content catalogue |
| `mod:economy`             | `economy`             | Economy/wallet system       |
| `mod:events`              | `events`              | Events & participation      |
| `mod:gamification`        | `gamification`        | XP, levels, ranking         |
| `mod:he4rt`               | `he4rt`               | Core/design system          |
| `mod:identity`            | `identity`            | Auth & user identity        |
| `mod:integration-devto`   | `integration-devto`   | Dev.to integration          |
| `mod:integration-discord` | `integration-discord` | Discord OAuth/API           |
| `mod:integration-twitch`  | `integration-twitch`  | Twitch integration          |
| `mod:marketing`           | `marketing`           | Encurtador, campanhas       |
| `mod:moderation`          | `moderation`          | Moderation pipeline         |
| `mod:panel-admin`         | `panel-admin`         | Admin Filament panel        |
| `mod:panel-app`           | `panel-app`           | User Filament panel         |
| `mod:portal`              | `portal`              | Public portal / homepage    |
| `mod:profile`             | `profile`             | User profiles               |
| `mod:docs`                | `docs`                | Knowledge base docs         |

When creating an issue for a new module that has no label yet, create the label first (`gh label create "mod:<name>" --description "<short description>" --color "c2e0c6"`) and add a row to this table.

---

# Difficulty Labels

Every implementable issue should be tagged with a difficulty estimate.

| Label                | Estimate  | Meaning                                          |
| -------------------- | --------- | ------------------------------------------------ |
| `difficulty:trivial` | < 1 day   | Deletion, config changes, scripts                |
| `difficulty:easy`    | 1-2 days  | Single model/action, well-defined scope          |
| `difficulty:medium`  | 3-5 days  | Multiple files, Filament UI, moderate complexity |
| `difficulty:hard`    | 1-2 weeks | Cross-module, complex logic, multiple panels     |
| `difficulty:epic`    | 2+ weeks  | Entire new system, major refactors               |

Issues tagged `difficulty:trivial` or `difficulty:easy` should also receive the `good first issue` label to help new contributors find approachable work.

---

# Title Convention

Issue titles follow **conventional commits** with the module as scope:

```
<type>(<module>): <short description in English>
```

Examples:

- `feat(profile): public profile page with domain routing`
- `refactor(gamification): XP system redesign`
- `fix(bot-discord): slash command timeout on large guilds`
- `prd(events): participation module MVP`

=== .ai/workflow/03-documentation-authoring rules ===

# Documentation Authoring

The repo has a documentation portal (`app-modules/docs`) that auto-discovers markdown across the
repository and serves it at `/docs`. Follow these conventions so what you write is discovered and
rendered correctly. Templates live in `app-modules/docs/stubs/*-FORMAT.md`.

## Where to save each document (co-location)

A document about **one module** lives inside that module; a **system-wide / cross-module** document
lives at the repo root. This mirrors the existing ADR rule, extended to every type:

```
app-modules/{module}/                  docs/            (system-wide / cross-module)
├── CONTEXT.md       (glossary)         ├── adr/
├── README.md        (entry point)      ├── specs/
└── docs/                               ├── plans/
    ├── adr/                            └── prd/
    ├── specs/
    ├── plans/
    └── prd/
```

- ADR numbering is **per module** (`{module}/docs/adr/0001-…`, `0002-…`), not global.
- Spec/Plan/PRD filenames are date-stamped: `AAAA-MM-DD-titulo.md` (PRDs may omit the date).
- When `brainstorm`/`grill-me` produce a spec or plan, save it under the **related module's** `docs/`
  (or `docs/` at the root if it spans modules) — not in a central `docs/superpowers/` folder.

## Front-matter standard

Add a YAML front-matter block so the portal builds badges and navigation. All keys optional, but
prefer them on new docs:

```yaml
---
type: spec # spec | plan | adr | prd
title: '...'
module: nome-do-modulo
status: ... # adr: accepted|superseded|… · plan: proposed|in_progress|completed
date: 2026-06-14
author: seu-handle-github # GitHub handle → avatar in the portal
related: # cross-links rendered as navigable links
    spec: nome-do-modulo/AAAA-MM-DD-titulo
---
```

The portal also reads the legacy inline style (`**Status:**`, `Builds on:`) as a fallback.

## README vs CONTEXT (do not duplicate)

- `CONTEXT.md` = glossary + module boundaries (conceptual).
- `README.md` = practical entry point + roadmap (concrete), linking to CONTEXT/ADRs.
- A module README **must not** include a column/schema table (that lives in the Model PHPDoc), a
  glossary (CONTEXT), or architecture decisions with rationale (those become ADRs). See
  `app-modules/docs/stubs/README-FORMAT.md`.

## Language

Write documentation in **pt_BR**. Existing English docs stay as-is; the portal renders each file as written.

=== .ai/workflow/04-branch-naming rules ===

# Branch Naming & Git Workflow

Agents MUST create branches using the canonical prefixes below. CI is a **merge
gate** defined in `.github/workflows/continuous-integration.yml`: it runs on a
pull request only when the PR's **base** (target) branch matches one of these
patterns. A branch that becomes a PR target outside this convention gets **no
pipeline**.

## Canonical branch prefixes

| Prefix                 | Use for                                        | Commit type   |
| ---------------------- | ---------------------------------------------- | ------------- |
| `feature/<slug>`       | New features                                   | `feat`        |
| `bugfix/<slug>`        | Bug fixes                                      | `fix`         |
| `chore/<slug>`         | Maintenance, dependencies, tooling, config, CI | `chore`       |
| `story/<issue>-<slug>` | Issue-driven work (a GitHub issue is open)     | fits the work |

- `<slug>` is lowercase kebab-case, e.g. `feature/public-profile-page`.
- `story/` embeds the issue number, e.g. `story/142-oauth-account-merge`.
- Prefixes map to the `type:` labels documented in the Triage Labels guideline.

## Base / integration branches

Every PR targets one of these bases — the patterns CI is gated on:

| Base pattern                                   | Meaning                                                |
| ---------------------------------------------- | ------------------------------------------------------ |
| `main`                                         | Default line                                           |
| `<major>.x`                                    | Versioned release lines (`1.x`, `2.x`) — Laravel style |
| `feature/**` `bugfix/**` `chore/**` `story/**` | Long-lived integration branches that receive sub-PRs   |

Long-lived integration branches use the **same prefixes**. Sub-work is PR'd into
them (e.g. `story/142-oauth-account-merge → feature/identity`); the integration
branch later merges into `main` or a `<major>.x` line.

## Rules for agents

- Branch off the correct base and name it with a canonical prefix. Never push
  work directly to `main` or a `<major>.x` line.
- Commit subjects follow conventional commits with the module as scope
  (`<type>(<module>): ...`) — see the Title Convention in the Triage Labels
  guideline.
- CI runs on PRs only; draft PRs are skipped. Open a non-draft PR — or mark it
  ready for review — to trigger the pipeline.
- Keep this table in sync with the `pull_request.branches` filter in
  `.github/workflows/continuous-integration.yml`; changing one without the other
  drifts the merge gate.

=== .ai/workflow/05-pull-requests rules ===

# Pull Requests

When creating or updating a GitHub pull request, agents should consider the repository PR template at `.github/pull_request_template.md` as a helpful reference for structuring the PR body.

## Guidance

- Prefer using the template sections `Contexto`, `Alterações`, `Plano de Testes`, `Evidências` and `Issues Relacionadas` when they fit the change.
- Keep the structure clear and review-friendly, rather than writing a completely free-form PR description.
- Populate the checklist in `Plano de Testes` with the validation steps that were actually run, including relevant commands such as `make check` and `make test` when applicable.
- Add issue references in the `Issues Relacionadas` section using the repository's expected format (for example: `Closes #123`) when there is a related issue to link.
- If the change has no visible UI impact, keep the `Evidências` section concise or leave it empty rather than inventing screenshots.

## Expectations for agents

- It is fine to draft a PR body without the template; the absence of the template does not block the PR.
- Adapt the content to the specific change and prefer clear, concise Portuguese when the repository and existing templates are written in Portuguese.
- If the PR is for a change that affects behavior, include enough detail for reviewers to understand the impact, the validation performed, and the related issues.

=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application running on PHP 8.4. You are an expert with the Laravel ecosystem. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:

- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Use `search-docs` before changes that depend on Laravel ecosystem APIs, behavior, configuration, or version-specific syntax. Skip it for copy-only edits and other changes where package documentation is irrelevant. Reuse sufficient results already in context instead of searching again.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists (settled decisions, non-obvious traps, standing constraints). Framework and package guidelines that only apply to specific paths (testing, frontend, components) also live there, under `.ai/rules/boost` — this is not just recorded decisions, it is load-bearing guidance you have not seen inline. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.
- Record durable rules with `record-rule` so the next agent or teammate inherits them instead of working them out again. Pass a `glob` (e.g. `app/Http/Controllers/**`), a short `title`, and a few-line `note`. Always use `record-rule`, never your native memory or notes tool — native memory is personal and session-scoped; only `.ai/rules` is shared with the team and persists in the repo.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
    - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.
- Activate the `deploying-to-cloud` skill whenever deploying to Laravel Cloud, configuring Cloud environments or resources, using the Cloud CLI, or troubleshooting Cloud deployments.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

# Pest

- This project uses Pest. Create tests with `php artisan make:test --pest {name}`.
- Do not include the test suite directory in `{name}`. Use `SomeFeatureTest`, not `Feature/SomeFeatureTest`.
- Read the `testing-best-practices` skill for guidance on coverage, naming, structure, dependency isolation, and review.
- Do not delete tests or test files without approval. They are part of the application.

## Running Tests

- Run the narrowest set of tests that covers the change. Pass a file path or `--filter=testName` to `php artisan test --compact`.
- Rerun a test after each change to it.
- Run `vendor/bin/pest` to call the test runner directly. It accepts the same file path and `--filter=testName` arguments.
- After the feature tests pass, ask the user to run the complete suite with `php artisan test --compact`.

=== internachi/modular/core rules ===

## Modular

- This a modular application. Each module is located in its own directory inside of `app-modules`.
- IMPORTANT: Activate `modular` every time you're working with or creating a new module.

=== spatie/laravel-medialibrary/core rules ===

## Media Library

- `spatie/laravel-medialibrary` associates files with Eloquent models, with support for collections, conversions, and responsive images.
- Always activate the `medialibrary-development` skill when working with media uploads, conversions, collections, responsive images, or any code that uses the `HasMedia` interface or `InteractsWithMedia` trait.

</laravel-boost-guidelines>
