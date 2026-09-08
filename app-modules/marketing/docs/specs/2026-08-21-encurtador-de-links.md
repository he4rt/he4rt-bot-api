---
type: spec
title: 'Encurtador de links — módulo marketing'
module: marketing
status: proposed
date: 2026-08-21
author: danielhe4rt
---

# Encurtador de links (`marketing`)

## 1. Motivação

Três dores, todas confirmadas na entrevista:

| #   | Dor                                                                                                | O que o encurtador precisa provar                                    |
| --- | -------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------- |
| 1   | **Métricas de campanha** — hoje não dá pra saber se o post do Discord ou o tweet trouxe mais gente | Cada clique vira uma linha rastreável, com canal de origem           |
| 2   | **Destino mutável** — link já postado/impresso não pode mudar de alvo                              | O slug é estável; o destino é editável e o histórico fica registrado |
| 3   | **Link bonito** — URLs do site são longas demais pra colar no Discord/bio/slide                    | `/l/discord-a3f9k` no lugar de `?utm_source=...&utm_medium=...`      |

**Não é** substituir bit.ly por questão de custo/plano — é ter a capacidade em casa com esses três poderes.

## 2. Decisões travadas

| Decisão       | Escolha                                                          | Consequência principal                                                                                                            |
| ------------- | ---------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------- |
| Quem cria     | **Só staff/admin**                                               | UI no `panel-admin`; gate já resolvido por `User::canAccessPanel()` → `isAdmin()`. Sem quota, sem moderação, sem rate limit.      |
| URL           | **`he4rtdevs.com/l/{slug}`**                                     | Zero DNS novo. Prefixo `/l/` evita colisão com `/{provider}` do OAuth e com todo path futuro do portal.                           |
| Slug          | **custom + sufixo de 5 chars** (`discord-a3f9k`)                 | Custom é obrigatório (sempre legível), sufixo é sempre gerado (unicidade sem loop de colisão).                                    |
| Destino       | **Qualquer URL http(s)**                                         | Valida só o esquema. `created_by` gravado para auditoria.                                                                         |
| Redirect      | **302 Found**                                                    | Browser não cacheia → todo clique conta e a troca de destino vale na hora.                                                        |
| Analytics     | **1 linha por clique, sem retenção**                             | Dado cru eterno, incluindo **IP e User-Agent completos**. Ver §8 (LGPD e volume).                                                 |
| Gravação      | **Job na fila** (`database`)                                     | Redirect responde antes do INSERT. Cliques aparecem no painel com segundos de atraso.                                             |
| Bots          | **`matomo/device-detector`**                                     | Dependência nova, aprovada. Classifica bot + device + browser + OS no job.                                                        |
| Geo           | **Header `CF-IPCountry`**                                        | Cloudflare já está na frente (`monicahq/laravel-cloudflare` + `TrustProxies` em `bootstrap/app.php`). País de graça, sem MaxMind. |
| Agrupamento   | **Tags livres**                                                  | Sem entidade `Campaign` no MVP.                                                                                                   |
| UTM           | **Anexado ao destino, opcional por link**                        | O analytics do site de destino também enxerga a origem.                                                                           |
| Ciclo de vida | **`active` + `expires_at` + histórico de destino + soft delete** | Slug nunca é reusado.                                                                                                             |
| Slug morto    | **Página do portal com CTA**, 404                                | Recupera o visitante em vez de dar parede.                                                                                        |
| QR Code       | **Fora do MVP**                                                  | —                                                                                                                                 |
| Entrega       | **1 issue, 1 PR**                                                | Branch `feature/marketing-shortener` → base `4.x`.                                                                                |

## 3. Fronteiras — onde cada peça mora

O guideline de arquitetura é explícito: _presentation modules own UI concerns only; domain logic belongs in domain modules_. Isso divide o encurtador em três lugares.

```
┌───────────────────────────────────────────────────────────────────────┐
│  app-modules/marketing/        ← MÓDULO NOVO (domínio puro, zero UI)  │
│                                                                       │
│  ShortLink/Models      ShortLink · ShortLinkClick · ShortLinkDestination
│  ShortLink/Actions     CreateShortLink · UpdateShortLink              │
│                        ResolveShortLink · RecordClick                 │
│  ShortLink/Jobs        RecordShortLinkClick (ShouldQueue)             │
│  ShortLink/Support     SlugGenerator · ShortLinkCache                 │
│  ShortLink/ValueObjects UtmParameters · TagList                       │
│                                                                       │
│  depende de: identity (created_by) — e de mais nada                   │
└───────────────────────────────────────────────────────────────────────┘
            ▲                                        ▲
            │ resolve + despacha job                 │ CRUD + leitura
            │                                        │
┌───────────┴──────────────────┐      ┌──────────────┴─────────────────┐
│  app-modules/portal/         │      │  app-modules/panel-admin/      │
│  (borda HTTP pública)        │      │  src/Marketing/  (UI de staff) │
│                              │      │                                │
│  Route GET /l/{slug}         │      │  ShortLinkResource (CRUD)      │
│  ShortLinkRedirectController │      │  ViewShortLink (stats)         │
│  view: short-link-unavailable│      │  → entra no MarketingCluster   │
└──────────────────────────────┘      │     que JÁ existe              │
                                      └────────────────────────────────┘
```

**Por que a rota fica no `portal` e não no `marketing`?** — Julgamento meu, e é o único ponto da spec que não saiu direto de uma resposta sua. A página de "link indisponível" precisa do layout, navbar e componentes do portal. Se a rota morasse em `marketing`, o módulo de domínio passaria a renderizar Blade e a depender de um módulo de apresentação — proibido pelas regras de dependência. O portal já registra **toda** rota pública direto no `PortalServiceProvider` (`/`, `/redes`, `/artigos`, `/comunidade/retrospectiva`), então o `/l/{slug}` entra no mesmo lugar, ao lado dos irmãos. Alternativa descartada: rota em `marketing` + `abort(404)` seco — mas você pediu página com CTA.

## 4. Fluxo — staff cria um link

```
 STAFF                                    SISTEMA
  │                                          │
  │  👆 abre Marketing → Links curtos        │
  │ ───────────────────────────────────────► │
  │                                          │  ShortLinkResource: action=list
  │                                          │  gate: User::canAccessPanel('admin')
  │                                          │
  │     ┌──────────────────────────────────┐ │
  │     │ Slug          Destino    Cliques │ │
  │     │ discord-a3f9k discord.gg   1.284 │ │
  │     │ hacktober-9mz2 github.com/…   47 │ │
  │     └──────────────────────────────────┘ │
  │ ◄────────────────────────────────────────│
  │                                          │
  │  👆 "Novo link"                          │
  │ ───────────────────────────────────────► │
  │                                          │
  │     ┌──────────────────────────────────┐ │
  │     │ Apelido*     [ discord         ] │ │
  │     │ Destino*     [ https://disco…  ] │ │
  │     │ Tags         [ comunidade ✕ ]    │ │
  │     │ Expira em    [ ─               ] │ │
  │     │ ── UTM (opcional) ──             │ │
  │     │ source [discord] medium [post]   │ │
  │     └──────────────────────────────────┘ │
  │                                          │
  │  👆 "Criar"                              │
  │ ───────────────────────────────────────► │
  │                                          │  CreateShortLink::execute(NewShortLinkData)
  │                                          │  SlugGenerator: 'discord' + '-' + 'a3f9k'
  │                                          │  validação: esquema http(s) ✓
  │                                          │  INSERT marketing_short_links
  │                                          │  INSERT marketing_short_link_destinations
  │                                          │         (valid_from=now, valid_until=null)
  │                                          │  ShortLinkCache::forget('discord-a3f9k')
  │                                          │
  │     ✓ "Link criado"                      │
  │       he4rtdevs.com/l/discord-a3f9k      │
  │       ┌────────────────┐                 │
  │       │ 📋 Copiar link │                 │
  │       └────────────────┘                 │
  │ ◄────────────────────────────────────────│
  │                                          │
  │  ⚙️ (semanas depois) edita o destino     │
  │     pro convite novo do Discord          │
  │ ───────────────────────────────────────► │
  │                                          │  UpdateShortLink: destino mudou
  │                                          │  UPDATE destination anterior
  │                                          │         SET valid_until = now
  │                                          │  INSERT nova linha de destino
  │                                          │  ShortLinkCache::forget(slug)
  │                                          │
  │     ✓ "Destino atualizado — o link       │
  │        curto continua o mesmo"           │
  │ ◄────────────────────────────────────────│
```

## 5. Fluxo — alguém clica

```
 VISITANTE                                SISTEMA
  │                                          │
  │  👆 clica he4rtdevs.com/l/discord-a3f9k  │
  │     (postado no Twitter)                 │
  │ ───────────────────────────────────────► │
  │                                          │  ShortLinkRedirectController
  │                                          │  ShortLinkCache::get('discord-a3f9k')
  │                                          │    → HIT (Redis) · ~1ms · sem SQL
  │                                          │  status: Active ✓ (expires_at=null)
  │                                          │  UtmParameters::appendTo(destino)
  │                                          │  dispatch(RecordShortLinkClick)  ⟶ fila
  │                                          │
  │     302 Found                            │
  │     Location: https://discord.gg/he4rt   │
  │       ?utm_source=discord&utm_medium=post│
  │ ◄────────────────────────────────────────│
  │                                          │
  │  📱 já está no Discord (~5ms total)      │
  │                                          │
  │ - - - - - assíncrono - - - - - - - - - - │
  │                                          │  RecordShortLinkClick (worker)
  │                                          │  DeviceDetector::parse(user_agent)
  │                                          │    → is_bot=false, mobile, iOS, Safari
  │                                          │  country ← header CF-IPCountry = 'BR'
  │                                          │  INSERT marketing_short_link_clicks
  │                                          │    (ip cru, UA cru, referer, utm…)
  │                                          │  increment clicks_count
  │                                          │  increment human_clicks_count
```

### Caminho triste

```
 VISITANTE                                SISTEMA
  │                                          │
  │  👆 clica /l/cfp-2025-x8k1 (venceu)      │
  │ ───────────────────────────────────────► │
  │                                          │  cache HIT · expires_at < now
  │                                          │  status: Expired
  │                                          │  (nenhum job despachado)
  │                                          │
  │     404 + página do portal               │
  │     ┌──────────────────────────────────┐ │
  │     │      Esse link não está mais     │ │
  │     │           disponível             │ │
  │     │                                  │ │
  │     │  ┌────────────┐ ┌──────────────┐ │ │
  │     │  │ Ir pra home│ │ Entrar no    │ │ │
  │     │  │            │ │ Discord      │ │ │
  │     │  └────────────┘ └──────────────┘ │ │
  │     └──────────────────────────────────┘ │
  │ ◄────────────────────────────────────────│
```

O mesmo desfecho para: slug inexistente, link `active=false` e link soft-deletado. Um slug desconhecido também grava um _negative cache_ de 60s, para que varredura automatizada não vire carga no Postgres.

## 6. Modelo de dados

```
 marketing_short_links                  marketing_short_link_destinations
┌────────────────────────────┐         ┌────────────────────────────────┐
│ id                uuid PK  │────1:N─▶│ id                  uuid PK    │
│ slug              str UQ   │         │ short_link_id       uuid FK    │
│ base_slug         str IDX  │         │ destination_url     text       │
│ destination_url   text     │         │ utm                 jsonb VO   │
│ utm               jsonb VO │         │ changed_by          uuid FK?   │
│ tags              jsonb VO │         │ valid_from          tstz       │
│ active            bool     │         │ valid_until         tstz?      │◀ null = vigente
│ expires_at        tstz?    │         │ created_at          tstz       │
│ clicks_count      int      │         └────────────────────────────────┘
│ human_clicks_count int     │
│ created_by        uuid FK? │          marketing_short_link_clicks
│ created_at        tstz     │         ┌────────────────────────────────┐
│ updated_at        tstz     │────1:N─▶│ id             bigIncrements PK│
│ deleted_at        tstz?    │         │ short_link_id  uuid FK  IDX    │
└────────────────────────────┘         │ clicked_at     tstz     IDX    │
                                       │ ip_address     str(45)     ⚠︎  │
  ⚠︎ = dado pessoal (LGPD, §8)          │ user_agent     text        ⚠︎  │
                                       │ referer        text?           │
                                       │ country_code   char(2)?        │
                                       │ device_type    str?            │
                                       │ browser        str?            │
                                       │ os             str?            │
                                       │ is_bot         bool     IDX    │
                                       │ bot_name       str?            │
                                       │ utm_source     str?            │◀ o que veio NA
                                       │ utm_medium     str?            │  URL curta
                                       │ utm_campaign   str?            │
                                       │ user_id        uuid FK? ⚠︎     │
                                       └────────────────────────────────┘
```

Índices: `short_links(slug)` UNIQUE · `short_links(base_slug)` · `clicks(short_link_id, clicked_at)` · `clicks(is_bot)` · `clicks(country_code)` · `destinations(short_link_id, valid_from)`.

Todas as colunas de data usam a variante `Tz` (`timestampTz`, `timestampsTz`, `softDeletesTz`), conforme o guideline de timezone.

**`clicks.id` é `bigIncrements`, não UUID** — divergência consciente do padrão do projeto. É uma tabela append-only de alto volume; UUID v4 em índice B-tree grande fragmenta e infla. Registrado aqui para não parecer descuido em review.

## 7. Passos de implementação

---

### Passo 0 — Destravar o autoload

**Context.** Hoje `php artisan` não roda neste checkout: o `vendor/composer/autoload_*` ainda referencia `he4rt/contents`, um módulo que não existe mais no disco. Qualquer comando Artisan morre com `include(.../he4rt/contents/src/ContentsServiceProvider.php): Failed to open stream`. Como todos os passos seguintes usam `make:module` e `make:migration`, isso precisa sair da frente primeiro.

```bash
composer dump-autoload
php artisan route:list --except-vendor | head   # confirma que voltou
```

**Expected behavior**

- **Dado** um checkout com autoload stale, **quando** eu rodo `composer dump-autoload`, **então** `php artisan` executa sem `ClassLoader` exception.
- **Dado** que o comando ainda falha depois do dump, **então** o problema é outro (módulo removido sem tirar do `composer.json` da raiz) e deve ser resolvido antes de continuar — não contornado.

---

### Passo 1 — Scaffold do módulo `marketing`

**Context.** Não existe módulo dono desta capability. `he4rt` é design system (só ServiceProvider), `community` é Feedback+Meeting, `portal` é apresentação. `marketing` nasce como domínio puro — e o nome já tem eco no painel: `panel-admin/src/Marketing/` (`MarketingCluster`, dashboards de Discord e Localização, traduções em `lang/{en,pt_BR}/marketing.php`). O módulo de domínio e o cluster de UI passam a ser as duas metades do mesmo assunto.

```bash
php artisan make:module marketing
gh label create "mod:marketing" --color "c2e0c6" --description "Encurtador de links, campanhas e analytics de divulgação"
```

O guideline de arquitetura exige, no mesmo change: a linha na tabela de módulos de `.ai/02-triage-labels` **e** a label viva no GitHub. Também exige o estilo `^1.0.0` nas dependências intra-repo.

```jsonc
// app-modules/marketing/composer.json
{
    "name": "he4rt/marketing",
    "require": {
        "he4rt/identity": "^1.0.0",
        "matomo/device-detector": "^6.4",
    },
    "autoload": {
        "psr-4": {
            "He4rt\\Marketing\\": "src/",
            "He4rt\\Marketing\\Database\\Factories\\": "database/factories/",
            "He4rt\\Marketing\\Database\\Seeders\\": "database/seeders/",
        },
    },
}
```

```jsonc
// app-modules/portal/composer.json — hoje "require": {}
{
    "require": {
        "he4rt/marketing": "^1.0.0",
    },
}
```

Atualizar também `CONTEXT-MAP.md` (linha na tabela de contextos + regra de dependência) e criar `app-modules/marketing/CONTEXT.md` com o glossário: _link curto_, _slug_, _destino_, _clique_, _tag_.

**Expected behavior**

- **Dado** o módulo criado, **quando** rodo `php artisan module:list`, **então** `marketing` aparece com o `MarketingServiceProvider` registrado.
- **Dado** que o módulo declara `he4rt/identity`, **então** a constraint é `^1.0.0` — nunca `>=1`, `^1.0` ou `*`.
- **Dado** que a label `mod:marketing` foi criada no GitHub, **então** a tabela em `.ai/02-triage-labels` tem a linha correspondente. Guideline e labels vivas não podem divergir.

---

### Passo 2 — Value Objects e casts tipados

**Context.** O guideline `06-typed-json-casts` **proíbe** `'array'`, `'json'`, `'object'` e `'collection'` em `casts()`, e o teste `tests/Unit/NoLooseArrayCastsTest.php` reflete sobre todo model concreto de `app-modules/*/src` e falha mecanicamente. As duas colunas jsonb (`utm`, `tags`) precisam de VO próprio antes dos models existirem — senão a build quebra no primeiro `php artisan test`.

O `UtmParameters` não é um saco de chaves: ele é o dono da regra de precedência quando o destino é montado.

```php
// ANTES — o que o guideline proíbe
protected function casts(): array
{
    return [
        'utm' => 'array',
        'tags' => 'array',
    ];
}

$url .= '?utm_source=' . $link->utm['utm_source'];   // mixed · magic string · quebra se já tem query
```

```php
// DEPOIS — VO tipado, dono da regra
protected function casts(): array
{
    return [
        'utm' => AsUtmParameters::class,
        'tags' => AsTagList::class,
    ];
}

$url = $link->utm->appendTo($link->destination_url, $request->query());
```

```php
namespace He4rt\Marketing\ShortLink\ValueObjects;

final readonly class UtmParameters
{
    public function __construct(
        public ?string $source = null,
        public ?string $medium = null,
        public ?string $campaign = null,
        public ?string $term = null,
        public ?string $content = null,
    ) {}

    /**
     * Monta a URL final. Precedência, do mais forte pro mais fraco:
     * 1. o que já está na URL de destino cadastrada  (staff escreveu de propósito)
     * 2. o que veio na query da URL curta            (quem clicou trouxe)
     * 3. o UTM configurado no link                   (preenche só o que faltou)
     *
     * @param  array<string, string>  $incoming
     */
    public function appendTo(string $destination, array $incoming = []): string { /* … */ }

    /** @param array<array-key, mixed> $data */
    public static function fromArray(array $data): self { /* … */ }

    /** @return array<string, string|null> */
    public function toArray(): array { /* … */ }
}
```

`TagList` é análogo: lista de strings normalizadas (lowercase, sem duplicata, sem vazio), com `contains()`, `add()`, `remove()` imutáveis.

O cast declara o generic largo no setter, como o guideline pede, para que atribuição por array continue um ramo tipado e alcançável:

```php
/** @implements CastsAttributes<UtmParameters, UtmParameters|array<array-key, mixed>> */
final class AsUtmParameters implements CastsAttributes { /* match (true) */ }
```

**Expected behavior**

- **Dado** um destino `https://exemplo.com/pagina` e UTM `source=discord`, **quando** chamo `appendTo()`, **então** recebo `https://exemplo.com/pagina?utm_source=discord`.
- **Dado** um destino que **já tem** `?utm_source=newsletter`, **quando** o link tem `utm_source=discord` configurado, **então** o valor do destino prevalece — a URL final continua com `newsletter`.
- **Dado** um clique em `/l/x?utm_source=twitter` num link com `utm_source=discord` configurado, **então** o destino recebe `utm_source=twitter` (quem clicou trouxe intenção mais específica que a configuração).
- **Dado** um destino com fragmento (`#secao`), **quando** UTM é anexado, **então** o fragmento permanece no fim: `...?utm_source=x#secao`.
- **Dado** um link sem UTM nenhum e sem query no clique, **então** o destino sai byte-a-byte igual ao cadastrado.
- **Dado** `AsUtmParameters` e `AsTagList` registrados, **quando** rodo `NoLooseArrayCastsTest`, **então** ele passa sem precisar de allowlist nova.

---

### Passo 3 — Migrations e models

**Context.** Três tabelas (§6). Todas nascem via Artisan com `--module=marketing` — criar arquivo de migration na mão é proibido pelo guideline `05-timezone-aware-dates`, e sem a flag o arquivo cai no diretório errado e o ServiceProvider do módulo não o carrega.

```bash
php artisan make:migration create_marketing_short_links_table --module=marketing
php artisan make:migration create_marketing_short_link_destinations_table --module=marketing
php artisan make:migration create_marketing_short_link_clicks_table --module=marketing
```

Todo campo de data usa a variante `Tz`:

```php
// ANTES — o que NÃO pode
$table->timestamp('expires_at')->nullable();
$table->timestamps();
$table->softDeletes();
```

```php
// DEPOIS
$table->timestampTz('expires_at')->nullable();
$table->timestampsTz();
$table->softDeletesTz();
```

Cada model declara os atributos de classe obrigatórios e o bloco `@property` completo (guideline `04-model-phpdoc-sync`):

```php
/**
 * @property string $id
 * @property string $slug
 * @property string $base_slug
 * @property string $destination_url
 * @property UtmParameters $utm
 * @property TagList $tags
 * @property bool $active
 * @property Carbon|null $expires_at
 * @property int $clicks_count
 * @property int $human_clicks_count
 * @property string|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[UseFactory(ShortLinkFactory::class)]
#[Table(name: 'marketing_short_links')]
final class ShortLink extends Model
{
    /** @use HasFactory<ShortLinkFactory> */
    use HasFactory;
    use SoftDeletes;
}
```

Enum de status, com os contratos Filament implementados **no mesmo change** (guideline `07-enum-filament-contracts` — retrofit depois significa tocar tudo duas vezes):

```php
enum ShortLinkStatus: string implements HasColor, HasDescription, HasIcon, HasLabel
{
    case Active = 'active';
    case Expired = 'expired';
    case Disabled = 'disabled';

    // Escala NÃO-ordenada (são estados, não níveis) → cores semânticas
    // distintas por caso, sem rampa light→red.
    public function getColor(): string|array
    {
        return match ($this) {
            self::Active => 'success',
            self::Expired => 'warning',
            self::Disabled => 'gray',
        };
    }
    // getLabel(), getDescription(), getIcon(): match sobre TODOS os casos, sem default.
}
```

O status é derivado, não persistido — um accessor no model: `deleted_at` ou `!active` → `Disabled`; `expires_at` no passado → `Expired`; senão `Active`.

**Expected behavior**

- **Dado** as migrations rodadas, **quando** inspeciono `marketing_short_links`, **então** `expires_at`, `created_at`, `updated_at` e `deleted_at` são `timestamptz`, não `timestamp`.
- **Dado** um `ShortLink` com `expires_at` ontem, **quando** leio `$link->status`, **então** recebo `ShortLinkStatus::Expired` mesmo com `active = true`.
- **Dado** um `ShortLink` com `active = false` e `expires_at` no futuro, **então** o status é `Disabled` — desligar manualmente ganha da data.
- **Dado** um link soft-deletado com slug `discord-a3f9k`, **quando** tento criar outro link com o mesmo slug, **então** o índice único rejeita. O slug **nunca** volta a ser reusável.
- **Dado** que adicionei colunas, **então** o bloco `@property` do model cobre todas elas e `vendor/bin/phpstan analyse` passa.
- **Dado** o enum `ShortLinkStatus`, **quando** um caso novo for adicionado no futuro, **então** cada `match` quebra em tempo de análise — porque nenhum tem braço `default`.

---

### Passo 4 — Geração de slug e Actions de escrita

**Context.** O slug é sempre `{apelido-do-staff}-{5 chars base36}`. O apelido garante legibilidade (motivação 3); o sufixo garante unicidade sem loop de verificação de colisão e sem vazar quantos links existem. 5 chars base36 = 60 milhões de combinações **por apelido** — colisão é irrelevante no volume de staff-only, e o índice único é a rede de segurança.

`UpdateShortLink` é onde mora a motivação 2: trocar o destino não é um `UPDATE` cego, é fechar a vigência anterior e abrir uma nova. Sem isso, o gráfico de cliques mente — você vê 1.284 cliques em `/l/discord-a3f9k` sem saber que metade foi pro convite antigo.

```php
// ANTES — update cego, história perdida
$link->update(['destination_url' => $novoDestino]);
```

```php
// DEPOIS — a mudança vira fato datado
final class UpdateShortLink
{
    public function execute(ShortLink $link, ShortLinkChanges $changes): ShortLink
    {
        return DB::transaction(function () use ($link, $changes): ShortLink {
            $destinationChanged = $changes->hasDestinationChange($link);

            $link->fill($changes->toAttributes())->save();

            if ($destinationChanged) {
                $link->destinations()
                    ->whereNull('valid_until')
                    ->update(['valid_until' => now()]);

                $link->destinations()->create([
                    'destination_url' => $link->destination_url,
                    'utm' => $link->utm,
                    'changed_by' => auth()->id(),
                    'valid_from' => now(),
                ]);
            }

            ShortLinkCache::forget($link->slug);

            return $link;
        });
    }
}
```

`SlugGenerator::for(string $apelido): string` → `Str::slug($apelido) . '-' . $sufixo`, com sufixo de `random_int` sobre `0-9a-z`. Validação do destino: só `http` e `https` — `javascript:`, `data:` e `file:` são rejeitados na Action, não só no form.

**Expected behavior**

- **Dado** o apelido `Discord`, **quando** gero o slug, **então** recebo algo como `discord-a3f9k`: lowercase, separador `-`, sufixo de exatamente 5 chars em `[0-9a-z]`.
- **Dado** o apelido `Hacktoberfest 2026!`, **então** o slug é `hacktoberfest-2026-<sufixo>` — acentos e pontuação normalizados.
- **Dado** dois links criados com o mesmo apelido, **então** os slugs diferem pelo sufixo e ambos persistem.
- **Dado** um destino `javascript:alert(1)`, **quando** chamo `CreateShortLink`, **então** uma exceção de validação é lançada e nada é gravado — mesmo que a chamada venha de fora do form do Filament.
- **Dado** um link criado, **então** existe exatamente 1 linha em `destinations` com `valid_until = null`.
- **Dado** que edito **só as tags** de um link, **então** nenhuma linha nova de destino é criada — o histórico registra mudança de destino, não qualquer edição.
- **Dado** que edito o destino, **então** a linha anterior ganha `valid_until = now()` e uma nova nasce com `valid_from = now()`; a soma das vigências não tem buraco nem sobreposição.
- **Dado** que o `UPDATE` do destino falha no meio, **então** a transação reverte e o histórico não fica com duas linhas vigentes.

---

### Passo 5 — Resolução, cache e a rota `/l/{slug}`

**Context.** É o caminho mais quente do sistema: todo clique passa aqui, e um link viral no Discord concentra tráfego em segundos. O cache elimina o SQL do caminho crítico; a invalidação por observer (e não por TTL) é o que faz "troco o destino e vale agora" ser verdade.

Detalhe importante: o cache guarda `expires_at` **cru** e o status é avaliado na leitura. Se o cache guardasse o status já calculado, um link expirando exigiria invalidação agendada — e ninguém ia lembrar disso.

```php
// app-modules/portal/src/PortalServiceProvider.php — junto das outras rotas públicas
Route::get('/l/{slug}', ShortLinkRedirectController::class)
    ->where('slug', '[a-z0-9-]+')
    ->name('short-link.redirect');
```

```php
final class ShortLinkRedirectController
{
    public function __construct(private ResolveShortLink $resolve) {}

    public function __invoke(Request $request, string $slug): RedirectResponse|Response
    {
        $resolution = $this->resolve->execute($slug);

        if (! $resolution->isRedirectable()) {
            // Cobre inexistente, inativo, expirado e soft-deletado — um só desfecho.
            return response()->view('portal::short-link-unavailable', status: 404);
        }

        RecordShortLinkClick::dispatch(
            ClickContext::fromRequest($request, $resolution->id),
        );

        return redirect()->away(
            $resolution->utm->appendTo($resolution->destinationUrl, $request->query()),
            status: 302,
        );
    }
}
```

O observer no `ShortLink` (`saved`, `deleted`, `restored`) chama `ShortLinkCache::forget($link->slug)`. Miss vira sentinela negativa com TTL de 60s, para que varredura de slug não vire carga no Postgres.

**Expected behavior**

- **Dado** um link ativo, **quando** faço GET em `/l/discord-a3f9k`, **então** recebo **302** (não 301) com `Location` apontando pro destino com UTM aplicado.
- **Dado** que edito o destino e clico de novo **imediatamente**, **então** vou pro destino novo — o cache foi invalidado no save, não expirado por tempo.
- **Dado** um link `active = false`, **então** recebo 404 com a página do portal, e **nenhum** job de clique é despachado.
- **Dado** um link com `expires_at` no passado, **então** recebo 404 — sem precisar de nenhuma invalidação de cache ou job agendado.
- **Dado** um slug inexistente, **então** 404, e uma segunda requisição ao mesmo slug dentro de 60s não toca o banco.
- **Dado** um slug com maiúsculas (`/l/Discord-A3F9K`), **então** o constraint `[a-z0-9-]+` não casa e a rota devolve 404 — slugs são canônicos em lowercase.
- **Dado** que o worker da fila está parado, **então** o redirect continua funcionando normalmente; só a contagem atrasa.
- **Dado** que a app está atrás do Cloudflare, **então** `$request->ip()` devolve o IP real do visitante, não o da edge — `TrustProxies` + `monicahq/laravel-cloudflare` já garantem isso em `bootstrap/app.php`.

---

### Passo 6 — Captura do clique (job)

**Context.** Você escolheu gravar tudo cru, para sempre. Toda a parte cara — parse de User-Agent, INSERT, increments — vive fora do request, no job. `matomo/device-detector` é a dependência nova aprovada: em vez de regex artesanal, ela classifica bot, device, browser e OS de forma confiável e mantida.

Bots importam mais do que parece: Discord, WhatsApp, Twitter e Slack batem no link para gerar preview (_unfurl_). Um post pode render 5–10 acessos fantasma antes de qualquer humano clicar. A linha é gravada (você quer o dado cru), mas com `is_bot = true`, e o contador humano é separado — assim a coluna "cliques" da tabela não mente.

```php
final class RecordShortLinkClick implements ShouldQueue
{
    public function __construct(private readonly ClickContext $context) {}

    public function handle(): void
    {
        $detector = new DeviceDetector($this->context->userAgent);
        $detector->parse();

        $isBot = $detector->isBot();

        ShortLinkClick::create([
            'short_link_id' => $this->context->shortLinkId,
            'clicked_at' => $this->context->clickedAt,
            'ip_address' => $this->context->ip,          // cru, por decisão explícita
            'user_agent' => $this->context->userAgent,   // cru
            'referer' => $this->context->referer,
            'country_code' => $this->context->countryCode, // header CF-IPCountry
            'is_bot' => $isBot,
            'bot_name' => $isBot ? $detector->getBot()['name'] ?? null : null,
            'device_type' => $isBot ? null : $detector->getDeviceName(),
            'browser' => $isBot ? null : $detector->getClient('name'),
            'os' => $isBot ? null : $detector->getOs('name'),
            'utm_source' => $this->context->utmSource,
            'utm_medium' => $this->context->utmMedium,
            'utm_campaign' => $this->context->utmCampaign,
            'user_id' => $this->context->userId,
        ]);

        ShortLink::whereKey($this->context->shortLinkId)->increment('clicks_count');

        if (! $isBot) {
            ShortLink::whereKey($this->context->shortLinkId)->increment('human_clicks_count');
        }
    }
}
```

`ClickContext` é um `readonly` DTO montado no controller — nunca o `Request` inteiro, que não serializa bem na fila.

**Expected behavior**

- **Dado** um clique de um iPhone com Safari, **então** a linha grava `is_bot = false`, `device_type = 'smartphone'`, `os = 'iOS'`, `browser = 'Mobile Safari'`, e **ambos** os contadores incrementam.
- **Dado** um acesso com UA `Discordbot/2.0`, **então** a linha grava `is_bot = true`, `bot_name = 'Discordbot'`, `clicks_count` incrementa e `human_clicks_count` **não**.
- **Dado** um clique vindo do Cloudflare com `CF-IPCountry: BR`, **então** `country_code = 'BR'`.
- **Dado** um clique sem o header (ambiente local, sem Cloudflare), **então** `country_code` fica `null` — e nada quebra.
- **Dado** um clique de usuário logado, **então** `user_id` é preenchido; anônimo grava `null`.
- **Dado** um `Referer` de `https://twitter.com/...`, **então** a URL completa é gravada — não só o domínio.
- **Dado** um pico de 500 cliques em 10 segundos, **então** os 500 redirects respondem sem esperar INSERT, e a fila drena depois.
- **Dado** que o job falha (banco fora), **então** ele volta pra fila e é retentado; nenhum clique é perdido silenciosamente e o visitante já foi redirecionado de qualquer forma.

---

### Passo 7 — UI no `panel-admin` (cluster Marketing)

**Context.** `panel-admin/src/Marketing/` já existe com `MarketingCluster.php`, dashboards de Discord e Localização, e traduções em `lang/{en,pt_BR}/marketing.php`. O encurtador entra ali como irmão, não como navegação nova. O gate de acesso já está resolvido — `User::canAccessPanel()` devolve `isAdmin()` em produção; não há sistema de roles no projeto e não vou inventar um.

Guideline de presentation: as Filament Actions envolvem as Domain Actions, nunca reimplementam a regra.

```php
// Estrutura
app-modules/panel-admin/src/Marketing/
├── Resources/ShortLinks/
│   ├── ShortLinkResource.php
│   ├── Schemas/ShortLinkForm.php
│   ├── Tables/ShortLinksTable.php
│   └── Pages/{ListShortLinks,CreateShortLink,EditShortLink,ViewShortLink}.php
└── Resources/ShortLinks/Widgets/
    ├── ClicksOverTimeChart.php
    ├── TopReferersTable.php
    └── DeviceBreakdownChart.php
```

```php
// A page de Create delega — nunca duplica a regra de slug/validação
protected function handleRecordCreation(array $data): Model
{
    return resolve(CreateShortLink::class)->execute(NewShortLinkData::fromForm($data));
}
```

Tabela: `slug` (copiável, com a URL completa), `destination_url` (truncada, linkada), `tags` (badges), `status` (badge vindo do enum), `human_clicks_count`, `created_at`. Filtros: `SelectFilter` por status, filtro por tag, e range de datas.

`ViewShortLink` é onde o dado cru vira resposta: cliques por dia, top referers, top UTM source, quebra por país, quebra por device, e um toggle **incluir bots** (desligado por padrão).

**Expected behavior**

- **Dado** um usuário admin, **quando** abro `Marketing → Links curtos`, **então** vejo a tabela dentro do `MarketingCluster`, ao lado dos dashboards que já existem.
- **Dado** um usuário não-admin em produção, **quando** tento acessar, **então** sou barrado pelo `canAccessPanel` — sem policy nova.
- **Dado** que preencho o form e submeto, **então** `CreateShortLink` é quem grava; o slug com sufixo aparece na notificação de sucesso, pronto pra copiar.
- **Dado** que edito o destino pela `EditShortLink`, **então** `UpdateShortLink` roda e a linha de histórico é criada — a UI não escreve no model direto.
- **Dado** um link com 1.284 cliques dos quais 137 são bots, **então** a coluna da tabela mostra **1.147** (humanos) por padrão.
- **Dado** que abro `ViewShortLink` e ligo "incluir bots", **então** os gráficos recalculam para 1.284 sem recarregar a página.
- **Dado** um link sem nenhum clique, **então** os widgets mostram estado vazio, não erro nem gráfico quebrado.
- **Dado** o tema escuro do painel, **então** nenhum widget usa `bg-white` hardcoded.

---

### Passo 8 — Página pública de link indisponível

**Context.** Um link de evento antigo circulando no Discord não pode virar parede. A view mora no `portal` porque precisa do layout, navbar e componentes que já estão lá — e porque um módulo de domínio não renderiza Blade.

```php
// app-modules/portal/resources/views/short-link-unavailable.blade.php
// Um único desfecho para inexistente / inativo / expirado / deletado:
// não revelar qual dos quatro é evita virar oráculo de enumeração de slug.
```

CTAs: "Ir pra home" (`route('home')`) e "Entrar no Discord" (o próprio link curto oficial do Discord, quando existir).

**Expected behavior**

- **Dado** qualquer slug não-resolvível, **então** a resposta é **404** com a página de marca — nunca 200, para não sujar indexação.
- **Dado** um slug que existe mas está desativado e um que nunca existiu, **então** as duas respostas são **idênticas** — ninguém consegue descobrir quais slugs existem.
- **Dado** que acesso pelo celular, **então** a página é responsiva e os dois CTAs são tocáveis.
- **Dado** o tema escuro, **então** a página respeita — sem `bg-white` fixo.

---

### Passo 9 — Testes e fechamento

**Context.** O guideline é taxativo: toda mudança precisa de teste programático. Os testes ficam dentro do módulo (`app-modules/marketing/tests/`), com os de UI no `panel-admin`.

| Suite                                  | Cobre                                                                                                                                     |
| -------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------- |
| `marketing/tests/Unit/`                | `SlugGenerator` (formato, normalização), `UtmParameters::appendTo` (as 4 regras de precedência), `TagList`, accessor de `ShortLinkStatus` |
| `marketing/tests/Feature/`             | Actions de create/update, histórico de destino, invalidação de cache, job de clique (bot vs humano, país, contadores)                     |
| `portal/tests/Feature/`                | 302 com UTM, 404 nos quatro casos mortos, indistinguibilidade das respostas, negative cache                                               |
| `panel-admin/tests/Feature/Marketing/` | Resource: criar, editar, tabela, filtros, contagem sem bots                                                                               |
| já existente                           | `tests/Unit/NoLooseArrayCastsTest.php` — passa sem allowlist nova                                                                         |

```bash
vendor/bin/pest --parallel --filter=ShortLink
vendor/bin/pint --dirty --format agent
vendor/bin/phpstan analyse
```

**Expected behavior**

- **Dado** a suíte completa, **então** `vendor/bin/pest --parallel` passa e `NoLooseArrayCastsTest` não precisou de entrada nova na allowlist.
- **Dado** os arquivos PHP alterados, **então** `vendor/bin/pint --dirty` não reporta diff e `phpstan` passa sem `ignoreErrors` novo.
- **Dado** a doc do painel, **então** `docs/admin/en/` ganha a página do encurtador sob o grupo correspondente (guideline de knowledge base).

---

## 8. Riscos e pendências

### 8.1 LGPD — a pendência mais séria

Você escolheu, com o risco na mesa, gravar **IP e User-Agent completos, sem retenção**. Isso é decisão sua e a spec a implementa. Mas registro o que ela implica, porque não é opinião:

- **IP é dado pessoal** sob a LGPD. Guardado indefinidamente, exige base legal declarada (provavelmente legítimo interesse, art. 7º IX).
- **Não existe política de privacidade nem termos de uso neste repositório** — verifiquei. Não há nenhum documento onde essa base legal esteja declarada hoje.
- Sem política, falta também o caminho de exclusão a pedido do titular (art. 18).

Isso **não bloqueia** a implementação. Recomendo abrir uma issue separada `type:docs` para a política de privacidade, referenciando esta spec. Se em algum momento você quiser reduzir a exposição sem perder capacidade analítica, o caminho mais barato é trocar `ip_address` por `sha256(ip + salt + dia)` — mantém "visitantes únicos por dia" e deixa de guardar dado pessoal recuperável. Fica registrado como opção, não como mudança de plano.

### 8.2 Volume

Raw eterno, ~400 bytes por linha com IP e UA completos. Ordem de grandeza: **1M de cliques ≈ 400MB** + índices. Não é problema no ano 1. Vira problema se um link estourar. Mitigação futura (fora do MVP): partição declarativa por mês em `clicked_at` no Postgres — é aditiva e não exige mudar o código de escrita.

Referência interna: `messages` já bate 2,3GB neste projeto. O padrão que produziu aquilo é o mesmo padrão aqui.

### 8.3 Outros

| Risco                                                           | Mitigação                                                                                                     |
| --------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------- |
| **Open redirect** — qualquer URL sob nosso domínio              | Staff-only + `created_by` gravado. Se um dia abrir pra membros, blocklist vira obrigatória.                   |
| **Autoload quebrado** hoje (`he4rt/contents` fantasma)          | Passo 0.                                                                                                      |
| **`bigIncrements` na tabela de cliques** diverge do padrão UUID | Consciente e documentado em §6, para não travar em review.                                                    |
| **`CACHE_STORE=array` local**                                   | O cache funciona por request em dev; os testes de invalidação precisam ser explícitos, não confiar no driver. |
| **Dependência nova** (`matomo/device-detector`)                 | Aprovada explicitamente. `~1MB`, LGPL-3.0, mantida pelo Matomo.                                               |
| **PR grande** (módulo novo + 3 tabelas + dep + UI)              | Escolha sua. Descrição do PR seguindo `.github/pull_request_template.md`, com `Plano de Testes` preenchido.   |

## 9. Entrega

- **Issue única**: `feat(marketing): encurtador de links com analytics de clique`
- **Labels**: `type:feat`, `mod:marketing`, `difficulty:hard`, `ready-for-agent`
- **Branch**: `feature/marketing-shortener` → base **`4.x`** (não `main`)
- **PR** seguindo o template do repo: `Contexto`, `Alterações`, `Plano de Testes` (com `make check` / `vendor/bin/pest --parallel`), `Evidências` (screenshot do resource e da página 404), `Issues Relacionadas`.

### Fora do escopo (registrado, não planejado)

QR Code · aliases múltiplos por destino · entidade `Campaign` · self-serve para membros · slash command `/encurtar` no bot · domínio dedicado · página intersticial · rollup agregado.
