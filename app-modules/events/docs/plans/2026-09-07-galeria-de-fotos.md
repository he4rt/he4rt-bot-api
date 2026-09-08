---
type: plan
title: 'Galeria de fotos: álbuns por evento no portal e no admin'
module: events
status: proposed
date: 2026-09-07
author: danielhe4rt
related:
    issue: https://github.com/he4rt/heartdevs.com/issues/504
---

# Galeria de fotos — plano de implementação

## Goal

Entregar a issue #504 com a leitura que a discussão convergiu e as decisões fechadas em 2026-09-07:

| Decisão                    | Escolha                                                                                        |
| -------------------------- | ---------------------------------------------------------------------------------------------- |
| Onde aparece               | Só `/galeria` pública, com link "Galeria" na navbar. A Home não muda nesta entrega.             |
| Onde mora o domínio        | Sub-domínio `Gallery/` dentro de `app-modules/events` (namespace `He4rt\Events\Gallery`).      |
| Álbum × Evento             | `Album` é entidade própria com `event_id` **opcional** apontando para `Event`.                 |
| Gestão                     | Painel admin (`panel-admin`), CRUD de álbum + upload em lote via `SpatieMediaLibraryFileUpload`. |
| Volume e curadoria         | Equipe sobe tudo do evento. Cada foto pode ser marcada como **destaque**; o card do álbum mostra os destaques. |
| Armazenamento              | Disco `public` local, como todo `HasMedia` do repo. Conversões `thumb` e `large` em WebP, **em fila**. |
| Lightbox                   | Alpine escrito à mão dentro de `@assets`, zero dependência npm (padrão do `articles.blade.php`). |

Referências de padrão que este plano copia, por ordem de importância:

- `app-modules/marketing/docs/adr/0004-borda-http-no-portal.md` — domínio puro, rota no portal, CRUD no admin.
- `app-modules/identity/src/User/Concerns/HasProfileImages.php` + `Enums/ProfileImage.php` — collection, conversão, fallback de URL.
- Commit `bbd0d2ec` (fix de capa/avatar) — armadilhas de `imageAspectRatio()` e teto de upload do PHP.
- `app-modules/panel-admin/src/Filament/Resources/Events/**` — anatomia de Resource, Schemas, Tables, RelationManagers.
- `app-modules/portal/src/Articles/**` + `resources/views/articles.blade.php` — página full-page Livewire, read model, Alpine em `@assets`.

## Arquitetura

```text
  ┌────────────────────────┐   Livewire/Filament   ┌──────────────────────────────┐
  │  panel-admin           │ ────────────────────► │  events (domínio)            │
  │  AlbumResource         │   Album::create()     │  Gallery/                    │
  │  PhotosRelationManager │   CuratePhoto         │   Models/Album (HasMedia)    │
  └────────────────────────┘                       │   Enums/PhotoConversion      │
                                                   │   DTOs/Photo                 │
  ┌────────────────────────┐   read model          │   Actions/CuratePhoto        │
  │  portal                │ ────────────────────► │                              │
  │  GET /galeria          │   Album::published()  │  events_albums ──0..1──► events│
  │  GET /galeria/{slug}   │   ->withPhotos()      │        │ morphMany            │
  │  AlbumFeed → DTOs      │                       │      media (spatie)          │
  └────────────────────────┘                       └──────────────┬───────────────┘
                                                                  │ disco public
                                                   ┌──────────────▼───────────────┐
                                                   │ storage/app/public/{media}/  │
                                                   │   original.jpg               │
                                                   │   conversions/*-thumb.webp   │
                                                   │   conversions/*-large.webp   │
                                                   └──────────────────────────────┘
```

Regras de dependência que mudam: `portal` passa a depender de `events` (apresentação depende de domínio, permitido). `events` continua sem importar de `portal` nem de `panel-admin`.

## Pipeline do upload

```text
  [Admin]              [Filament]                 [Spatie]                    [Fila]
     │                     │                         │                          │
  arrasta 40 jpg ────► SpatieMediaLibraryFileUpload  │                          │
                       valida mime + maxSize ──────► addMediaFromString()       │
                       (min(10 MB, php.ini))         toMediaCollection('photos')│
                                                     order_column = posição     │
                                                     custom_properties = {}     │
                                                     dispatch PerformConversions ─► thumb 640×480 webp (Crop)
                                                                                 ─► large 1920 webp (Max)
  [Admin] marca destaque + legenda ──► CuratePhoto ─► custom_properties {highlight: true, caption: "..."}

  [Portal] ────────► Album::published()->withPhotos() ─► AlbumFeed ─► PhotoView
                     thumb pronto?  sim → getUrl('thumb')
                                    não → getUrl() (original, até a fila rodar)
```

## Estados do álbum

```text
  [rascunho] ──published_at preenchido──► [publicado] ──published_at = null──► [rascunho]
       │                                        │
       └── invisível no portal                  └── visível em /galeria se tiver ≥ 1 foto
                                                    (sem foto: some da lista e /galeria/{slug} dá 404)
```

## Telas

### `/galeria` (lista de álbuns)

```text
  ┌─────────────────────────────────────────────────────────────────┐
  │ [navbar]  Artigos · Redes · Galeria(ativo)                       │
  ├─────────────────────────────────────────────────────────────────┤
  │  ◦ Galeria                                                       │
  │  O que acontece quando a gente sai do Discord.                   │
  │  Alguns registros dos nossos encontros. Tem palestra, tem        │
  │  workshop — e tem muita conversa fora da tela.                   │
  ├─────────────────────────────────────────────────────────────────┤
  │ ┌───────────────────────────────────────────────────────────┐   │
  │ │ Pub #2            ┌──────┐ ┌──────┐ ┌──────┐ ┌ ─ ─ ┐      │   │
  │ │ Curitiba          │thumb │ │thumb │ │thumb │   +28        │   │
  │ │ Agosto de 2025    │legend│ │legend│ │legend│ └ ─ ─ ┘      │   │
  │ │ [31 fotos]        └──────┘ └──────┘ └──────┘              │   │
  │ └───────────────────────────────────────────────────────────┘   │
  │ ┌───────────────────────────────────────────────────────────┐   │
  │ │ Pub #1  · São Paulo · Março de 2025 · [24 fotos] ...      │   │
  │ └───────────────────────────────────────────────────────────┘   │
  │  (mobile: coluna única, 3 thumbs em linha, card inteiro é link)  │
  └─────────────────────────────────────────────────────────────────┘
```

### `/galeria/{slug}` (álbum + lightbox)

```text
  ┌─────────────────────────────────────────────────────────────────┐
  │ ← Galeria                                                        │
  │ Pub #2                                                           │
  │ Curitiba · Agosto de 2025 · 31 fotos · [evento: Pub #2 ↗]*       │
  │ Descrição do álbum.                                              │
  ├─────────────────────────────────────────────────────────────────┤
  │ ┌────┐ ┌────┐ ┌────┐ ┌────┐        grid auto-fill 220px         │
  │ │    │ │    │ │    │ │    │        loading=lazy, thumb webp      │
  │ └────┘ └────┘ └────┘ └────┘                                      │
  │ ┌────┐ ┌────┐ ┌────┐ ┌────┐                                      │
  └─────────────────────────────────────────────────────────────────┘
        │ clique / Enter em uma foto
        ▼
  ┌─────────────────────────────────────────────────────────────────┐
  │ [x]                                                     12 / 31  │
  │        ◄     ┌───────────────────────────┐     ►                 │
  │              │        large webp         │                       │
  │              └───────────────────────────┘                       │
  │              Legenda da foto                                     │
  │  teclas: ← → Esc · swipe no touch · URL ?foto=12 (replaceState)  │
  └─────────────────────────────────────────────────────────────────┘
  * só quando event_id preenchido. Não há rota pública de evento hoje: renderizar
    como texto, sem link, até existir.
```

### Admin: `EditAlbum`

```text
  ┌─────────────────────────────────────────────────────────────────┐
  │ Álbum: Pub #2                       [Salvar]  [Excluir]          │
  ├─────────────────────────────────────────────────────────────────┤
  │ Título ______________________   Slug ____________________        │
  │ Evento (opcional) [Select ▾]    Aconteceu em [__/__/____]        │
  │ Local __________________________                                 │
  │ Descrição ______________________________________________          │
  │ Publicado em [__/__/____ __:__]  (vazio = rascunho)              │
  │ Fotos                                                            │
  │ ┌──┐┌──┐┌──┐┌──┐┌──┐┌──┐┌──┐┌──┐  arraste até 100 · 10 MB cada  │
  │ └──┘└──┘└──┘└──┘└──┘└──┘└──┘└──┘  reordenável                     │
  ├─────────────────────────────────────────────────────────────────┤
  │ Fotos (RelationManager)                          ⇅ reordenar     │
  │ ┌────┬─────────┬──────────────────────┬──────────┬────────────┐  │
  │ │thumb│ Destaque│ Legenda              │ Tamanho  │ Enviada em │  │
  │ │ ▣  │  [x]    │ [Lightning talks    ] │ 2,1 MB   │ 03/09 14:02│  │
  │ │ ▣  │  [ ]    │ [                   ] │ 1,8 MB   │ 03/09 14:02│  │
  │ └────┴─────────┴──────────────────────┴──────────┴────────────┘  │
  └─────────────────────────────────────────────────────────────────┘
```

## Fluxo de curadoria

```text
USER (admin)                          SYSTEM
  │                                       │
  │ 👆 Novo álbum, preenche título        │
  │ ─────────────────────────────────►    │ slug sugerido via Str::slug (só se vazio)
  │ 👆 escolhe Evento "Pub #2"            │
  │ ─────────────────────────────────►    │ afterStateUpdated: preenche local e data
  │                                       │ se os campos estiverem vazios
  │ 👆 arrasta 40 fotos                   │
  │ ─────────────────────────────────►    │ ⚙️ valida mime/tamanho, salva média,
  │                                       │    enfileira conversões
  │ 👆 Salvar                             │
  │ ─────────────────────────────────►    │ ✓ Album criado (rascunho)
  │                                       │
  │ 👆 RelationManager: marca 3 destaques │
  │    e escreve legendas                 │
  │ ─────────────────────────────────►    │ CuratePhoto grava custom_properties
  │ 👆 Publicado em = agora, Salvar       │
  │ ─────────────────────────────────►    │ ✓ /galeria passa a listar o álbum
```

---

## Comandos

Rodar antes de escrever código, nesta ordem. Sempre pelo MCP `lerd` (regra da sessão), nunca `php artisan` direto no shell.

```bash
php artisan make:migration create_events_albums_table --module=events --no-interaction
php artisan make:factory AlbumFactory --no-interaction   # mover para app-modules/events/database/factories/
php artisan make:test --pest AlbumTest --no-interaction  # mover para app-modules/events/tests/Feature/Gallery/
```

Resource, páginas e RelationManager do Filament são criados à mão espelhando `app-modules/panel-admin/src/Filament/Resources/Events/`. O gerador do Filament grava em `app/Filament`, namespace que este repo não usa.

---

## Fase 1 — Domínio (`app-modules/events`)

### Passo 1.1: Migration `events_albums`

- [ ] Criar a migration via artisan com `--module=events`.

**Context.** Não existe nenhuma tabela de álbum ou foto no repo. A tabela segue o estilo de `2026_05_16_200001_create_events_table.php`: UUID como PK, `timestampsTz()`, índices nomeados com prefixo `idx_`. O vínculo com `events` é opcional e não pode apagar o álbum quando o evento some (`nullOnDelete`). A tabela `media` do Spatie já existe (`database/migrations/2025_11_05_135059_create_media_table.php`) com `model_id` string, compatível com UUID.

```php
// app-modules/events/database/migrations/2026_09_07_000000_create_events_albums_table.php
Schema::create('events_albums', static function (Blueprint $table): void {
    $table->uuid('id')->primary();
    $table->foreignUuid('event_id')->nullable()->constrained('events')->nullOnDelete();
    $table->string('slug', 120);
    $table->string('title', 200);
    $table->string('location')->nullable();
    $table->date('happened_at');
    $table->text('description')->nullable();
    $table->timestampTz('published_at')->nullable();
    $table->timestampsTz();

    $table->unique('slug', 'idx_events_albums_slug');
    $table->index(['published_at', 'happened_at'], 'idx_events_albums_public_order');
});
```

`happened_at` é `date` de propósito: a UI mostra "Agosto de 2025", não hora. `date` não tem variante Tz e não sofre o bug de ±3h.

**Expected behavior.**

```gherkin
Feature: Tabela de álbuns
    Scenario: Evento apagado não leva o álbum junto
        Given um álbum vinculado ao evento "Pub #2"
        When o evento é apagado
        Then o álbum continua existindo com event_id nulo

    Scenario: Slug é único
        Given um álbum com slug "pub-2"
        When outro álbum tenta usar o slug "pub-2"
        Then o banco rejeita a inserção
```

### Passo 1.2: Enum `PhotoConversion`

- [ ] Criar `app-modules/events/src/Gallery/Enums/PhotoConversion.php`.

**Context.** `ProfileImage` prova que variação de imagem é dado, não subclasse. As duas conversões da galeria nascem num enum que é a única fonte de nome, dimensão e `Fit`. Por regra do repo todo enum novo implementa os contratos do Filament no mesmo commit.

```php
namespace He4rt\Events\Gallery\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;
use Spatie\Image\Enums\Fit;

enum PhotoConversion: string implements HasColor, HasDescription, HasLabel
{
    case Thumb = 'thumb';
    case Large = 'large';

    /** @return list<string> */
    public static function mimeTypes(): array
    {
        return ['image/jpeg', 'image/png', 'image/webp'];
    }

    public static function maxKilobytes(): int
    {
        return 10_240;
    }

    public function width(): int
    {
        return match ($this) {
            self::Thumb => 640,
            self::Large => 1_920,
        };
    }

    public function height(): int
    {
        return match ($this) {
            self::Thumb => 480,
            self::Large => 1_920,
        };
    }

    public function fit(): Fit
    {
        return match ($this) {
            self::Thumb => Fit::Crop,
            self::Large => Fit::Max,
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Thumb => __('events::enums.photo_conversion.thumb'),
            self::Large => __('events::enums.photo_conversion.large'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Thumb => 'gray',
            self::Large => 'primary',
        };
    }

    public function getDescription(): ?string
    {
        return match ($this) {
            self::Thumb => __('events::enums.photo_conversion.thumb_description'),
            self::Large => __('events::enums.photo_conversion.large_description'),
        };
    }
}
```

GIF fica fora. Foto de evento não é animação, e aceitar GIF exigiria o caminho "servido sem conversão" do perfil.

Adicionar ao `app-modules/events/lang/{en,pt_BR}/enums.php` a chave `photo_conversion` com `thumb`, `large`, `thumb_description`, `large_description`.

### Passo 1.3: DTO `Photo` e action `CuratePhoto`

- [ ] Criar `app-modules/events/src/Gallery/DTOs/Photo.php`.
- [ ] Criar `app-modules/events/src/Gallery/DTOs/CuratePhotoData.php`.
- [ ] Criar `app-modules/events/src/Gallery/Actions/CuratePhoto.php`.

**Context.** Legenda e destaque vivem em `custom_properties` do `Media`, como o `focal_y` do perfil. Para não espalhar as strings `caption` e `highlight` pelo admin e pelo portal, as chaves ficam em um único dono: o DTO `Photo`. ADR-0003 do módulo manda toda escrita passar por Action.

```php
namespace He4rt\Events\Gallery\DTOs;

use Spatie\MediaLibrary\MediaCollections\Models\Media;

final readonly class Photo
{
    public const string CAPTION = 'caption';

    public const string HIGHLIGHT = 'highlight';

    public function __construct(
        public int $id,
        public string $uuid,
        public ?string $caption,
        public bool $highlight,
        public int $position,
    ) {}

    public static function fromMedia(Media $media): self
    {
        $caption = $media->getCustomProperty(self::CAPTION);

        return new self(
            id: (int) $media->getKey(),
            uuid: (string) $media->uuid,
            caption: is_string($caption) && $caption !== '' ? $caption : null,
            highlight: (bool) $media->getCustomProperty(self::HIGHLIGHT, false),
            position: (int) ($media->order_column ?? 0),
        );
    }
}
```

```php
namespace He4rt\Events\Gallery\DTOs;

final readonly class CuratePhotoData
{
    public function __construct(
        public ?string $caption,
        public bool $highlight,
    ) {}
}
```

```php
namespace He4rt\Events\Gallery\Actions;

use He4rt\Events\Gallery\DTOs\CuratePhotoData;
use He4rt\Events\Gallery\DTOs\Photo;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

final class CuratePhoto
{
    public function handle(Media $media, CuratePhotoData $data): Media
    {
        $caption = $data->caption !== null ? mb_trim($data->caption) : null;

        $media
            ->setCustomProperty(Photo::CAPTION, $caption !== '' ? $caption : null)
            ->setCustomProperty(Photo::HIGHLIGHT, $data->highlight);

        $media->save();

        return $media;
    }
}
```

URL e conversão não entram no DTO de domínio. Quem monta URL é o portal (Passo 3.2), porque a URL depende do fallback para o original enquanto a fila não rodou.

**Expected behavior.**

```gherkin
Feature: Curadoria de foto
    Scenario: Marcar destaque com legenda
        Given uma foto sem propriedades
        When CuratePhoto recebe caption "Lightning talks" e highlight true
        Then custom_properties tem caption "Lightning talks" e highlight true

    Scenario: Legenda em branco vira nula
        When CuratePhoto recebe caption "   " e highlight false
        Then custom_properties.caption é null
```

### Passo 1.4: Model `Album`

- [ ] Criar `app-modules/events/src/Gallery/Models/Album.php`.
- [ ] Criar `app-modules/events/database/factories/AlbumFactory.php`.

**Context.** Único `HasMedia` do módulo `events`. Copia o desenho de `HasProfileImages`: collection no disco `public`, mime restrito, conversões registradas dentro da collection. Diferença: conversões ficam **em fila** (default `queue_conversions_by_default = true`, sem `nonQueued()`), porque um upload de 40 fotos síncrono estouraria o request. O portal cai no original enquanto a conversão não existe.

```php
namespace He4rt\Events\Gallery\Models;

use Carbon\Carbon;
use He4rt\Events\Database\Factories\AlbumFactory;
use He4rt\Events\Event\Models\Event;
use He4rt\Events\Gallery\DTOs\Photo;
use He4rt\Events\Gallery\Enums\PhotoConversion;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @property string $id
 * @property string|null $event_id
 * @property string $slug
 * @property string $title
 * @property string|null $location
 * @property Carbon $happened_at
 * @property string|null $description
 * @property Carbon|null $published_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read int|null $photos_count
 */
#[UseFactory(AlbumFactory::class)]
#[Table(name: 'events_albums')]
final class Album extends Model implements HasMedia
{
    /** @use HasFactory<AlbumFactory> */
    use HasFactory;
    use HasUuids;
    use InteractsWithMedia;

    public const string PHOTOS = 'photos';

    public const int CARD_HIGHLIGHTS = 3;

    protected $fillable = [
        'event_id', 'slug', 'title', 'location', 'happened_at', 'description', 'published_at',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::PHOTOS)
            ->useDisk('public')
            ->acceptsMimeTypes(PhotoConversion::mimeTypes())
            ->registerMediaConversions(function (?Media $media = null): void {
                foreach (PhotoConversion::cases() as $conversion) {
                    $this->addMediaConversion($conversion->value)
                        ->fit($conversion->fit(), $conversion->width(), $conversion->height())
                        ->format('webp');
                }
            });
    }

    /** @return BelongsTo<Event, $this> */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Só as fotos, já na ordem do admin. Existe além de getMedia() para
     * withCount('photos') e eager loading na listagem.
     *
     * @return MorphMany<Media, $this>
     */
    public function photos(): MorphMany
    {
        return $this->media()
            ->where('collection_name', self::PHOTOS)
            ->orderBy('order_column');
    }

    /** @return Collection<int, Media> */
    public function highlights(): Collection
    {
        $photos = $this->getMedia(self::PHOTOS);

        $flagged = $photos->filter(
            fn (Media $media): bool => (bool) $media->getCustomProperty(Photo::HIGHLIGHT, false),
        );

        return $flagged->isNotEmpty() ? $flagged->values() : $photos->take(self::CARD_HIGHLIGHTS)->values();
    }

    public function cover(): ?Media
    {
        return $this->highlights()->first();
    }

    public function isPublished(): bool
    {
        return $this->published_at !== null && $this->published_at->isPast();
    }

    /** @param  Builder<self>  $query */
    protected function scopePublished(Builder $query): void
    {
        $query->whereNotNull('published_at')->where('published_at', '<=', now());
    }

    /** @param  Builder<self>  $query */
    protected function scopeWithPhotos(Builder $query): void
    {
        $query->whereHas('photos');
    }

    /** @param  Builder<self>  $query */
    protected function scopeChronological(Builder $query): void
    {
        $query->orderByDesc('happened_at')->orderByDesc('created_at');
    }

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'happened_at' => 'date',
            'published_at' => 'datetime',
        ];
    }
}
```

`highlights()` cai nas três primeiras fotos quando ninguém marcou destaque, para o card nunca sair vazio.

Factory:

```php
final class AlbumFactory extends Factory
{
    protected $model = Album::class;

    public function definition(): array
    {
        $title = fake()->unique()->words(3, asText: true);

        return [
            'event_id' => null,
            'slug' => Str::slug($title),
            'title' => Str::title($title),
            'location' => fake()->optional()->city(),
            'happened_at' => fake()->dateTimeBetween('-2 years', 'now'),
            'description' => fake()->optional()->sentence(),
            'published_at' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (): array => ['published_at' => now()->subMinute()]);
    }

    public function forEvent(Event $event): static
    {
        return $this->state(fn (): array => ['event_id' => $event->id]);
    }

    /** Exige Storage::fake('public') no teste. */
    public function withPhotos(int $count = 3): static
    {
        return $this->afterCreating(static function (Album $album) use ($count): void {
            foreach (range(1, $count) as $index) {
                $album->addMedia(UploadedFile::fake()->image("foto-{$index}.jpg", 1_600, 1_200))
                    ->toMediaCollection(Album::PHOTOS);
            }
        });
    }
}
```

**Expected behavior.**

```gherkin
Feature: Álbum
    Scenario: Conversões são registradas na collection de fotos
        Given Storage::fake('public') e fila síncrona
        When um álbum recebe uma foto 1600x1200
        Then a média tem as conversões "thumb" e "large" geradas em webp

    Scenario: Destaques caem nas primeiras fotos
        Given um álbum com 5 fotos e nenhuma marcada como destaque
        Then highlights() devolve as 3 primeiras na ordem do order_column

    Scenario: Destaques marcados vencem
        Given um álbum com 5 fotos e a 4ª e a 5ª marcadas como destaque
        Then highlights() devolve exatamente a 4ª e a 5ª

    Scenario: Escopo published ignora data futura
        Given um álbum com published_at amanhã
        Then Album::published() não o inclui

    Scenario: Foto rejeitada por mime
        When um GIF é adicionado à collection "photos"
        Then o media library lança FileUnacceptableForCollection
```

Testes em `app-modules/events/tests/Feature/Gallery/AlbumTest.php`, nomeados em inglês `test('when ..., then ...')` como o resto do módulo.

### Passo 1.5: Glossário e regras de dependência

- [ ] Acrescentar ao `app-modules/events/CONTEXT.md` (tabela Glossary) as linhas **Album**, **Photo**, **Highlight**.
- [ ] Acrescentar em "Module Boundaries": `Portal → Events` lê `Album` publicado; `Panel Admin → Events` escreve álbum e curadoria.
- [ ] Remover "Timeline / activity feed" não; acrescentar em Out of Scope: "Home carousel de destaques (issue #504, fase 2)".
- [ ] Em `CONTEXT-MAP.md`, atualizar a descrição da linha Events ("… + photo albums per event") e a regra de dependência: "**Events** … `portal` renders the public gallery (`/galeria`) and `panel-admin` owns album CRUD; both depend on `events`, never the reverse."
- [ ] Escrever `app-modules/events/docs/adr/0009-galeria-como-subdominio-de-events.md` (formato de `0004-…`): contexto (issue #504), alternativas (módulo `gallery` novo; `Event` HasMedia), decisão (sub-domínio; álbum próprio com `event_id` opcional; borda HTTP no portal como ADR marketing/0004), consequências (portal ganha dependência de `events`; álbum sem evento é permitido; Home fica para outra entrega).

Glossário sugerido:

| Term          | Definition                                                                                          | Not to be confused with                                  |
| ------------- | --------------------------------------------------------------------------------------------------- | -------------------------------------------------------- |
| **Album**     | A curated set of photos from one community moment. Has title, location, date and an optional `Event`. Visible on the portal only when published and non-empty. | "Gallery" — the public page that lists albums.          |
| **Photo**     | One media item in an album's `photos` collection. Carries caption and highlight flag as custom properties, plus an order. | Profile avatar/cover, which are single-file collections. |
| **Highlight** | A photo flagged by staff to represent the album on its card. Falls back to the first three photos when none is flagged. | Album cover — the cover is simply the first highlight.  |

---

## Fase 2 — Admin (`app-modules/panel-admin`)

### Passo 2.1: `AlbumResource`

- [ ] Criar `app-modules/panel-admin/src/Filament/Resources/Albums/AlbumResource.php`.
- [ ] Criar `Pages/ListAlbums.php`, `Pages/CreateAlbum.php`, `Pages/EditAlbum.php`.
- [ ] Criar `app-modules/panel-admin/lang/{en,pt_BR}/albums.php`.

**Context.** Cópia estrutural de `EventResource`. Labels via métodos `getNavigationLabel()/getModelLabel()/getPluralModelLabel()` lendo `__('panel-admin::albums.*')`.

```
Resource: AlbumResource
  Location: He4rt\PanelAdmin\Filament\Resources\Albums\AlbumResource
  Docs: https://filamentphp.com/docs/5.x/resources/overview
  Model: He4rt\Events\Gallery\Models\Album
  Slug: albums
  RecordTitleAttribute: title
  Navigation:
    Icon: Filament\Support\Icons\Heroicon::OutlinedPhoto
    Group: NavGroup::Content (montado à mão no provider, ver Passo 2.5)
  Pages:
    index  → ListAlbums::route('/')
    create → CreateAlbum::route('/create')
    edit   → EditAlbum::route('/{record}/edit')
  Relations:
    PhotosRelationManager
  Authorization: todo usuário que acessa o painel admin (config he4rt.admins via User::canAccessPanel). Sem policy, igual aos outros resources.
```

`lang/pt_BR/albums.php`:

```php
return [
    'label' => 'Álbum',
    'plural' => 'Álbuns de fotos',
    'columns' => [
        'title' => 'Título', 'slug' => 'Slug', 'event' => 'Evento', 'location' => 'Local',
        'happened_at' => 'Aconteceu em', 'description' => 'Descrição', 'published_at' => 'Publicado em',
        'photos' => 'Fotos', 'photos_count' => 'Fotos', 'created_at' => 'Criado em',
    ],
    'form' => [
        'helpers' => [
            'event' => 'Opcional. Escolher um evento preenche local e data, se estiverem vazios.',
            'published_at' => 'Vazio mantém o álbum como rascunho, fora do portal.',
            'photos' => 'Até :max_files fotos por vez, :max_mb MB cada. JPG, PNG ou WEBP.',
        ],
    ],
    'filters' => ['published' => 'Publicados', 'drafts' => 'Rascunhos'],
    'photos' => [
        'title' => 'Fotos',
        'columns' => ['preview' => 'Prévia', 'highlight' => 'Destaque', 'caption' => 'Legenda', 'size' => 'Tamanho', 'uploaded_at' => 'Enviada em'],
        'empty' => 'Envie fotos pelo campo "Fotos" do formulário acima.',
    ],
];
```

`lang/en/albums.php` com as mesmas chaves em inglês (`plural` = `Photo albums`).

### Passo 2.2: `Schemas/AlbumForm.php`

- [ ] Criar `app-modules/panel-admin/src/Filament/Resources/Albums/Schemas/AlbumForm.php`.

**Context.** Formulário de 2 colunas como `EventForm`. O campo de fotos é o primeiro `SpatieMediaLibraryFileUpload` do repo. Regras herdadas do commit `bbd0d2ec`: não usar `imageAspectRatio()`, teto anunciado = `UploadLimit::kilobytes()`. Como não há editor de recorte, `automaticallyResizeImagesToWidth/Height` ficam nulos e a conversão faz o enquadramento.

```
Form:
  Columns: 2
  Imports:
    Filament\Schemas\Components\Utilities\Get
    Filament\Schemas\Components\Utilities\Set
    App\Support\UploadLimit
    He4rt\Events\Gallery\Enums\PhotoConversion
    He4rt\Events\Gallery\Models\Album
    He4rt\Events\Event\Models\Event

  Field: title
    Component: Filament\Forms\Components\TextInput
    Docs: https://filamentphp.com/docs/5.x/forms/text-input
    Validation: required, max:200
    Config: ->live(onBlur: true), ->afterStateUpdated(fn (Set $set, Get $get, ?string $state) => blank($get('slug')) ? $set('slug', Str::slug($state ?? '')) : null), ->columnSpanFull()

  Field: slug
    Component: Filament\Forms\Components\TextInput
    Docs: https://filamentphp.com/docs/5.x/forms/text-input
    Validation: required, max:120, alpha_dash, unique:events_albums,slug (ignora o registro atual)
    Config: ->unique(table: Album::class, column: 'slug', ignoreRecord: true), ->alphaDash()

  Field: event_id
    Component: Filament\Forms\Components\Select
    Docs: https://filamentphp.com/docs/5.x/forms/select
    Validation: nullable, exists:events,id
    Config: ->relationship('event', 'title'), ->searchable(), ->preload(), ->nullable(), ->live(),
            ->afterStateUpdated(function (?string $state, Get $get, Set $set): void {
                $event = $state !== null ? Event::query()->find($state) : null;
                if (! $event instanceof Event) { return; }
                if (blank($get('location'))) { $set('location', $event->location); }
                if (blank($get('happened_at'))) { $set('happened_at', $event->starts_at->toDateString()); }
            }),
            ->helperText(__('panel-admin::albums.form.helpers.event'))

  Field: happened_at
    Component: Filament\Forms\Components\DatePicker
    Docs: https://filamentphp.com/docs/5.x/forms/date-time-picker
    Validation: required, date, before_or_equal:today
    Config: ->maxDate(now()), ->native(false), ->displayFormat('d/m/Y')

  Field: location
    Component: Filament\Forms\Components\TextInput
    Validation: nullable, max:255

  Field: description
    Component: Filament\Forms\Components\Textarea
    Docs: https://filamentphp.com/docs/5.x/forms/textarea
    Validation: nullable, max:2000
    Config: ->rows(3), ->columnSpanFull()

  Field: published_at
    Component: Filament\Forms\Components\DateTimePicker
    Validation: nullable, date
    Config: ->nullable(), ->seconds(false), ->timezone(config('app.display_timezone')), ->helperText(__('panel-admin::albums.form.helpers.published_at'))

  Field: photos
    Component: Filament\Forms\Components\SpatieMediaLibraryFileUpload
    Docs: https://filamentphp.com/docs/5.x/forms/file-upload  (métodos do plugin: collection, conversion, customProperties, responsiveImages)
    Validation: nullable, image, mimetypes:image/jpeg,image/png,image/webp, max:{UploadLimit::kilobytes(PhotoConversion::maxKilobytes())}
    Config: ->collection(Album::PHOTOS), ->conversion(PhotoConversion::Thumb->value), ->multiple(), ->reorderable(), ->appendFiles(),
            ->image(), ->acceptedFileTypes(PhotoConversion::mimeTypes()), ->maxSize(UploadLimit::kilobytes(PhotoConversion::maxKilobytes())),
            ->maxFiles(100), ->panelLayout('grid'), ->imageAspectRatio(ratio: null), ->automaticallyCropImagesToAspectRatio(condition: false),
            ->automaticallyResizeImagesToWidth(width: null), ->automaticallyResizeImagesToHeight(height: null),
            ->helperText(__('panel-admin::albums.form.helpers.photos', ['max_files' => 100, 'max_mb' => round(UploadLimit::kilobytes(PhotoConversion::maxKilobytes()) / 1_024, 1)])),
            ->columnSpanFull()
```

O plugin grava a ordem via `Media::setNewOrder()` quando `reorderable()` está ativo (`SpatieMediaLibraryFileUpload::reorderUploadedFilesUsing`). `conversion('thumb')` faz o uploader mostrar a miniatura webp em vez do original de 5 MB; enquanto a fila não roda o Filament cai no original sozinho.

**Expected behavior.**

```gherkin
Feature: Formulário de álbum
    Scenario: Slug sugerido a partir do título
        Given o campo slug vazio
        When o admin digita "Pub #2 Curitiba" e sai do campo título
        Then slug recebe "pub-2-curitiba"

    Scenario: Slug preenchido não é sobrescrito
        Given slug "meu-slug"
        When o título muda
        Then slug continua "meu-slug"

    Scenario: Evento preenche local e data
        Given local e data vazios
        When o admin escolhe o evento "Pub #2" (local "Curitiba", starts_at 2025-08-15)
        Then local vira "Curitiba" e happened_at vira 2025-08-15

    Scenario: Upload em lote
        When o admin envia 3 JPGs válidos e salva
        Then o álbum tem 3 médias na collection "photos" na ordem enviada

    Scenario: Arquivo fora do mime
        When o admin envia um GIF
        Then o formulário reprova o campo photos com erro de mimetypes

    Scenario: Validação (dataset)
        - title: required, max:200
        - slug: required, max:120, alpha_dash, unique
        - happened_at: required, before_or_equal:today
        - event_id: exists
```

### Passo 2.3: `Tables/AlbumsTable.php`

- [ ] Criar `app-modules/panel-admin/src/Filament/Resources/Albums/Tables/AlbumsTable.php` com `public static function table(Table $table): Table` (mesma assinatura de `EventsTable`).

```
Table:
  DefaultSort: happened_at desc
  Column: photos
    Component: Filament\Tables\Columns\SpatieMediaLibraryImageColumn
    Docs: https://filamentphp.com/docs/5.x/tables/columns/image
    Config: ->collection(Album::PHOTOS), ->conversion(PhotoConversion::Thumb->value), ->stacked(), ->limit(3), ->limitedRemainingText(), ->label(__('panel-admin::albums.columns.photos'))
  Column: title
    Component: Filament\Tables\Columns\TextColumn
    Config: ->searchable(), ->sortable(), ->description(fn (Album $record): ?string => $record->location)
  Column: event.title
    Component: Filament\Tables\Columns\TextColumn
    Config: ->placeholder('—'), ->toggleable()
  Column: happened_at
    Component: Filament\Tables\Columns\TextColumn
    Config: ->date('d/m/Y'), ->sortable()
  Column: photos_count
    Component: Filament\Tables\Columns\TextColumn
    Config: ->counts('photos'), ->badge(), ->sortable()
  Column: published_at
    Component: Filament\Tables\Columns\TextColumn
    Config: ->dateTime('d/m/Y H:i'), ->timezone(config('app.display_timezone')), ->placeholder(__('panel-admin::albums.filters.drafts')), ->sortable()
  Column: created_at
    Component: Filament\Tables\Columns\TextColumn
    Config: ->dateTime(), ->sortable(), ->toggleable(isToggledHiddenByDefault: true)

  Filter: published
    Component: Filament\Tables\Filters\TernaryFilter
    Docs: https://filamentphp.com/docs/5.x/tables/filters/ternary
    Config: ->nullable(), ->attribute('published_at'), ->trueLabel(__('panel-admin::albums.filters.published')), ->falseLabel(__('panel-admin::albums.filters.drafts'))

  Actions: EditAction, DeleteAction (linha); BulkActionGroup[DeleteBulkAction] (toolbar)
```

A memória do projeto avisa: o painel usa `PaginationMode::Cursor` global, e ordenar por agregado (`photos_count`) quebra a página 2. Se `photos_count->sortable()` estourar, remover o `sortable()` desse campo em vez de trocar o modo de paginação.

### Passo 2.4: `RelationManagers/PhotosRelationManager.php`

- [ ] Criar `app-modules/panel-admin/src/Filament/Resources/Albums/RelationManagers/PhotosRelationManager.php`.

**Context.** O uploader não edita metadado por arquivo. A curadoria (destaque, legenda, ordem fina) fica numa tabela sobre a relação `media` do álbum, filtrada na collection `photos`. Toda escrita passa por `CuratePhoto`.

```
RelationManager: PhotosRelationManager
  Location: He4rt\PanelAdmin\Filament\Resources\Albums\RelationManagers\PhotosRelationManager
  Docs: https://filamentphp.com/docs/5.x/resources/managing-relationships
  Relationship: media (MorphMany de Spatie) — propriedade `$relationship = 'media'`
  Title: __('panel-admin::albums.photos.title')
  Can create: no (upload é pelo formulário)
  Can edit: inline (colunas editáveis)
  Can delete: yes (DeleteAction; o Media apaga o arquivo e as conversões)
  Can reorder: yes

  Table:
    ModifyQuery: fn (Builder $query) => $query->where('collection_name', Album::PHOTOS)
    Reorderable: ->reorderable('order_column'), ->defaultSort('order_column')
    Paginated: false (um álbum tem dezenas de fotos, não milhares)
    EmptyState: heading __('panel-admin::albums.photos.empty')

    Column: preview
      Component: Filament\Tables\Columns\ImageColumn
      Config: ->state(fn (Media $record): string => $record->hasGeneratedConversion(PhotoConversion::Thumb->value) ? $record->getUrl(PhotoConversion::Thumb->value) : $record->getUrl()), ->imageHeight(64), ->square()
    Column: highlight
      Component: Filament\Tables\Columns\ToggleColumn
      Docs: https://filamentphp.com/docs/5.x/tables/columns/toggle
      Config: ->state(fn (Media $record): bool => Photo::fromMedia($record)->highlight),
              ->updateStateUsing(fn (Media $record, bool $state) => resolve(CuratePhoto::class)->handle($record, new CuratePhotoData(caption: Photo::fromMedia($record)->caption, highlight: $state)))
    Column: caption
      Component: Filament\Tables\Columns\TextInputColumn
      Docs: https://filamentphp.com/docs/5.x/tables/columns/text-input
      Config: ->state(fn (Media $record): ?string => Photo::fromMedia($record)->caption), ->rules(['nullable', 'max:140']),
              ->updateStateUsing(fn (Media $record, ?string $state) => resolve(CuratePhoto::class)->handle($record, new CuratePhotoData(caption: $state, highlight: Photo::fromMedia($record)->highlight)))
    Column: size
      Component: Filament\Tables\Columns\TextColumn
      Config: ->state(fn (Media $record): string => $record->humanReadableSize)
    Column: created_at
      Component: Filament\Tables\Columns\TextColumn
      Config: ->dateTime('d/m H:i'), ->timezone(config('app.display_timezone'))

  Actions: DeleteAction (linha), BulkActionGroup[DeleteBulkAction]
```

Registrar em `AlbumResource::getRelations()`.

**Expected behavior.**

```gherkin
Feature: Curadoria de fotos no admin
    Scenario: Só fotos da collection aparecem
        Given um álbum com 2 fotos
        When o admin abre a aba Fotos
        Then a tabela lista 2 registros

    Scenario: Marcar destaque
        When o admin liga o toggle Destaque da 2ª foto
        Then custom_properties.highlight da média é true

    Scenario: Legenda inline
        When o admin escreve "Lightning talks" na legenda
        Then custom_properties.caption é "Lightning talks"

    Scenario: Reordenar
        When o admin arrasta a 3ª foto para a 1ª posição
        Then order_column reflete a nova ordem e /galeria/{slug} mostra a foto primeiro
```

### Passo 2.5: Navegação e registro do resource

- [ ] `PanelAdminServiceProvider::register()`: adicionar `AlbumResource::class` ao array `->resources([...])`.
- [ ] `PanelAdminServiceProvider::defaultNavigation()`: adicionar `...AlbumResource::getNavigationItems()` ao final do grupo `NavGroup::Content`.
- [ ] Atualizar `app-modules/panel-admin/tests/Feature/NavigationGroupsTest.php`, teste "o grupo Conteúdo…": lista passa a `['Artigos', 'Contribuições', 'Retrospectivas', 'Photo albums']` (locale dos testes é `en`).

```php
// antes
->items([
    ...ContentEntryResource::getNavigationItems(),
    ...InteractionResource::getNavigationItems(),
    ...RetrospectiveResource::getNavigationItems(),
]),
// depois
->items([
    ...ContentEntryResource::getNavigationItems(),
    ...InteractionResource::getNavigationItems(),
    ...RetrospectiveResource::getNavigationItems(),
    ...AlbumResource::getNavigationItems(),
]),
```

### Passo 2.6: Testes do admin

- [ ] Criar `app-modules/panel-admin/tests/Feature/Albums/AlbumResourceTest.php` (nomes em pt_BR, `beforeEach` igual ao de `Contents/ContentEntryResourceTest.php` + `Storage::fake('public')`).

```
AlbumResourceTest:
  Listagem:
    - lista renderiza e mostra álbuns existentes (livewire(ListAlbums)->loadTable()->assertCanSeeTableRecords)
    - filtro "Publicados" esconde rascunhos (filterTable('published', true))
  Criação:
    - cria álbum com 2 fotos via fillForm(['photos' => [UploadedFile::fake()->image(...), ...]]) e persiste na collection "photos"
    - validação (dataset): title required; slug required, alpha_dash, unique; happened_at required
  Edição:
    - salva alteração de título (fillForm + call('save') + assertHasNoFormErrors)
  RelationManager:
    - renderiza com ownerRecord + pageClass EditAlbum
    - toggle de destaque grava custom property (callTableColumnAction / updateTableColumnState('highlight', $media, true))
    - legenda grava custom property
  Navegação:
    - grupo Conteúdo inclui o label do resource (NavigationGroupsTest)
```

---

## Fase 3 — Portal (`app-modules/portal`)

### Passo 3.1: Dependência de módulo

- [ ] `app-modules/portal/composer.json`: adicionar `"he4rt/events": "^1.0.0"` em `require` (estilo caret obrigatório). Rodar `composer dump-autoload`.

### Passo 3.2: Read model `AlbumFeed` e DTOs

- [ ] Criar `app-modules/portal/src/Gallery/AlbumFeed.php`.
- [ ] Criar `app-modules/portal/src/Gallery/DTOs/PhotoView.php`, `AlbumCard.php`, `AlbumDetail.php`.

**Context.** Mesmo desenho de `ArticleFeed`: classe final resolvida do container, mapeia Eloquent para DTOs imutáveis, uma query por caso de uso. A URL de cada foto é decidida aqui, com o fallback de conversão que `HasProfileImages::imageUrl()` já faz.

```php
final readonly class PhotoView
{
    public function __construct(
        public string $thumbUrl,
        public string $largeUrl,
        public ?string $caption,
        public bool $highlight,
    ) {}

    public static function fromMedia(Media $media): self
    {
        $photo = Photo::fromMedia($media);

        return new self(
            thumbUrl: self::url($media, PhotoConversion::Thumb),
            largeUrl: self::url($media, PhotoConversion::Large),
            caption: $photo->caption,
            highlight: $photo->highlight,
        );
    }

    private static function url(Media $media, PhotoConversion $conversion): string
    {
        return $media->hasGeneratedConversion($conversion->value)
            ? $media->getUrl($conversion->value)
            : $media->getUrl();
    }
}
```

```php
final readonly class AlbumCard
{
    /** @param list<PhotoView> $highlights */
    public function __construct(
        public string $slug,
        public string $title,
        public ?string $location,
        public CarbonImmutable $happenedAt,
        public int $photoCount,
        public array $highlights,
    ) {}

    public function remaining(): int { return max(0, $this->photoCount - count($this->highlights)); }

    public function happenedLabel(): string
    {
        return Str::ucfirst($this->happenedAt->translatedFormat('F \d\e Y'));
    }
}
```

`AlbumDetail` = `AlbumCard` + `description`, `eventTitle` (nullable) e `photos: list<PhotoView>` completo.

```php
final class AlbumFeed
{
    /** @return list<AlbumCard> */
    public function albums(): array
    {
        return Album::query()
            ->published()->withPhotos()->chronological()
            ->with('media')->withCount('photos')
            ->get()
            ->map(fn (Album $album): AlbumCard => new AlbumCard(
                slug: $album->slug,
                title: $album->title,
                location: $album->location,
                happenedAt: $album->happened_at->toImmutable(),
                photoCount: (int) $album->photos_count,
                highlights: $album->highlights()->take(Album::CARD_HIGHLIGHTS)
                    ->map(fn (Media $media): PhotoView => PhotoView::fromMedia($media))->values()->all(),
            ))
            ->values()->all();
    }

    public function find(string $slug): ?AlbumDetail
    {
        $album = Album::query()->published()->withPhotos()->where('slug', $slug)->with(['media', 'event'])->first();

        return $album instanceof Album ? AlbumDetail::fromAlbum($album) : null;
    }
}
```

Carregar `media` inteiro (`with('media')`) é o que o `InteractsWithMedia` espera: `getMedia()` lê a relação `media` carregada, não `photos`. Sem isso, cada card dispararia uma query.

### Passo 3.3: Páginas e rotas

- [ ] Criar `app-modules/portal/src/Gallery/GalleryPage.php` e `AlbumPage.php` (Livewire full-page, `#[Layout('portal::components.layouts.app')]`).
- [ ] Registrar rotas no `PortalServiceProvider::boot()`, dentro do grupo `web`, logo após `/artigos`.

```php
Route::get('/galeria', GalleryPage::class)
    ->name('gallery')
    ->withHead(
        title: 'Galeria da comunidade',
        description: 'Fotos dos encontros da He4rt Developers: meetups, workshops, pubs e confraternizações, álbum por álbum.',
    );

Route::get('/galeria/{slug}', AlbumPage::class)
    ->where('slug', '[a-z0-9-]+')
    ->name('gallery.album');
```

`AlbumPage::mount(AlbumFeed $feed, string $slug)`: `$this->album = $feed->find($slug) ?? abort(404)`. O `<head>` desta rota é dinâmico (título do álbum), então é definido no componente com o helper do `laravel/head` em vez de `withHead()` na rota — consultar `App\Support\Seo\SiteHead` para o método usado nas páginas dinâmicas, ou `head()->title(...)` conforme a API instalada.

- [ ] Navbar: adicionar entrada `['label' => 'Galeria', 'href' => route('gallery', absolute: false), 'active' => request()->routeIs('gallery', 'gallery.album')]` após "Redes" em `resources/views/components/navbar.blade.php`.
- [ ] Sitemap: adicionar `'gallery' => ['changefreq' => 'weekly', 'priority' => '0.7']` em `SitemapController::PAGES`. Álbuns individuais entram numa segunda leva (o controller hoje é uma constante estática).

**Expected behavior.**

```gherkin
Feature: Rotas da galeria
    Scenario: Rotas dentro do grupo web
        Then "gallery" e "gallery.album" listam o middleware "web" (PortalRoutesTest dataset)

    Scenario: Álbum publicado com fotos
        Given um álbum publicado com 3 fotos e slug "pub-2"
        When GET /galeria/pub-2
        Then 200 com o título do álbum

    Scenario: Rascunho é 404
        Given um álbum rascunho com slug "secreto"
        When GET /galeria/secreto
        Then 404

    Scenario: Álbum vazio é 404
        Given um álbum publicado sem fotos
        When GET /galeria/{slug}
        Then 404

    Scenario: Navbar
        When GET /
        Then o HTML contém href="/galeria"
```

### Passo 3.4: Views

- [ ] `resources/views/gallery.blade.php` — lista.
- [ ] `resources/views/gallery-album.blade.php` — grid + lightbox.
- [ ] `resources/views/components/gallery/album-card.blade.php`, `components/gallery/lightbox.blade.php`.

**Context.** Cabeçalho com `x-he4rt::headline` (badge "Galeria" com `heroicon-o-photo`, título "O que acontece quando a gente sai do Discord.", descrição do layout aprovado na issue). Cada card é uma `<article>` com link `after:absolute after:inset-0` como `articles/card.blade.php`. Imagens são `<img loading="lazy" decoding="async" class="aspect-[4/3] object-cover">`; não existe componente de imagem no design system e não se cria um agora.

Estado vazio (nenhum álbum publicado), no mesmo tom do de artigos:

```blade
<div class="border-outline-low bg-elevation-01dp flex flex-col items-center gap-3 rounded-lg border border-dashed p-12 text-center">
    <p class="text-text-high text-sm font-semibold">Ainda não há álbuns por aqui.</p>
    <p class="text-text-medium max-w-sm text-xs">As fotos dos próximos encontros aparecem nesta página. Enquanto isso, o Discord está aberto.</p>
</div>
```

Lightbox (Alpine em `@assets`, componente `galleryLightbox(photos)`):

- estado: `open`, `index`; `photos` = `@js($photosPayload)` com `{l: largeUrl, c: caption}`;
- `openAt(i)`, `next()`, `prev()`, `close()`; `x-on:keydown.window.escape`, `.arrow-left`, `.arrow-right`;
- `x-trap.noscroll="open"` para foco e bloqueio de scroll; `aria-modal="true"`, botão fechar com `aria-label`;
- swipe: `x-on:touchstart` / `x-on:touchend` medindo `clientX` (> 40px troca de foto);
- deep link `?foto=N` via `history.replaceState`, lido em `init()` como o `readUrl()` de `articlesFeed`;
- pré-carrega a próxima `large` com `new Image().src`.

Sem CSS novo no Vite: utilitários Tailwind mais um `<style>` local se precisar (padrão de `social-links.blade.php`).

**Expected behavior.**

```gherkin
Feature: Página /galeria
    Scenario: Lista em ordem cronológica inversa
        Given álbuns publicados de Mar/2025 e Ago/2025, ambos com fotos
        When GET /galeria
        Then "Ago 2025" aparece antes de "Mar 2025" no HTML

    Scenario: Card mostra destaques e o restante
        Given um álbum com 24 fotos e 3 destaques
        Then o card mostra 3 thumbs e o texto "+21"

    Scenario: Rascunho não aparece
        Given um álbum rascunho
        Then o título dele não está no HTML de /galeria

    Scenario: Estado vazio
        Given nenhum álbum publicado
        Then /galeria mostra "Ainda não há álbuns por aqui."

Feature: Lightbox
    Scenario: Abrir e navegar
        Given o álbum tem 3 fotos
        When o visitante clica na 2ª foto
        Then o lightbox abre em 2/3 e a URL ganha ?foto=2
        When pressiona →
        Then mostra 3/3
        When pressiona Esc
        Then o lightbox fecha e ?foto some da URL
```

Os cenários de lightbox são verificados no navegador; o teste automatizado cobre a presença do payload e do `x-data="galleryLightbox(` no HTML.

### Passo 3.5: Testes do portal

- [ ] Criar `app-modules/portal/tests/Feature/GalleryPageTest.php` (pt_BR, `withoutVite()`, `Storage::fake('public')`, helper local `publishedAlbum(array $attributes = [], int $photos = 3): Album`).
- [ ] `PortalRoutesTest`: adicionar `'gallery'` e `'gallery.album'` ao dataset.
- [ ] `HomepageTest`: novo `it('leva para a galeria pela navbar')` com `assertSee('href="/galeria"', escape: false)`.
- [ ] `SeoHeadTest`, teste do sitemap: `->toContain('<loc>'.route('gallery').'</loc>')`.

---

## Fase 4 — Qualidade e entrega

- [ ] `vendor/bin/pint --dirty --format agent`
- [ ] `make phpstan` (novo model precisa do PHPDoc completo; `photos_count` como `@property-read`).
- [ ] `make rector`
- [ ] Testes via `lerd`, `--parallel`; suíte completa antes do push com `vendor/bin/pest --parallel --update-shards`.
- [ ] Um commit por fase (`feat(events): …`, `feat(panel-admin): …`, `feat(portal): …`, `docs(events): …`).
- [ ] PR da branch `feat/gallery` para `4.x` com o template do repo; `Closes #504`; evidências: print de `/galeria`, do lightbox e da aba Fotos do admin.

---

## Fora desta entrega

- Carrossel de destaques na Home (decisão de 2026-09-07: Home fica limpa).
- Rota pública de evento; o card só mostra o nome do evento vinculado.
- Álbuns individuais no sitemap.
- Imagens responsivas (`withResponsiveImages()`); `thumb` e `large` bastam para o volume atual.
- Filtro por categoria de momento (palestra, bastidores…): a issue cita, mas `Album` já aceita um enum futuro sem mudar tabela de fotos.
