---
type: plan
title: 'Aba Pessoas no painel admin'
module: panel-admin
status: proposed
date: 2026-08-22
author: danielhe4rt
---

# Aba Pessoas — plano de implementação

Primeira leva da reestruturação do painel: o grupo **Pessoas**, com quatro telas
(Usuários, Identidades externas, Perfis, Skills) e o enum de grupos de navegação
que as sustenta.

## Contexto

O painel expõe 14 Resources para 55 models de domínio. `users` é referenciada por
19 das 69 foreign keys do schema e **não tem Resource nenhum** — a entidade mais
central do sistema é invisível no admin. `user_profiles`, `skills` e
`profile_skills` também não têm tela.

A sidebar hoje não usa `navigationGroup` em nenhum lugar: `PanelAdminServiceProvider`
monta a navegação à mão com `NavigationBuilder` e `->items()`, deixando
`ExternalIdentityResource` e `EventResource` soltos na raiz.

## Decisões tomadas com o autor

| Decisão                 | Escolha                      | Consequência no plano                            |
| ----------------------- | ---------------------------- | ------------------------------------------------ |
| Escopo                  | As quatro telas da aba       | 4 Resources + 2 RelationManagers                 |
| Punir (banir/suspender) | **Só leitura** no painel     | Nenhuma escrita em `banned_at`/`suspended_until` |
| Autorização             | Binária, sem Policy          | `canAccessPanel()` já barra; sem `UserPolicy`    |
| Navegação               | Só o enum, clusters intactos | Enum novo + ajuste no `NavigationBuilder`        |

## Restrições descobertas no levantamento

**1. `WebModerationAdapter` é o dono de banir e suspender.**
`app-modules/moderation/src/Platform/WebModerationAdapter.php:96,106` escreve
`suspended_until` e `banned_at` a partir de um `ModerationAction`. O painel de
Pessoas **não pode** escrever nessas colunas: criaria punição sem caso, sem
notificação e sem audit log. Elas são exibidas, nunca editadas.

**2. `$navigationGroup` é ignorado quando existe `NavigationBuilder` custom.**
O painel chama `->navigation($this->buildNavigation(...))`. Nesse modo, a
propriedade `$navigationGroup` dos Resources não tem efeito — grupos só aparecem
via `$builder->groups([NavigationGroup::make()->items([...])])`.
Docs: https://filamentphp.com/docs/5.x/navigation/overview#registering-custom-navigation-groups
Por isso o enum é a fonte de label/ícone/ordem, e o builder o consome.

**3. Todo perfil já nasce com o usuário.**
`UserObserver::created()` chama `Profile::ensureExists()`. Logo `ProfileResource`
**não tem Create nem Delete** — criar um segundo perfil viola o índice único em
`user_id`, e apagar quebra a invariante que o resto do sistema assume.

**4. Todos os enums envolvidos já implementam os contratos Filament.**
`SeniorityLevel`, `StartAvailability`, `SkillCategory`, `SkillProficiency`
(módulo profile) e `IdentityProvider`, `IdentityType`, `CredentialsType`
(módulo identity) implementam `HasLabel`, `HasColor` e `HasIcon`. Badges e
filtros ganham label, cor e ícone sem nenhuma configuração manual.

**5. `expected_salary_min` / `expected_salary_max` são dado sensível.**
Ficam fora da tabela e fora do form. Aparecem apenas no infolist da View page,
dentro de uma Section colapsada e iniciada fechada.

## Arquitetura da navegação

```text
  ┌─ PanelAdminServiceProvider::defaultNavigation() ────────────────┐
  │                                                                 │
  │  $builder                                                       │
  │    ->items([ ...Dashboard::getNavigationItems() ])   ← solto    │
  │    ->groups([                                                   │
  │        NavigationGroup::make(NavGroup::People->getLabel())       │
  │          ->icon(NavGroup::People->getIcon())                    │
  │          ->items([                                              │
  │             ...UserResource::getNavigationItems(),              │
  │             ...ExternalIdentityResource::getNavigationItems(),  │
  │             ...ProfileResource::getNavigationItems(),           │
  │             ...SkillResource::getNavigationItems(),             │
  │          ]),                                                    │
  │     ])                                                          │
  │    ->items([                                    ← inalterado    │
  │        ...ModerationCluster::getNavigationItems(),              │
  │        ...MarketingCluster::getNavigationItems(),               │
  │        ...TwitchCluster::getNavigationItems(),                  │
  │        ...GithubCluster::getNavigationItems(),                  │
  │        ...DiscordCluster::getNavigationItems(),                 │
  │        ...EventResource::getNavigationItems(),                  │
  │     ])                                                          │
  └─────────────────────────────────────────────────────────────────┘
```

Os cinco clusters e o `EventResource` continuam exatamente como estão. A única
mudança neles é que `ExternalIdentityResource` sai da lista solta e entra no
grupo Pessoas.

## Fronteiras de módulo

```text
  ┌──────────────┐   lê    ┌─────────────┐
  │ panel-admin  │ ──────► │  identity   │  User, ExternalIdentity
  │ (presentation)│ ──────► │  profile    │  Profile, Skill, ProfileSkill,
  └──────┬───────┘         └─────────────┘  WorkExperience
         │  lê (só URL de link)
         ▼
  ┌──────────────┐
  │  moderation  │  ModerationCaseResource::getUrl()
  └──────────────┘
```

`identity` já importa `He4rt\Profile\Models\Profile` no `UserObserver`, então a
relação `User::profile()` que este plano adiciona não cria acoplamento novo —
apenas torna explícito o que já existe.

---

# 1. Commands

Rodar nesta ordem, antes de escrever qualquer código.

```bash
php artisan make:filament-resource User \
    --view --generate \
    --model-namespace="He4rt\Identity\User\Models" \
    --resource-namespace="He4rt\PanelAdmin\Filament\Resources" \
    --record-title-attribute=username \
    --no-interaction

php artisan make:filament-resource Profile \
    --view --generate \
    --model-namespace="He4rt\Profile\Models" \
    --resource-namespace="He4rt\PanelAdmin\Filament\Resources" \
    --record-title-attribute=nickname \
    --no-interaction

php artisan make:filament-resource Skill \
    --generate \
    --model-namespace="He4rt\Profile\Models" \
    --resource-namespace="He4rt\PanelAdmin\Filament\Resources" \
    --record-title-attribute=name \
    --no-interaction

php artisan make:filament-relation-manager UserResource providers provider \
    --resource-namespace="He4rt\PanelAdmin\Filament\Resources" \
    --no-interaction

php artisan make:filament-relation-manager ProfileResource workExperiences company_name \
    --resource-namespace="He4rt\PanelAdmin\Filament\Resources" \
    --no-interaction

php artisan make:filament-relation-manager ProfileResource profileSkills skill.name \
    --resource-namespace="He4rt\PanelAdmin\Filament\Resources" \
    --no-interaction
```

Depois de gerar, **conferir o path**: os arquivos devem cair em
`app-modules/panel-admin/src/Filament/Resources/{Users,Profiles,Skills}/`.
Se o gerador escrever em `app/Filament/Resources/`, mover à mão e corrigir o
namespace para `He4rt\PanelAdmin\Filament\Resources\...`.

Nenhuma migration é criada — todas as tabelas já existem.

---

# 2. Models

## 2.1 `He4rt\Identity\User\Models\User` — atualizar

**Adicionar** a relação com o perfil (hoje inexistente):

```
Relationship: profile
  Type: HasOne<Profile, $this>
  Target: He4rt\Profile\Models\Profile
  PHPDoc: /** @return HasOne<Profile, $this> */
```

**Adicionar** um accessor de situação, usado pela coluna e pelo filtro. Ele é
derivado — não há coluna nova, nenhuma migration:

```
Accessor: situation
  Returns: He4rt\Identity\User\Enums\UserSituation
  Logic:
    - banned_at is not null              -> UserSituation::Banned
    - suspended_until is in the future   -> UserSituation::Suspended
    - otherwise                          -> UserSituation::Active
  PHPDoc: @property-read UserSituation $situation
```

**Corrigir desvio de guideline** (o model usa `newFactory()` em vez do atributo):

```
Remove: protected static function newFactory(): UserFactory
Add:    #[UseFactory(factoryClass: UserFactory::class)]
Import: Illuminate\Database\Eloquent\Attributes\UseFactory
```

Manter o trait `HasFactory` — ele é quem fornece `factory()`.

**Não alterar** `banned_at`, `suspended_until`, nem os casts existentes.

## 2.2 `He4rt\Identity\User\Enums\UserSituation` — criar

Enum novo. Pela guideline do repo (`.ai/07-enum-filament-contracts`), todo enum
novo implementa os contratos Filament no mesmo commit, e escalas ordenadas
formam rampa claro → vermelho.

```
Enum: He4rt\Identity\User\Enums\UserSituation
  Backed by: string
  Implements: Filament\Support\Contracts\HasColor,
              Filament\Support\Contracts\HasDescription,
              Filament\Support\Contracts\HasIcon,
              Filament\Support\Contracts\HasLabel

  Cases:
    Active    = 'active'
    Suspended = 'suspended'
    Banned    = 'banned'

  getLabel():
    Active    -> 'Ativo'
    Suspended -> 'Suspenso'
    Banned    -> 'Banido'

  getColor():   (escala ordenada, rampa claro -> vermelho)
    Active    -> 'success'
    Suspended -> 'warning'
    Banned    -> 'danger'

  getDescription():
    Active    -> 'Sem restrição ativa'
    Suspended -> 'Acesso bloqueado até a data de término'
    Banned    -> 'Acesso revogado por decisão de moderação'

  getIcon():
    Active    -> Heroicon::OutlinedCheckCircle
    Suspended -> Heroicon::OutlinedClock
    Banned    -> Heroicon::OutlinedNoSymbol

  Imports: Filament\Support\Icons\Heroicon
```

Cada getter é um `match ($this)` sobre **todos** os cases, sem braço `default`.

## 2.3 `He4rt\Profile\Models\Skill` — atualizar

Falta o atributo de factory exigido pela guideline:

```
Add:    #[UseFactory(factoryClass: SkillFactory::class)]
Import: Illuminate\Database\Eloquent\Attributes\UseFactory
```

## 2.4 Models não alterados

`Profile`, `ProfileSkill`, `WorkExperience` e `ExternalIdentity` ficam como
estão. Já têm `#[Table]`, PHPDoc completo, casts e relações necessárias.

---

# 3. Navegação

## 3.1 `He4rt\PanelAdmin\Enums\NavigationGroup` — criar

```
Enum: He4rt\PanelAdmin\Enums\NavigationGroup
  Pure enum (não backed) — a ordem dos cases define a ordem dos grupos
  Implements: Filament\Support\Contracts\HasIcon,
              Filament\Support\Contracts\HasLabel
  Docs: https://filamentphp.com/docs/5.x/navigation/overview#registering-navigation-groups-with-an-enum

  Cases (nesta ordem):
    People

  getLabel(): People -> __('panel-admin::navigation.groups.people')

  getIcon(): string | BackedEnum | Htmlable | null
    People -> Heroicon::OutlinedUsers

  Imports:
    - BackedEnum
    - Filament\Support\Icons\Heroicon
    - Illuminate\Contracts\Support\Htmlable
```

Só o case `People` nesta leva. Os demais grupos entram quando suas telas
existirem — um case sem tela renderiza um grupo vazio.

## 3.2 Traduções — criar

`app-modules/panel-admin/lang/pt_BR/navigation.php`:

```php
return [
    'groups' => [
        'people' => 'Pessoas',
    ],
];
```

`app-modules/panel-admin/lang/en/navigation.php`:

```php
return [
    'groups' => [
        'people' => 'People',
    ],
];
```

## 3.3 `PanelAdminServiceProvider` — atualizar

**Em `register()`**, adicionar os três Resources novos ao array `->resources([...])`
que já lista `ExternalIdentityResource` e `EventResource`:

```
->resources([
    ExternalIdentityResource::class,
    EventResource::class,
    UserResource::class,        ← novo
    ProfileResource::class,     ← novo
    SkillResource::class,       ← novo
])
```

**Em `defaultNavigation()`**, trocar a lista plana única por itens soltos +
um grupo, conforme o diagrama da seção "Arquitetura da navegação":

```
Antes:
  return $builder->items([
      ...Dashboard::getNavigationItems(),
      ...ModerationCluster::getNavigationItems(),
      ...MarketingCluster::getNavigationItems(),
      ...TwitchCluster::getNavigationItems(),
      ...GithubCluster::getNavigationItems(),
      ...ExternalIdentityResource::getNavigationItems(),
      ...EventResource::getNavigationItems(),
      ...DiscordCluster::getNavigationItems(),
  ]);

Depois:
  return $builder
      ->items([
          ...Dashboard::getNavigationItems(),
          ...ModerationCluster::getNavigationItems(),
          ...MarketingCluster::getNavigationItems(),
          ...TwitchCluster::getNavigationItems(),
          ...GithubCluster::getNavigationItems(),
          ...DiscordCluster::getNavigationItems(),
          ...EventResource::getNavigationItems(),
      ])
      ->groups([
          NavigationGroup::make(NavGroup::People->getLabel())
              ->icon(NavGroup::People->getIcon())
              ->items([
                  ...UserResource::getNavigationItems(),
                  ...ExternalIdentityResource::getNavigationItems(),
                  ...ProfileResource::getNavigationItems(),
                  ...SkillResource::getNavigationItems(),
              ]),
      ]);

  Imports:
    - Filament\Navigation\NavigationGroup
    - He4rt\PanelAdmin\Enums\NavigationGroup as NavGroup   (alias obrigatório:
      o nome colide com a classe de navegação do Filament)
```

`ExternalIdentityResource` sai de `->items()` e entra no grupo. Os cinco clusters
e o `EventResource` permanecem soltos, sem alteração de comportamento.

**Não** mexer em `buildNavigation()`, `moderationNavigation()`,
`marketingNavigation()`, `twitchNavigation()` nem `discordNavigation()`.

---

# 4. Resources

## 4.1 UserResource

```
Resource: UserResource
  Command: ver seção 1
  Location: He4rt\PanelAdmin\Filament\Resources\Users\UserResource
  Model: He4rt\Identity\User\Models\User
  Docs: https://filamentphp.com/docs/5.x/resources/overview

  Slug: users
  RecordTitleAttribute: username
  Icon: Heroicon::OutlinedUsers
  NavigationGroup: montado pelo builder (seção 3.3), não por $navigationGroup

  GloballySearchableAttributes: [username, name, email]

  getEloquentQuery(): ->withCount('providers')

  canCreate(): false
    Motivo: usuário nasce por OAuth (FindOrCreateUserByProvider). Criar à mão
    produziria conta sem identidade externa, que não consegue logar.

  Pages:
    index → ListUsers
    view  → ViewUser
    edit  → EditUser
    (sem CreateUser — remover a página e a entrada em getPages())
```

### Form (Edit)

```
Form:
  Columns: 2

  Section: Identificação
    Component: Filament\Schemas\Components\Section
    Docs: https://filamentphp.com/docs/5.x/schemas/sections
    ColumnSpan: full
    Columns: 2

    Field: username
      Component: Filament\Forms\Components\TextInput
      Docs: https://filamentphp.com/docs/5.x/forms/text-input
      Validation: required, max:255, unique:users,username
      Config: ->maxLength(255), ->required()

    Field: name
      Component: Filament\Forms\Components\TextInput
      Docs: https://filamentphp.com/docs/5.x/forms/text-input
      Validation: required, max:255
      Config: ->maxLength(255), ->required()

    Field: email
      Component: Filament\Forms\Components\TextInput
      Docs: https://filamentphp.com/docs/5.x/forms/text-input
      Validation: nullable, email, max:255, unique:users,email
      Config: ->email(), ->maxLength(255)

  Section: Sinalizações
    Component: Filament\Schemas\Components\Section
    ColumnSpan: full
    Columns: 1

    Field: is_donator
      Component: Filament\Forms\Components\Toggle
      Docs: https://filamentphp.com/docs/5.x/forms/toggle
      Validation: boolean
      Config: ->helperText('Marca manual de apoiador. Não afeta permissões.')
```

**Nenhum campo de punição no form.** `banned_at` e `suspended_until` não entram —
ver restrição 1.

### Table

```
Table:
  DefaultSort: created_at desc

  Column: username
    Component: Filament\Tables\Columns\TextColumn
    Docs: https://filamentphp.com/docs/5.x/tables/columns/text
    Config: ->searchable(), ->sortable(), ->weight(FontWeight::Medium)
    Imports: Filament\Support\Enums\FontWeight

  Column: name
    Component: Filament\Tables\Columns\TextColumn
    Docs: https://filamentphp.com/docs/5.x/tables/columns/text
    Config: ->searchable(), ->sortable(), ->description(fn (User $record): ?string => $record->email)

  Column: situation
    Component: Filament\Tables\Columns\TextColumn
    Docs: https://filamentphp.com/docs/5.x/tables/columns/text
    Config: ->badge(), ->state(fn (User $record): UserSituation => $record->situation), ->sortable(false)
    Nota: cor, label e ícone vêm do enum (HasColor/HasLabel/HasIcon) — não
          configurar ->color() manualmente.

  Column: suspended_until
    Component: Filament\Tables\Columns\TextColumn
    Docs: https://filamentphp.com/docs/5.x/tables/columns/text
    Config: ->dateTime('d/m/Y H:i'), ->timezone(config('app.display_timezone')),
            ->sortable(), ->placeholder('—'), ->toggleable(isToggledHiddenByDefault: true)

  Column: is_donator
    Component: Filament\Tables\Columns\IconColumn
    Docs: https://filamentphp.com/docs/5.x/tables/columns/icon
    Config: ->boolean(), ->sortable()

  Column: providers_count
    Component: Filament\Tables\Columns\TextColumn
    Docs: https://filamentphp.com/docs/5.x/tables/columns/text
    Config: ->label('Identidades'), ->counts('providers'), ->numeric(0), ->sortable()

  Column: first_login_at
    Component: Filament\Tables\Columns\TextColumn
    Docs: https://filamentphp.com/docs/5.x/tables/columns/text
    Config: ->dateTime('d/m/Y H:i'), ->timezone(config('app.display_timezone')),
            ->sortable(), ->placeholder('Nunca'), ->toggleable(isToggledHiddenByDefault: true)

  Column: created_at
    Component: Filament\Tables\Columns\TextColumn
    Docs: https://filamentphp.com/docs/5.x/tables/columns/text
    Config: ->dateTime('d/m/Y H:i'), ->timezone(config('app.display_timezone')),
            ->sortable(), ->toggleable(isToggledHiddenByDefault: true)
```

### Filters

```
Filter: situation
  Component: Filament\Tables\Filters\SelectFilter
  Docs: https://filamentphp.com/docs/5.x/tables/filters/select
  Config: ->options(UserSituation::class),
          ->query(fn (Builder $query, array $data): Builder => match ($data['value'] ?? null) {
              UserSituation::Banned->value    => $query->whereNotNull('banned_at'),
              UserSituation::Suspended->value => $query->whereNull('banned_at')->where('suspended_until', '>', now()),
              UserSituation::Active->value    => $query->whereNull('banned_at')
                  ->where(fn (Builder $inner): Builder => $inner->whereNull('suspended_until')->orWhere('suspended_until', '<=', now())),
              default => $query,
          })
  Imports: Illuminate\Database\Eloquent\Builder

Filter: is_donator
  Component: Filament\Tables\Filters\TernaryFilter
  Docs: https://filamentphp.com/docs/5.x/tables/filters/ternary
  Config: ->label('Apoiador')

Filter: never_logged_in
  Component: Filament\Tables\Filters\Filter
  Docs: https://filamentphp.com/docs/5.x/tables/filters/custom
  Config: ->label('Nunca logou'),
          ->query(fn (Builder $query): Builder => $query->whereNull('first_login_at'))
```

### Actions

```
Action: view
  Component: Filament\Actions\ViewAction
  Docs: https://filamentphp.com/docs/5.x/actions/overview
  Location: table row

Action: edit
  Component: Filament\Actions\EditAction
  Docs: https://filamentphp.com/docs/5.x/actions/overview
  Location: table row

Action: moderationCases
  Component: Filament\Actions\Action
  Docs: https://filamentphp.com/docs/5.x/actions/overview
  Location: table row
  Icon: Heroicon::OutlinedShieldExclamation
  Color: gray
  Label: 'Casos de moderação'
  Visibility: always
  Authorization: nenhuma além do acesso ao painel
  Behavior:
    - Abre a listagem de casos já filtrada por este usuário como autor
    - Config: ->url(fn (User $record): string => ModerationCaseResource::getUrl('index', [
          'tableFilters' => ['author' => ['value' => $record->getKey()]],
      ])), ->openUrlInNewTab(false)
  Imports: He4rt\PanelAdmin\Moderation\Resources\ModerationCaseResource
  Dependência: exige o filtro 'author' descrito em 4.5 — implementar os dois juntos.

Toolbar: nenhuma bulk action.
  Motivo: exclusão em massa de usuários não é operação que este painel deva
  oferecer; `users` é alvo de 19 FKs e a cascata não está mapeada.
```

### Infolist (View page)

```
Infolist:
  Columns: 1

  Section: Conta
    Component: Filament\Schemas\Components\Section
    Docs: https://filamentphp.com/docs/5.x/schemas/sections
    Columns: 2
    Icon: Heroicon::OutlinedUser

    Entry: username
      Component: Filament\Infolists\Components\TextEntry
      Docs: https://filamentphp.com/docs/5.x/infolists/text-entry
      Config: ->copyable()

    Entry: name
      Component: Filament\Infolists\Components\TextEntry
      Docs: https://filamentphp.com/docs/5.x/infolists/text-entry

    Entry: email
      Component: Filament\Infolists\Components\TextEntry
      Docs: https://filamentphp.com/docs/5.x/infolists/text-entry
      Config: ->copyable(), ->placeholder('Sem e-mail')

    Entry: is_donator
      Component: Filament\Infolists\Components\IconEntry
      Docs: https://filamentphp.com/docs/5.x/infolists/icon-entry
      Config: ->boolean(), ->label('Apoiador')

    Entry: first_login_at
      Component: Filament\Infolists\Components\TextEntry
      Docs: https://filamentphp.com/docs/5.x/infolists/text-entry
      Config: ->dateTime('d/m/Y H:i'), ->timezone(config('app.display_timezone')), ->placeholder('Nunca')

    Entry: created_at
      Component: Filament\Infolists\Components\TextEntry
      Docs: https://filamentphp.com/docs/5.x/infolists/text-entry
      Config: ->dateTime('d/m/Y H:i'), ->timezone(config('app.display_timezone'))

  Section: Situação
    Component: Filament\Schemas\Components\Section
    Columns: 2
    Icon: Heroicon::OutlinedShieldCheck
    Description: 'Somente leitura. Punições são aplicadas pelo fluxo de moderação.'

    Entry: situation
      Component: Filament\Infolists\Components\TextEntry
      Docs: https://filamentphp.com/docs/5.x/infolists/text-entry
      Config: ->badge(), ->state(fn (User $record): UserSituation => $record->situation)

    Entry: suspended_until
      Component: Filament\Infolists\Components\TextEntry
      Docs: https://filamentphp.com/docs/5.x/infolists/text-entry
      Config: ->dateTime('d/m/Y H:i'), ->timezone(config('app.display_timezone')), ->placeholder('—')

    Entry: banned_at
      Component: Filament\Infolists\Components\TextEntry
      Docs: https://filamentphp.com/docs/5.x/infolists/text-entry
      Config: ->dateTime('d/m/Y H:i'), ->timezone(config('app.display_timezone')), ->placeholder('—')

  Section: Perfil
    Component: Filament\Schemas\Components\Section
    Columns: 2
    Icon: Heroicon::OutlinedIdentification
    Collapsible: yes

    Entry: profile.headline
      Component: Filament\Infolists\Components\TextEntry
      Docs: https://filamentphp.com/docs/5.x/infolists/text-entry
      Config: ->label('Headline'), ->placeholder('Não preenchido')

    Entry: profile.seniority_level
      Component: Filament\Infolists\Components\TextEntry
      Docs: https://filamentphp.com/docs/5.x/infolists/text-entry
      Config: ->badge(), ->label('Senioridade'), ->placeholder('—')

    Entry: profile.available_for_proposals
      Component: Filament\Infolists\Components\IconEntry
      Docs: https://filamentphp.com/docs/5.x/infolists/icon-entry
      Config: ->boolean(), ->label('Aberto a propostas')
```

### RelationManager: providers

```
RelationManager: ProvidersRelationManager
  Location: He4rt\PanelAdmin\Filament\Resources\Users\RelationManagers\ProvidersRelationManager
  Relationship: providers (MorphMany ExternalIdentity)
  Title attribute: provider
  Can create: no
  Can edit: no
  Can delete: no
  Motivo: vincular e desvincular identidade é responsabilidade das Actions do
          módulo identity (LinkExternalIdentity / AttachProviderToUser), que
          disparam eventos. Aqui é leitura.

  Table:
    Column: provider
      Component: Filament\Tables\Columns\TextColumn
      Docs: https://filamentphp.com/docs/5.x/tables/columns/text
      Config: ->badge()

    Column: type
      Component: Filament\Tables\Columns\TextColumn
      Docs: https://filamentphp.com/docs/5.x/tables/columns/text
      Config: ->badge()

    Column: external_account_id
      Component: Filament\Tables\Columns\TextColumn
      Docs: https://filamentphp.com/docs/5.x/tables/columns/text
      Config: ->copyable(), ->placeholder('—')

    Column: connected_at
      Component: Filament\Tables\Columns\TextColumn
      Docs: https://filamentphp.com/docs/5.x/tables/columns/text
      Config: ->dateTime('d/m/Y H:i'), ->timezone(config('app.display_timezone')), ->sortable()

    Column: disconnected_at
      Component: Filament\Tables\Columns\TextColumn
      Docs: https://filamentphp.com/docs/5.x/tables/columns/text
      Config: ->dateTime('d/m/Y H:i'), ->timezone(config('app.display_timezone')), ->placeholder('Ativa')

  Record actions:
    Action: view
      Component: Filament\Actions\Action
      Location: table row
      Label: 'Abrir'
      Config: ->url(fn (ExternalIdentity $record): string => ExternalIdentityResource::getUrl('edit', ['record' => $record]))
```

---

## 4.2 ExternalIdentityResource — atualizar

Resource já existe. Apenas mudanças:

```
Modify: navegação
  ExternalIdentityResource sai de ->items() e entra no grupo Pessoas (seção 3.3).
  Nenhuma alteração dentro da classe do Resource.

Modify: ExternalIdentitiesTable
  Remove Column: metadata
    Motivo: renderiza array bruto na listagem. Sem valor numa tabela.

  Modify Column: provider
    Config atual: ->badge(), ->label('Provider')
    Config novo:  ->badge(), ->label('Provider'), ->searchable(), ->sortable()

  Modify Column: connected_at / disconnected_at
    Config atual: ->date()
    Config novo:  ->dateTime('d/m/Y H:i'), ->timezone(config('app.display_timezone')), ->sortable()
    Motivo: guideline `.ai/05-timezone-aware-dates` — datas exibidas ao usuário
            usam config('app.display_timezone').

  Add Filter: provider
    Component: Filament\Tables\Filters\SelectFilter
    Docs: https://filamentphp.com/docs/5.x/tables/filters/select
    Config: ->options(IdentityProvider::class), ->multiple(), ->searchable()
    Imports: He4rt\Identity\ExternalIdentity\Enums\IdentityProvider

  Add Filter: credentials_type
    Component: Filament\Tables\Filters\SelectFilter
    Docs: https://filamentphp.com/docs/5.x/tables/filters/select
    Config: ->options(CredentialsType::class)
    Imports: He4rt\Identity\ExternalIdentity\Enums\CredentialsType

  Add Filter: connection_state
    Component: Filament\Tables\Filters\TernaryFilter
    Docs: https://filamentphp.com/docs/5.x/tables/filters/ternary
    Config: ->label('Conexão ativa'),
            ->queries(
                true: fn (Builder $query): Builder => $query->whereNull('disconnected_at'),
                false: fn (Builder $query): Builder => $query->whereNotNull('disconnected_at'),
                blank: fn (Builder $query): Builder => $query,
            )

  Keep Filter: trashed (TrashedFilter) — inalterado
```

Não mexer em `ExternalIdentityForm`, `ExternalIdentityInfolist`,
`MessagesRelationManager` nem nas Pages.

---

## 4.3 ProfileResource

```
Resource: ProfileResource
  Command: ver seção 1
  Location: He4rt\PanelAdmin\Filament\Resources\Profiles\ProfileResource
  Model: He4rt\Profile\Models\Profile
  Docs: https://filamentphp.com/docs/5.x/resources/overview

  Slug: profiles
  RecordTitleAttribute: nickname
  Icon: Heroicon::OutlinedIdentification

  GloballySearchableAttributes: [nickname, headline, user.username]

  getEloquentQuery(): ->with('user')->withCount('profileSkills')

  canCreate(): false
  canDelete(): false
  canDeleteAny(): false
    Motivo: UserObserver::created() já cria o perfil via Profile::ensureExists().
    Criar produz violação do índice único em user_id; apagar quebra a invariante
    "todo usuário tem perfil" que o resto do sistema assume.

  Pages:
    index → ListProfiles
    view  → ViewProfile
    edit  → EditProfile
    (sem CreateProfile)
```

### Form (Edit)

```
Form:
  Columns: 2

  Section: Apresentação
    Component: Filament\Schemas\Components\Section
    ColumnSpan: full
    Columns: 2

    Field: nickname
      Component: Filament\Forms\Components\TextInput
      Docs: https://filamentphp.com/docs/5.x/forms/text-input
      Validation: nullable, max:255
      Config: ->maxLength(255)

    Field: headline
      Component: Filament\Forms\Components\TextInput
      Docs: https://filamentphp.com/docs/5.x/forms/text-input
      Validation: nullable, max:255
      Config: ->maxLength(255)

    Field: about
      Component: Filament\Forms\Components\Textarea
      Docs: https://filamentphp.com/docs/5.x/forms/textarea
      Validation: nullable, max:5000
      Config: ->rows(5), ->columnSpanFull()

    Field: birthdate
      Component: Filament\Forms\Components\DatePicker
      Docs: https://filamentphp.com/docs/5.x/forms/date-time-picker
      Validation: nullable, date, before:today
      Config: ->maxDate(now()), ->native(false), ->displayFormat('d/m/Y')

  Section: Carreira
    Component: Filament\Schemas\Components\Section
    ColumnSpan: full
    Columns: 2

    Field: seniority_level
      Component: Filament\Forms\Components\Select
      Docs: https://filamentphp.com/docs/5.x/forms/select
      Validation: nullable
      Config: ->options(SeniorityLevel::class)
      Imports: He4rt\Profile\Enums\SeniorityLevel

    Field: years_experience
      Component: Filament\Forms\Components\TextInput
      Docs: https://filamentphp.com/docs/5.x/forms/text-input
      Validation: nullable, integer, min:0, max:70
      Config: ->integer(), ->minValue(0), ->maxValue(70)

    Field: available_for_proposals
      Component: Filament\Forms\Components\Toggle
      Docs: https://filamentphp.com/docs/5.x/forms/toggle
      Validation: boolean
      Config: ->live()
      Imports: Filament\Schemas\Components\Utilities\Get

    Field: start_availability
      Component: Filament\Forms\Components\Select
      Docs: https://filamentphp.com/docs/5.x/forms/select
      Validation: nullable
      Config: ->options(StartAvailability::class),
              ->visible(fn (Get $get): bool => (bool) $get('available_for_proposals'))
      Imports: He4rt\Profile\Enums\StartAvailability,
               Filament\Schemas\Components\Utilities\Get
```

**Fora do form**: `expected_salary_min`, `expected_salary_max`, `social_links`,
`preferences` e `user_id`. Salário é sensível (restrição 5); `preferences` é
value object com cast próprio (`AsWorkPreferences`) e merece tela própria;
`user_id` é imutável.

### Table

```
Table:
  DefaultSort: created_at desc

  Column: user.username
    Component: Filament\Tables\Columns\TextColumn
    Docs: https://filamentphp.com/docs/5.x/tables/columns/text
    Config: ->label('Usuário'), ->searchable(), ->sortable(), ->weight(FontWeight::Medium)
    Imports: Filament\Support\Enums\FontWeight

  Column: nickname
    Component: Filament\Tables\Columns\TextColumn
    Docs: https://filamentphp.com/docs/5.x/tables/columns/text
    Config: ->searchable(), ->placeholder('—')

  Column: headline
    Component: Filament\Tables\Columns\TextColumn
    Docs: https://filamentphp.com/docs/5.x/tables/columns/text
    Config: ->searchable(), ->limit(50), ->placeholder('Não preenchido')

  Column: seniority_level
    Component: Filament\Tables\Columns\TextColumn
    Docs: https://filamentphp.com/docs/5.x/tables/columns/text
    Config: ->badge(), ->placeholder('—'), ->sortable()

  Column: years_experience
    Component: Filament\Tables\Columns\TextColumn
    Docs: https://filamentphp.com/docs/5.x/tables/columns/text
    Config: ->numeric(0), ->sortable(), ->suffix(' anos'), ->placeholder('—')

  Column: available_for_proposals
    Component: Filament\Tables\Columns\IconColumn
    Docs: https://filamentphp.com/docs/5.x/tables/columns/icon
    Config: ->boolean(), ->label('Aberto a propostas'), ->sortable()

  Column: profile_skills_count
    Component: Filament\Tables\Columns\TextColumn
    Docs: https://filamentphp.com/docs/5.x/tables/columns/text
    Config: ->label('Skills'), ->counts('profileSkills'), ->numeric(0), ->sortable()

Filters:
  Filter: seniority_level
    Component: Filament\Tables\Filters\SelectFilter
    Docs: https://filamentphp.com/docs/5.x/tables/filters/select
    Config: ->options(SeniorityLevel::class), ->multiple()

  Filter: available_for_proposals
    Component: Filament\Tables\Filters\TernaryFilter
    Docs: https://filamentphp.com/docs/5.x/tables/filters/ternary
    Config: ->label('Aberto a propostas')

  Filter: incomplete
    Component: Filament\Tables\Filters\Filter
    Docs: https://filamentphp.com/docs/5.x/tables/filters/custom
    Config: ->label('Perfil incompleto'),
            ->query(fn (Builder $query): Builder => $query->whereNull('headline')->orWhereNull('seniority_level'))

Actions: ViewAction, EditAction (namespaces Filament\Actions\*)
Toolbar: nenhuma bulk action (canDelete é false)
```

### Infolist (View page)

```
Infolist:
  Columns: 1

  Section: Apresentação
    Columns: 2
    Entry: user.username   → TextEntry, ->label('Usuário')
    Entry: nickname        → TextEntry, ->placeholder('—')
    Entry: headline        → TextEntry, ->placeholder('Não preenchido')
    Entry: birthdate       → TextEntry, ->date('d/m/Y'), ->placeholder('—')
    Entry: about           → TextEntry, ->columnSpanFull(), ->placeholder('—')
    (todos: Filament\Infolists\Components\TextEntry
     Docs: https://filamentphp.com/docs/5.x/infolists/text-entry)

  Section: Carreira
    Columns: 2
    Entry: seniority_level        → TextEntry, ->badge(), ->placeholder('—')
    Entry: years_experience       → TextEntry, ->numeric(0), ->suffix(' anos')
    Entry: available_for_proposals → IconEntry, ->boolean()
      Component: Filament\Infolists\Components\IconEntry
      Docs: https://filamentphp.com/docs/5.x/infolists/icon-entry
    Entry: start_availability     → TextEntry, ->badge(), ->placeholder('—')

  Section: Remuneração pretendida
    Component: Filament\Schemas\Components\Section
    Columns: 2
    Icon: Heroicon::OutlinedLockClosed
    Collapsible: yes
    Collapsed: yes
    Description: 'Informado pela pessoa. Não exibir fora do painel.'

    Entry: expected_salary_min
      Component: Filament\Infolists\Components\TextEntry
      Docs: https://filamentphp.com/docs/5.x/infolists/text-entry
      Config: ->money('BRL'), ->placeholder('—')

    Entry: expected_salary_max
      Component: Filament\Infolists\Components\TextEntry
      Docs: https://filamentphp.com/docs/5.x/infolists/text-entry
      Config: ->money('BRL'), ->placeholder('—')
```

### RelationManager: profileSkills

```
RelationManager: ProfileSkillsRelationManager
  Location: He4rt\PanelAdmin\Filament\Resources\Profiles\RelationManagers\ProfileSkillsRelationManager
  Relationship: profileSkills (HasMany ProfileSkill)
  Title attribute: skill.name
  Can create: yes
  Can edit: yes
  Can delete: yes

  Form:
    Field: skill_id
      Component: Filament\Forms\Components\Select
      Docs: https://filamentphp.com/docs/5.x/forms/select
      Validation: required, exists:skills,id
      Config: ->relationship('skill', 'name'), ->searchable(),
              ->getSearchResultsUsing(fn (string $search): array => Skill::search($search)),
              ->required()
      Imports: He4rt\Profile\Models\Skill
      Nota: Skill::search() já existe no model e devolve rótulos "Categoria · Nome",
            com limite de 50 — usar em vez de ->preload(), que carregaria as 116 linhas.

    Field: proficiency
      Component: Filament\Forms\Components\Select
      Docs: https://filamentphp.com/docs/5.x/forms/select
      Validation: required
      Config: ->options(SkillProficiency::class), ->required()
      Imports: He4rt\Profile\Enums\SkillProficiency

    Field: years_experience
      Component: Filament\Forms\Components\TextInput
      Docs: https://filamentphp.com/docs/5.x/forms/text-input
      Validation: nullable, integer, min:0, max:70
      Config: ->integer(), ->minValue(0), ->maxValue(70)

  Table:
    Column: skill.name
      Component: Filament\Tables\Columns\TextColumn
      Docs: https://filamentphp.com/docs/5.x/tables/columns/text
      Config: ->label('Skill'), ->searchable(), ->sortable()

    Column: skill.category
      Component: Filament\Tables\Columns\TextColumn
      Docs: https://filamentphp.com/docs/5.x/tables/columns/text
      Config: ->badge(), ->label('Categoria')

    Column: proficiency
      Component: Filament\Tables\Columns\TextColumn
      Docs: https://filamentphp.com/docs/5.x/tables/columns/text
      Config: ->badge(), ->sortable()

    Column: years_experience
      Component: Filament\Tables\Columns\TextColumn
      Docs: https://filamentphp.com/docs/5.x/tables/columns/text
      Config: ->numeric(0), ->suffix(' anos'), ->placeholder('—')
```

### RelationManager: workExperiences

```
RelationManager: WorkExperiencesRelationManager
  Location: He4rt\PanelAdmin\Filament\Resources\Profiles\RelationManagers\WorkExperiencesRelationManager
  Relationship: workExperiences (HasMany WorkExperience)
  Title attribute: company_name
  Can create: yes
  Can edit: yes
  Can delete: yes

  Form:
    Columns: 2

    Field: company_name
      Component: Filament\Forms\Components\TextInput
      Docs: https://filamentphp.com/docs/5.x/forms/text-input
      Validation: required, max:255
      Config: ->maxLength(255), ->required()

    Field: position
      Component: Filament\Forms\Components\TextInput
      Docs: https://filamentphp.com/docs/5.x/forms/text-input
      Validation: required, max:255
      Config: ->maxLength(255), ->required()

    Field: description
      Component: Filament\Forms\Components\Textarea
      Docs: https://filamentphp.com/docs/5.x/forms/textarea
      Validation: required, max:5000
      Config: ->rows(4), ->columnSpanFull(), ->required()

    Field: start_date
      Component: Filament\Forms\Components\DatePicker
      Docs: https://filamentphp.com/docs/5.x/forms/date-time-picker
      Validation: required, date, before_or_equal:today
      Config: ->maxDate(now()), ->native(false), ->displayFormat('d/m/Y'), ->required()

    Field: is_currently_working_here
      Component: Filament\Forms\Components\Toggle
      Docs: https://filamentphp.com/docs/5.x/forms/toggle
      Validation: boolean
      Config: ->live(), ->afterStateUpdated(fn (Set $set, bool $state) => $state ? $set('end_date', null) : null)
      Imports: Filament\Schemas\Components\Utilities\Set

    Field: end_date
      Component: Filament\Forms\Components\DatePicker
      Docs: https://filamentphp.com/docs/5.x/forms/date-time-picker
      Validation: nullable, date, after:start_date, required_if:is_currently_working_here,false
      Config: ->native(false), ->displayFormat('d/m/Y'),
              ->disabled(fn (Get $get): bool => (bool) $get('is_currently_working_here')),
              ->afterOrEqual('start_date')
      Imports: Filament\Schemas\Components\Utilities\Get

  Table:
    Column: company_name
      Component: Filament\Tables\Columns\TextColumn
      Docs: https://filamentphp.com/docs/5.x/tables/columns/text
      Config: ->searchable(), ->sortable(), ->description(fn (WorkExperience $record): string => $record->position)

    Column: start_date
      Component: Filament\Tables\Columns\TextColumn
      Docs: https://filamentphp.com/docs/5.x/tables/columns/text
      Config: ->date('m/Y'), ->sortable()

    Column: end_date
      Component: Filament\Tables\Columns\TextColumn
      Docs: https://filamentphp.com/docs/5.x/tables/columns/text
      Config: ->date('m/Y'), ->placeholder('Atual')

    Column: is_currently_working_here
      Component: Filament\Tables\Columns\IconColumn
      Docs: https://filamentphp.com/docs/5.x/tables/columns/icon
      Config: ->boolean(), ->label('Atual')

  DefaultSort: start_date desc
```

---

## 4.4 SkillResource

```
Resource: SkillResource
  Command: ver seção 1
  Location: He4rt\PanelAdmin\Filament\Resources\Skills\SkillResource
  Model: He4rt\Profile\Models\Skill
  Docs: https://filamentphp.com/docs/5.x/resources/overview

  Slug: skills
  RecordTitleAttribute: name
  Icon: Heroicon::OutlinedSparkles

  GloballySearchableAttributes: [name, slug]

  getEloquentQuery(): ->withCount('profileSkills')

  Sem --simple e sem --view: a listagem precisa de filtros e agrupamento, que o
  modo simples (modais) não comporta, e o catálogo não tem detalhe suficiente
  para justificar uma View page.

  Pages:
    index  → ListSkills
    create → CreateSkill
    edit   → EditSkill
```

### Form

```
Form:
  Columns: 2

  Field: name
    Component: Filament\Forms\Components\TextInput
    Docs: https://filamentphp.com/docs/5.x/forms/text-input
    Validation: required, max:255
    Config: ->maxLength(255), ->required(), ->live(onBlur: true),
            ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug($state ?? '')))
    Imports: Filament\Schemas\Components\Utilities\Set, Illuminate\Support\Str

  Field: slug
    Component: Filament\Forms\Components\TextInput
    Docs: https://filamentphp.com/docs/5.x/forms/text-input
    Validation: required, max:255, unique:skills,slug
    Config: ->maxLength(255), ->required()

  Field: category
    Component: Filament\Forms\Components\Select
    Docs: https://filamentphp.com/docs/5.x/forms/select
    Validation: required
    Config: ->options(SkillCategory::class), ->required()
    Imports: He4rt\Profile\Enums\SkillCategory

  Field: icon
    Component: Filament\Forms\Components\TextInput
    Docs: https://filamentphp.com/docs/5.x/forms/text-input
    Validation: nullable, max:255
    Config: ->maxLength(255), ->helperText('Nome do ícone, ex.: devicon-php-plain')
```

### Table

```
Table:
  DefaultSort: name asc

  Column: name
    Component: Filament\Tables\Columns\TextColumn
    Docs: https://filamentphp.com/docs/5.x/tables/columns/text
    Config: ->searchable(), ->sortable(), ->weight(FontWeight::Medium)
    Imports: Filament\Support\Enums\FontWeight

  Column: slug
    Component: Filament\Tables\Columns\TextColumn
    Docs: https://filamentphp.com/docs/5.x/tables/columns/text
    Config: ->searchable(), ->color('gray'), ->copyable()

  Column: category
    Component: Filament\Tables\Columns\TextColumn
    Docs: https://filamentphp.com/docs/5.x/tables/columns/text
    Config: ->badge(), ->sortable()

  Column: profile_skills_count
    Component: Filament\Tables\Columns\TextColumn
    Docs: https://filamentphp.com/docs/5.x/tables/columns/text
    Config: ->label('Perfis'), ->counts('profileSkills'), ->numeric(0), ->sortable()

Grouping:
  Default: category
  Options: [category]
  Collapsible: true
  Docs: https://filamentphp.com/docs/5.x/tables/grouping

Filters:
  Filter: category
    Component: Filament\Tables\Filters\SelectFilter
    Docs: https://filamentphp.com/docs/5.x/tables/filters/select
    Config: ->options(SkillCategory::class), ->multiple()

  Filter: unused
    Component: Filament\Tables\Filters\Filter
    Docs: https://filamentphp.com/docs/5.x/tables/filters/custom
    Config: ->label('Sem nenhum perfil'),
            ->query(fn (Builder $query): Builder => $query->whereDoesntHave('profileSkills'))
    Imports: Illuminate\Database\Eloquent\Builder

Actions: EditAction, DeleteAction (Filament\Actions\*)
  DeleteAction Visibility: só quando profile_skills_count === 0
    Config: ->visible(fn (Skill $record): bool => $record->profileSkills()->doesntExist())
    Motivo: apagar skill em uso deixa profile_skills órfão — não há cascade.

Toolbar:
  BulkActionGroup contendo DeleteBulkAction
    Component: Filament\Actions\BulkActionGroup, Filament\Actions\DeleteBulkAction
    Docs: https://filamentphp.com/docs/5.x/actions/overview
```

---

## 4.5 ModerationCaseResource — alteração mínima

Necessária para a action `moderationCases` do UserResource funcionar com filtro.

```
Modify: app-modules/panel-admin/src/Moderation/Resources/ModerationCaseResource.php
  Add Filter: author
    Component: Filament\Tables\Filters\SelectFilter
    Docs: https://filamentphp.com/docs/5.x/tables/filters/select
    Config: ->relationship('author', 'username'), ->searchable(), ->preload(false)
    Posição: junto dos filtros existentes (status, source_platform, violation_type, severity)
```

`ModerationCase::author()` já existe (`BelongsTo User`), então basta o filtro.

---

# 5. Authorization

```
Resource: UserResource
  Authorization: qualquer usuário que acesse o painel admin

Resource: ExternalIdentityResource
  Authorization: qualquer usuário que acesse o painel admin

Resource: ProfileResource
  Authorization: qualquer usuário que acesse o painel admin

Resource: SkillResource
  Authorization: qualquer usuário que acesse o painel admin
```

Nenhuma Policy é criada. O controle já existe e é binário:
`User::canAccessPanel()` devolve `isAdmin()` em produção, que compara `username`
contra a lista em `config('he4rt.admins')`. Fora de produção, qualquer usuário
autenticado entra.

Restrições que **não** são autorização e sim design do Resource:

| Restrição                        | Onde              | Motivo                               |
| -------------------------------- | ----------------- | ------------------------------------ |
| `canCreate(): false`             | UserResource      | conta nasce por OAuth                |
| `canCreate(): false`             | ProfileResource   | UserObserver já cria                 |
| `canDelete(): false`             | ProfileResource   | invariante "todo usuário tem perfil" |
| DeleteAction condicional         | SkillResource     | evita `profile_skills` órfão         |
| Sem campo de punição             | UserResource form | WebModerationAdapter é o dono        |
| Salário só no infolist colapsado | ProfileResource   | dado sensível                        |

---

# 6. Tests

Arquivos, seguindo a convenção de `app-modules/panel-admin/tests/Feature/`:

- `app-modules/panel-admin/tests/Feature/Identity/UserResourceTest.php`
- `app-modules/panel-admin/tests/Feature/Identity/ExternalIdentityResourceTest.php`
- `app-modules/panel-admin/tests/Feature/Profile/ProfileResourceTest.php`
- `app-modules/panel-admin/tests/Feature/Profile/SkillResourceTest.php`
- `app-modules/panel-admin/tests/Feature/NavigationGroupsTest.php`
- `app-modules/identity/tests/Unit/UserSituationTest.php`

Todo teste do painel precisa de um admin autenticado, como em
`AdminPanelAccessTest`:

```php
$user = User::factory()->create(['username' => 'danielhe4rt']);
config(['he4rt.admins' => 'danielhe4rt']);
$this->actingAs($user);
```

Usar `use function Pest\Livewire\livewire;`.

```
UserSituation (unit):
  - banned_at preenchido devolve UserSituation::Banned
  - suspended_until no futuro e banned_at nulo devolve Suspended
  - suspended_until no passado devolve Active
  - suspended_until nulo e banned_at nulo devolve Active
  - banned_at vence suspended_until quando os dois estão preenchidos

UserResource:
  Authorization:
    - usuário não-admin não acessa a listagem em produção
    - página de listagem carrega para admin
  Component Config:
    - a listagem não expõe página de criação (canCreate é false)
    - o form de edição não contém campo banned_at
    - o form de edição não contém campo suspended_until
    - coluna situation existe e renderiza como badge
  Filters:
    - filtro situation=banned mostra só usuários com banned_at
    - filtro situation=suspended mostra só suspensão vigente, não vencida
    - filtro situation=active exclui banidos e suspensos vigentes
    - filtro never_logged_in mostra só usuários com first_login_at nulo
    - filtro is_donator separa apoiadores
  Validation (use dataset pattern):
    - username: required, max:255, unique
    - name: required, max:255
    - email: nullable, email, max:255
  Actions:
    - action moderationCases aponta para a URL de casos com o filtro author preenchido
  RelationManagers:
    - ProvidersRelationManager lista as identidades do usuário
    - ProvidersRelationManager não oferece criar, editar nem excluir

ExternalIdentityResource:
  Filters:
    - filtro provider mostra só o provider selecionado
    - filtro connection_state=true mostra só disconnected_at nulo
    - filtro connection_state=false mostra só desconectadas
  Component Config:
    - a tabela não tem mais a coluna metadata

ProfileResource:
  Component Config:
    - a listagem não expõe página de criação
    - a listagem não expõe ação de exclusão
    - start_availability fica oculto enquanto available_for_proposals é falso
    - start_availability aparece quando available_for_proposals é verdadeiro
    - expected_salary_min não aparece no form de edição
    - expected_salary_max não aparece no form de edição
  Filters:
    - filtro seniority_level filtra por senioridade
    - filtro available_for_proposals separa quem está aberto a propostas
    - filtro incomplete mostra perfis sem headline ou sem senioridade
  Validation (use dataset pattern):
    - years_experience: nullable, integer, min:0, max:70
    - birthdate: nullable, date, before today
  RelationManagers:
    - ProfileSkillsRelationManager cria vínculo com skill e proficiência
    - WorkExperiencesRelationManager desabilita end_date quando is_currently_working_here é verdadeiro
    - WorkExperiencesRelationManager rejeita end_date anterior a start_date

SkillResource:
  Component Config:
    - preencher name gera o slug automaticamente
    - a tabela agrupa por category por padrão
  Filters:
    - filtro category filtra por categoria
    - filtro unused mostra só skills sem nenhum profile_skill
  Actions:
    - DeleteAction fica oculta para skill vinculada a algum perfil
    - DeleteAction fica visível para skill sem vínculo
  Validation (use dataset pattern):
    - name: required, max:255
    - slug: required, unique
    - category: required

NavigationGroups:
  - a navegação padrão do painel expõe um grupo com o label 'Pessoas'
  - o grupo Pessoas contém os quatro itens (users, external-identities, profiles, skills)
  - os cinco clusters continuam como itens de topo, fora de grupo
  - ExternalIdentityResource não aparece mais como item solto de topo
```

Rodar: `php artisan test --compact --parallel` no módulo afetado, e
`vendor/bin/pest --parallel --update-shards` na suíte completa antes do push.

---

# 7. Verificação antes de abrir PR

- [ ] `vendor/bin/pint --dirty --format agent`
- [ ] `vendor/bin/phpstan analyse` limpo nos módulos tocados
- [ ] `php artisan test --compact --parallel`
- [ ] Nenhum arquivo novo em `app/Filament/` — tudo em `app-modules/panel-admin/src/Filament/Resources/`
- [ ] Nenhuma escrita em `banned_at` ou `suspended_until` fora de `WebModerationAdapter`
- [ ] Datas exibidas usam `config('app.display_timezone')`
- [ ] `UserSituation` implementa os quatro contratos, `match` sem `default`
- [ ] `#[UseFactory]` presente em `User` e `Skill`
- [ ] Branch `feature/*` ou `story/*`, commits `<type>(panel-admin): ...`

# 8. Fora de escopo, registrado

| Item                                                 | Por quê                                                                                          |
| ---------------------------------------------------- | ------------------------------------------------------------------------------------------------ |
| Aplicar punição pelo painel                          | Decisão do autor: só leitura nesta leva                                                          |
| Enum de grupos para os outros 8 grupos               | Requer as telas correspondentes                                                                  |
| Desmontar o `NavigationBuilder` custom e os clusters | Decisão do autor: clusters intactos                                                              |
| `UserPolicy` e permissões granulares                 | Decisão do autor: manter autorização binária                                                     |
| Tela de `preferences` (WorkPreferences)              | Value object com cast próprio, merece tela dedicada                                              |
| `user_information` e `user_address`                  | Tabelas legadas, sobrepõem `user_profiles` — precisam de decisão de domínio antes de ganhar tela |
