---
type: spec
title: 'Tracking de contribuições: GitHub como primeira fonte externa'
module: activity
status: proposed
date: 2026-08-23
author: danielhe4rt
related:
    modules: [activity, integration-github, identity, panel-admin]
---

# Tracking de contribuições: GitHub como primeira fonte externa

## Contexto

O módulo `activity/src/Tracking/` foi desenhado para ser o hub que reúne contribuição de
todas as fontes. Hoje ele está pela metade, de um jeito que engana quem lê o código.

O `ActivityType` declara treze casos — entre eles `pr_merged`, `peer_review` e `repo_star`.
Apenas **um** tem produtor: `article`, alimentado pelo `TrackContentContribution` a partir do
dev.to. Os outros doze são promessa. Quem abre o enum conclui que o GitHub é rastreado.

Em paralelo, `github_contributions` acumula **7.974 linhas** desde 30/03/2020, com 308 atores
distintos, e nunca encosta no Tracking. A única leitura dessa tabela fora do próprio módulo é
o deck de retrospectiva, que agrupa por `actor_login` e jamais resolve a identidade.

```text
  ESTADO ATUAL

  ContentEntry ──ArticlePublished──► TrackContentContribution ──► interactions
                                                                   72 linhas
                                                                   todas article/devto/pending
                                                                   nenhuma aprovada

  GitHub ──webhook/backfill──► github_contributions ──► GithubSource (retrospectiva)
                                7.974 linhas                │
                                                            └──► e mais nada.
```

O `interactions` também carrega a economia que está sendo aposentada: `character_id` NOT NULL
com FK para `characters`, `coins_min`, `coins_max`, `coins_awarded`, `xp_awarded`, `value_tier`.
Mais da metade das colunas serve a coins e XP que deixam de existir.

O custo de mexer é baixo e não vai ficar mais baixo: fora do próprio módulo, o único
consumidor de `Interaction` é o `Character`, pelo trait `HasInteractions`. Nenhum painel,
nenhuma tela, nenhuma rota lê a tabela.

## Objetivo

Tornar o `Tracking` o registro canônico de contribuição, ligado à identidade externa
conectada, e plugar o GitHub como primeira fonte externa de verdade — removendo no caminho
a economia aposentada e todo caso de enum sem produtor.

## Fora de escopo

- Perfil público renderizando contribuição.
- Outras fontes: Discord, WhatsApp, `messages`, `voice`.
- Renomear `Interaction` para `Contribution` — fica para um PR seguinte (ver _Dívida assumida_).

---

## Decisões

| #   | Decisão                                                         | Racional                                                                                                                                                                                 |
| --- | --------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1   | `external_identity_id` é a verdade; `user_id` é derivado        | O dono da identidade é polimórfico (`model_type`/`model_id`), e `hasManyThrough` não atravessa morph. FK NOT NULL para a identidade faz "só conectado" virar schema em vez de `if`.      |
| 2   | Contribuição de quem não conectou é descartada na entrada       | O lake guarda tudo; a conexão resgata depois (decisão 8). Descarte é adiamento, não perda.                                                                                               |
| 3   | Curadoria é opt-out: entra visível                              | Contribuição do GitHub é fato verificável, não alegação. Sem prêmio, não há fraude a barrar. O histórico mostra que fila de aprovação não é trabalhada: 72 linhas em `pending` há meses. |
| 4   | `value_tier`, coins, XP e o config de classificação morrem      | Julgavam o que "vale". Sem economia, não sobra pergunta que respondam.                                                                                                                   |
| 5   | Gamification só perde o trait                                   | Aposentar o módulo é outra conversa.                                                                                                                                                     |
| 6   | Os seis tipos do GitHub viram Interaction, 1:1                  | O registro é ledger de fato, não julgamento. Filtro de ruído vive na leitura, onde mudar de ideia é query e não re-backfill.                                                             |
| 7   | A seam reporta transição, não só criação                        | Sem isso `pr_merged` nunca dispara ao vivo (ver _Achados_).                                                                                                                              |
| 8   | Única transição: `merged: false → true`                         | É a única que muda o significado e mantém o ator correto.                                                                                                                                |
| 9   | Match por `actor_id`, com login como fallback; ambíguo descarta | `actor_id` é exato em 5 dos 6 tipos. Commit ao vivo não tem id — limitação do payload do GitHub, não bug.                                                                                |
| 10  | Adoção na conexão, em fila                                      | Espelha o `ReconcileOrphanEntries` do `contents`, que já faz isso para dev.to.                                                                                                           |
| 11  | Desconectar não esconde o passado                               | Para a ingestão de coisa nova; o que já foi registrado permanece. Mantém a leitura plana pelo `user_id`.                                                                                 |
| 12  | Enum podado: só casos com produtor                              | Caso sem produtor é promessa que o próximo leitor lê como feature. Foi o que gerou esta spec.                                                                                            |
| 13  | `unique (external_ref)` global e auto-descritivo                | Três caminhos escrevem o mesmo fato e precisam convergir numa linha só.                                                                                                                  |
| 14  | O backfill do lake passa a emitir                               | Fecha o caminho mudo (ver _Achados_).                                                                                                                                                    |
| 15  | A origem responde pelo detalhe (`ContributionDetail`)           | Título, contexto e link viviam copiados no `metadata`; a cópia envelhecia sozinha e a tela do admin lia `metadata.repo`, vazio em toda linha de conteúdo.                                |
| 16  | `metadata` morre e vira coluna `attributed_by`                  | Tirando as três cópias sobrava uma chave enumerada. Coluna é consultável e indexável; jsonb com um valor dentro é cerimônia — e some do allowlist do `NoLooseArrayCastsTest`.            |
| 17  | `matched_by` vira `AttributionMethod`, vocabulário do domínio   | `actor_id`/`login` são detalhe do GitHub e `activity` não importa integration. O enum ordena por risco e dá nome ao caso do dev.to (`Owned`), que antes aparecia como travessão.         |

### Refinamento do formato de `external_ref`

Padrão único, com o repo sempre presente para blindar colisão entre repositórios:

```text
  devto:article:4099087
  github:pr_opened:he4rt/heartdevs.com:474
  github:pr_merged:he4rt/heartdevs.com:474
  github:review:he4rt/heartdevs.com:8471023
  github:review_comment:he4rt/heartdevs.com:2210394
  github:comment:he4rt/heartdevs.com:1180022
  github:commit:he4rt/heartdevs.com:a1b2c3d
  github:issue:he4rt/heartdevs.com:312
```

---

## Achados que motivaram decisões

Três coisas encontradas no código durante o desenho. Cada uma inverteria o plano se ignorada.

### 1. A seam é muda no merge

```php
// integration-github/src/Contributions/RecordContribution.php
$recorded = GithubContribution::query()->updateOrCreate(
    ['repo' => ..., 'type' => ..., 'external_ref' => ...],   // pr + "pr:474"
    [... 'metadata' => $contribution->metadata],             // merged: true/false
);

// Só emite na criação. [...] evita recompensas duplicadas em listeners downstream.
if ($emit && $recorded->wasRecentlyCreated) {
    event(new GithubContributionRecorded($recorded));
}
```

PR aberto cria a linha e emite. PR mergeado **atualiza a mesma linha** e não emite. Um listener
pendurado em `GithubContributionRecorded` jamais veria um merge. O motivo escrito no comentário
— evitar recompensa duplicada — deixou de existir junto com as coins.

### 2. O backfill do lake escreve sem avisar

```php
// integration-github/src/Backfill/BackfillRepository.php:271
$recorded = $this->recorder->execute($contribution);
//                                            ↑ emit tem default false
```

O backfill é justamente o mecanismo que recupera webhook perdido. Hoje ele encheria o lake
deixando o Tracking cego para tudo que trouxesse.

### 3. Commit ao vivo não tem `actor_id`

```php
// integration-github/src/Webhook/ProjectGithubEvent.php  — push()
actorId: null,
```

Não é descuido: o payload de `push` do GitHub manda `author.username`/`name`/`email` por commit,
nunca o id numérico. Isso explica exatamente as **623 linhas sem `actor_id`** — todas do tipo
`commit`, todas vindas do webhook. O backfill via API traz o id.

### 4 · `connected_at` não distingue pessoa de ETL

Descoberto ao rodar a projeção: filtrar identidade só por `connected_at IS NOT NULL AND
disconnected_at IS NULL` devolve **2.677 das 2.678** identidades GitHub, porque a ETL também
preenche essa data. Só **121** têm credencial — as demais nunca foram conectadas por ninguém.

```text
external_identities WHERE provider='github'
  total                                  2.678
  passa no filtro por data               2.677   ← inclui artefato de ETL
  tem access_token                         121   ← pessoa que clicou em conectar
  tem connected_by                          15
```

Com o filtro por data, a projeção reivindicava **4.579** linhas em vez de 3.972 — 607 atribuídas
a identidades que ninguém conectou. O critério virou um scope no dono do conceito,
`ExternalIdentity::scopeActivelyConnected()`, exigindo credencial além das datas.

Nota: o `isConnected()` do próprio model continua olhando só as datas. Não foi alterado porque
outros consumidores dependem dele, mas os dois conceitos convivem e merecem reconciliação.

---

## Arquitetura alvo

```text
  ┌─────────────────────────────────────────────────────────────────────────┐
  │ INTEGRATION                                                             │
  │                                                                         │
  │  GitHub ──webhook──► ProjectGithubEvent ──┐                             │
  │                                           ├──► RecordContribution       │
  │  GitHub API ──backfill──► BackfillRepository┘         │                 │
  │                              (emit: true agora)       ▼                 │
  │                                              github_contributions       │
  │                                              (lake: cru, TUDO,          │
  │                                               conectado ou não)         │
  │                                                       │                 │
  │                             ┌─────────────────────────┴──────────┐      │
  │                             ▼                                    ▼      │
  │                  GithubContributionRecorded      GithubContributionChanged
  │                        (criou)                      (merged: false→true)│
  │                             │                                    │      │
  │                             └────────────┬───────────────────────┘      │
  │                                          ▼                              │
  │                              TrackGithubContribution                    │
  │                                 resolve identidade:                     │
  │                                   actor_id → login → DESCARTA           │
  └──────────────────────────────────────────┬──────────────────────────────┘
                                             │  (integration pode importar domain)
  ┌──────────────────────────────────────────▼──────────────────────────────┐
  │ DOMAIN — activity/Tracking                                              │
  │                                                                         │
  │   TrackActivity ──► interactions                                        │
  │                       external_identity_id  NOT NULL   ← verdade        │
  │                       user_id               NOT NULL   ← derivado       │
  │                       type · external_ref (unique)                      │
  │                       hidden_at · hidden_by                             │
  │                       source (morph) · metadata · occurred_at           │
  └─────────────────────────────────────────────────────────────────────────┘

  ExternalIdentityConnected ──► AdoptGithubContributions (queued)
  AccountsMerged            ──► ReassignInteractionOwnership
  ArticlePublished          ──► TrackContentContribution (adaptado)
```

### Regra de dependência respeitada

`activity/src` importa hoje de Identity, Community, Gamification, Economy, Moderation e Contents
— todos domain, nenhum `Integration*`. O produtor do GitHub **mora em `integration-github/`**,
que pode importar domain. É o inverso do `TrackContentContribution`, que fica em `activity`
porque `contents` é domain.

### Ciclo de vida da visibilidade

```text
  [visible] ──ocultar (admin)──► [hidden] ──mostrar (admin)──► [visible]
      ▲
      └── entra assim por padrão
```

---

## Passos de implementação

### Passo 1 — Schema de `interactions`

**Contexto.** A tabela carrega a economia inteira e prende a interação a `characters`. As 72
linhas existentes resolvem 100% pelo caminho `character → user → identidade devto ativa`, então
a migração é in-place: adiciona nullable, preenche, aplica NOT NULL, remove o antigo. Arquivo
via `php artisan make:migration --module=activity`, com colunas de data em variante `Tz`.

```php
// ANTES
$table->foreignUuid('character_id')->constrained('characters');
$table->string('provider');
$table->string('value_tier');
$table->integer('coins_min');
$table->integer('coins_max');
$table->integer('coins_awarded')->nullable();
$table->integer('xp_awarded')->nullable();
$table->string('status')->default('pending');
$table->timestampTz('reviewed_at')->nullable();
$table->unique(['provider', 'external_ref'], 'uniq_interactions_provider_ref');

// DEPOIS
$table->foreignUuid('external_identity_id')->constrained('external_identities');
$table->foreignUuid('user_id')->constrained('users');
$table->timestampTz('hidden_at')->nullable();
$table->foreignUuid('hidden_by')->nullable()->constrained('users');
$table->unique('external_ref', 'uniq_interactions_external_ref');
$table->index(['user_id', 'occurred_at'], 'idx_interactions_user_occurred');
$table->index('external_identity_id', 'idx_interactions_identity');
```

```gherkin
Feature: Migração do schema de interações
    Para que a interação pertença à identidade conectada
    Como mantenedor da plataforma
    Quero migrar as linhas existentes sem perda

    Scenario: As 72 linhas de dev.to são preservadas
        Given existem 72 interações ligadas a 5 characters
        And cada character pertence a um user com identidade devto ativa
        When a migration roda
        Then as 72 linhas continuam existindo
        And cada uma tem external_identity_id da identidade devto do autor
        And cada uma tem user_id igual ao dono daquela identidade
        And nenhuma tem hidden_at preenchido

    Scenario: Linha em pending vira visível
        Given uma interação com status "pending"
        When a migration roda
        Then a coluna status não existe mais
        And hidden_at é nulo
        And a interação é considerada visível

    Scenario: O rollback devolve o schema anterior
        Given a migration foi aplicada
        When o rollback roda
        Then as colunas de economia voltam a existir
        And as 72 linhas voltam a apontar para seus characters
```

### Passo 2 — Poda do enum e remoção da economia

**Contexto.** `ActivityType` tem 13 casos e 1 produtor. `ValueTier`, `ActivityStatus`,
`ClassifyActivity`, `CalculateReward` e `config/activity-tracking.php` existem só para calcular
coins e XP. `peer_review` passa a `review`, espelhando o `ContributionType` do lake.

```php
// ANTES — 13 casos, 1 produtor
enum ActivityType: string
{
    case Article = 'article';
    case PrMerged = 'pr_merged';
    case Mentoring = 'mentoring';
    case SquadProject = 'squad_project';
    case Referral = 'referral';
    case PeerReview = 'peer_review';
    case CallParticipation = 'call_participation';
    case ForumDebate = 'forum_debate';
    case ContentShare = 'content_share';
    case Engagement = 'engagement';
    case RepoStar = 'repo_star';
    case Message = 'message';
    case Voice = 'voice';
}

// DEPOIS — 8 casos, 8 produtores
enum ActivityType: string implements HasColor, HasDescription, HasLabel
{
    case Article = 'article';              // devto
    case PrOpened = 'pr_opened';           // github
    case PrMerged = 'pr_merged';           // github
    case Review = 'review';                // github
    case ReviewComment = 'review_comment'; // github
    case Comment = 'comment';              // github
    case Commit = 'commit';                // github
    case Issue = 'issue';                  // github
}
```

Removidos por inteiro: `Enums/ValueTier.php`, `Enums/ActivityStatus.php`,
`Actions/ClassifyActivity.php`, `Actions/CalculateReward.php`, `config/activity-tracking.php`.

`ActivityType` passa a implementar os contratos Filament exigidos pelo repo (`HasLabel`,
`HasColor`, `HasDescription`), com `match` sobre todos os casos e sem braço `default`. É enum
não-ordenado — cores semânticas distintas por caso, sem rampa até `danger`.

```gherkin
Feature: Enum de tipo de atividade
    Para que o código não prometa o que não entrega
    Como mantenedor
    Quero que todo caso do enum tenha um produtor

    Scenario: Todo caso tem produtor
        Given o enum ActivityType
        When eu listo seus casos
        Then cada caso corresponde a um produtor existente no código

    Scenario: Os contratos Filament estão implementados
        Given qualquer caso de ActivityType
        When eu chamo getLabel, getColor e getDescription
        Then cada um retorna valor sem cair em braço default
```

### Passo 3 — Contrato novo do Tracking

**Contexto.** `TrackActivityDTO` recebe `characterId` e `provider`; `TrackActivity` credita
carteira e incrementa XP. Ambos deixam de fazer sentido. O provider passa a vir da identidade,
e o `user_id` é derivado dentro da action — nunca parâmetro, senão vira segunda fonte da verdade.

```php
// ANTES
final readonly class TrackActivityDTO
{
    public function __construct(
        public string $characterId,
        public ActivityType $type,
        public IdentityProvider $provider,
        public DateTimeImmutable $occurredAt,
        public ?string $externalRef = null,
        ...
    ) {}
}

// DEPOIS
final readonly class TrackActivityDTO
{
    public function __construct(
        public string $externalIdentityId,
        public ActivityType $type,
        public DateTimeImmutable $occurredAt,
        public string $externalRef,
        public ?string $sourceType = null,
        public ?string $sourceId = null,
        /** @var array<string, mixed>|null */
        public ?array $metadata = null,
    ) {}
}
```

```php
// ANTES — TrackActivity::handle()
$classification = $this->classifyActivity->handle($dto->type);
$interaction = Interaction::query()->create([... 'value_tier' => ..., 'coins_min' => ...]);

if ($classification['status'] === ActivityStatus::AutoApproved) {
    $reward = $this->calculateReward->handle($interaction);
    $character = Character::query()->findOrFail($dto->characterId);
    $wallet = $character->getOrCreateWallet();
    resolve(Credit::class)->handle(new CreditDTO(...));
    $character->increment('experience', $reward['xp_awarded']);
}

// DEPOIS
$identity = ExternalIdentity::query()->findOrFail($dto->externalIdentityId);

$interaction = Interaction::query()->firstOrCreate(
    ['external_ref' => $dto->externalRef],
    [
        'external_identity_id' => $identity->id,
        'user_id' => $identity->model_id,   // derivado, nunca parâmetro
        'type' => $dto->type,
        'source_type' => $dto->sourceType,
        'source_id' => $dto->sourceId,
        'metadata' => $dto->metadata,
        'occurred_at' => $dto->occurredAt,
    ],
);
```

`externalRef` deixa de ser opcional: a idempotência depende dele.

```gherkin
Feature: Registro de contribuição
    Para que o hub tenha um registro canônico por fato
    Como produtor de qualquer fonte
    Quero registrar sem duplicar e sem tocar em economia

    Scenario: Registro cria a interação e deriva o dono
        Given uma identidade externa conectada de um usuário
        When registro uma contribuição com external_ref inédito
        Then existe uma interação com aquele external_ref
        And o external_identity_id aponta para a identidade
        And o user_id é o dono daquela identidade
        And nenhuma carteira foi creditada
        And nenhum XP foi incrementado

    Scenario: Registro repetido é no-op
        Given uma interação já registrada com external_ref "github:pr_merged:he4rt/api:474"
        When registro o mesmo external_ref de novo
        Then continua existindo exatamente uma interação com aquele ref
        And nenhum evento de criação é disparado outra vez

    Scenario: Dois fatos do mesmo PR coexistem
        Given o PR 474 foi aberto e depois mergeado
        When ambos são registrados
        Then existe uma interação pr_opened
        And existe uma interação pr_merged
        And as duas apontam para a mesma identidade
```

### Passo 4 — Visibilidade no lugar da aprovação

**Contexto.** `ApproveInteraction` existe para liberar recompensa e `RejectInteraction` para
negá-la. Sem economia, o par vira ocultar/mostrar no perfil. A interação entra visível.

```php
// ANTES — ApproveInteraction: trava, calcula, credita, incrementa XP, muda status
// DEPOIS
final readonly class HideInteraction
{
    public function handle(Interaction $interaction, User $actor): Interaction
    {
        $interaction->update([
            'hidden_at' => now(),
            'hidden_by' => $actor->id,
        ]);

        return $interaction->fresh();
    }
}
```

`InteractionApproved` é removido; `InteractionTracked` permanece.

```gherkin
Feature: Curadoria de visibilidade
    Para que o perfil não exiba o que não deve
    Como moderador
    Quero ocultar uma contribuição pontualmente

    Scenario: Contribuição nasce visível
        Given uma contribuição recém registrada
        Then hidden_at é nulo

    Scenario: Ocultar registra quem e quando
        Given uma contribuição visível
        When um moderador a oculta
        Then hidden_at é preenchido
        And hidden_by é o moderador

    Scenario: Mostrar limpa a ocultação
        Given uma contribuição oculta
        When um moderador a torna visível
        Then hidden_at é nulo
        And hidden_by é nulo
```

### Passo 5 — Trait sai do Character e vai para o User

**Contexto.** Com `user_id` na tabela, a relação natural passa a ser do `User`. É o último laço
entre Tracking e gamification.

```php
// ANTES — gamification/src/Character/Models/Character.php
use He4rt\Activity\Tracking\Concerns\HasInteractions;
final class Character extends Model
{
    use HasInteractions;

// DEPOIS — identity/src/User/Models/User.php
use He4rt\Activity\Tracking\Concerns\HasInteractions;
final class User extends Authenticatable
{
    use HasInteractions;
```

```gherkin
Feature: Interações pertencem ao usuário
    Scenario: O usuário lista as próprias contribuições
        Given um usuário com 3 contribuições registradas
        When acesso a relação de interações do usuário
        Then recebo 3 registros

    Scenario: Character não conhece mais interações
        Given um Character
        Then ele não expõe relação de interações
```

### Passo 6 — Consistência do `user_id` no merge de contas

**Contexto.** `MergeAccountsAction` repõe `external_identities.model_id` em massa, o que deixaria
o `user_id` denormalizado velho. O `contents`/`activity` já têm o padrão para isso
(`ReassignTimelineOwnership`). Na mesma passagem, corrigir o `AccountsMerged` disparado **duas
vezes**, linhas 28 e 30 — hoje inofensivo porque os listeners são idempotentes, mas é bug.

```php
// ANTES — identity/src/Auth/Actions/MergeAccountsAction.php
event(new AccountsMerged($oldUser->id, $currentUser->id));

event(new AccountsMerged($oldUser->id, $currentUser->id));

// DEPOIS
event(new AccountsMerged($oldUser->id, $currentUser->id));
```

```php
// NOVO — activity/src/Tracking/Listeners/ReassignInteractionOwnership.php
Interaction::query()
    ->where('user_id', $event->mergedId)
    ->update(['user_id' => $event->survivorId]);
```

```gherkin
Feature: Merge de contas não deixa dono velho
    Scenario: Interações seguem o sobrevivente
        Given o usuário A tem 5 contribuições
        And o usuário A é mergeado no usuário B
        Then as 5 contribuições têm user_id igual a B
        And o external_identity_id de cada uma não muda

    Scenario: O evento de merge dispara uma vez
        Given dois usuários prestes a serem mergeados
        When o merge executa
        Then AccountsMerged é disparado exatamente uma vez
```

### Passo 7 — dev.to adaptado ao contrato novo

**Contexto.** `TrackContentContribution` resolve `$entry->author?->character`. Passa a resolver a
identidade externa do autor no provider correspondente. Autor sem identidade ativa naquele
provider deixa de gerar interação — mesma regra do GitHub.

```php
// ANTES
$character = $entry->author?->character;
if ($character === null) { Log::info(...); return; }

$this->trackActivity->handle(new TrackActivityDTO(
    characterId: (string) $character->id,
    type: ActivityType::Article,
    provider: $identityProvider,
    ...
));

// DEPOIS
$identity = $entry->author?->providers()
    ->where('provider', $identityProvider)
    ->whereNotNull('connected_at')
    ->whereNull('disconnected_at')
    ->first();

if ($identity === null) { return; }

$this->trackActivity->handle(new TrackActivityDTO(
    externalIdentityId: $identity->id,
    type: ActivityType::Article,
    occurredAt: $entry->published_at->toDateTimeImmutable(),
    externalRef: sprintf('devto:article:%s', $entry->external_id),
    sourceType: 'content_entry',
    sourceId: $entry->id,
));
```

```gherkin
Feature: Artigo publicado vira contribuição
    Scenario: Autor com identidade devto ativa
        Given um artigo publicado por autor com identidade devto conectada
        When o evento ArticlePublished é processado
        Then existe uma interação article ligada àquela identidade

    Scenario: Autor sem identidade ativa é ignorado
        Given um artigo cujo autor não tem identidade devto conectada
        When o evento ArticlePublished é processado
        Then nenhuma interação é criada
```

### Passo 8 — A seam passa a reportar transição, e o backfill a emitir

**Contexto.** É o achado 1 combinado com o achado 2. Sem os dois, `pr_merged` nunca dispara ao
vivo e todo backfill do lake fica invisível para o Tracking. O docbloco de
`GithubContributionRecorded` ainda fala em "award coins/xp" e precisa ser reescrito.

```php
// ANTES — RecordContribution::execute()
if ($emit && $recorded->wasRecentlyCreated) {
    event(new GithubContributionRecorded($recorded));
}

// DEPOIS
$mergedNow = !$recorded->wasRecentlyCreated
    && $this->justMerged($recorded);

if ($emit && $recorded->wasRecentlyCreated) {
    event(new GithubContributionRecorded($recorded));
} elseif ($emit && $mergedNow) {
    event(new GithubContributionChanged($recorded));
}
```

```php
// ANTES — BackfillRepository:271
$recorded = $this->recorder->execute($contribution);

// DEPOIS
$recorded = $this->recorder->execute($contribution, emit: true);
```

A transição é detectada pelo `merged` que era falso e passou a verdadeiro. `synchronize`
(que mexe em `additions`/`deletions` a cada push), `issue` fechada e mudança de `state` de
review **não** emitem.

```gherkin
Feature: A seam reporta o que muda
    Scenario: PR aberto emite criação
        Given nenhum registro para o PR 474
        When chega o webhook de abertura
        Then GithubContributionRecorded é disparado

    Scenario: PR mergeado emite transição
        Given o PR 474 registrado com merged falso
        When chega o webhook com merged verdadeiro
        Then GithubContributionChanged é disparado
        And GithubContributionRecorded não é disparado

    Scenario: Push num PR aberto não emite nada
        Given o PR 474 registrado e ainda não mergeado
        When chega o webhook de synchronize alterando additions e deletions
        Then nenhum evento é disparado

    Scenario: Backfill do lake avisa o Tracking
        Given uma contribuição inédita vinda da API
        When o backfill a registra
        Then GithubContributionRecorded é disparado
```

### Passo 9 — Resolução de identidade e produtor do GitHub

**Contexto.** Mora em `integration-github/`, porque domain não pode importar integration. A
resolução é a peça sensível: `actor_id` é exato, login é fallback obrigatório para commit ao
vivo, e ambiguidade nunca vira palpite.

```php
// NOVO — integration-github/src/Contributions/ResolveContributorIdentity.php
public function handle(GithubContribution $contribution): ?ExternalIdentity
{
    $byActorId = $contribution->actor_id !== null
        ? $this->activeGithubIdentities()
            ->where('external_account_id', (string) $contribution->actor_id)
            ->first()
        : null;

    if ($byActorId !== null) {
        return $byActorId;
    }

    $candidates = $this->activeGithubIdentities()
        ->whereRaw("lower(metadata->>'username') = ?", [mb_strtolower($contribution->actor_login)])
        ->get();

    return $candidates->count() === 1 ? $candidates->first() : null;
}
```

O produtor grava a origem do match, para o pedaço frágil ser auditável e promovível depois:

```php
metadata: [
    'matched_by' => $matchedBy,        // 'actor_id' | 'login'
    'repo' => $contribution->repo,
    'lake_ref' => $contribution->external_ref,
],
```

Mapa de tipos: `commit → Commit`, `review → Review`, `review_comment → ReviewComment`,
`comment → Comment`, `issue → Issue`, `pr → PrOpened`, e a transição de merge → `PrMerged`.

`Relation::morphMap` do `IntegrationGithubServiceProvider` ganha
`'github_contribution' => GithubContribution::class`, para o morph `source` apontar para o lake.

```gherkin
Feature: Resolução do contribuidor
    Scenario: Match exato por actor_id
        Given uma identidade github conectada com external_account_id "31713982"
        And uma contribuição com actor_id 31713982
        When o produtor a processa
        Then a interação é criada para aquela identidade
        And metadata.matched_by é "actor_id"

    Scenario: Commit ao vivo casa por login
        Given uma identidade github conectada com username "fulano"
        And um commit com actor_id nulo e actor_login "Fulano"
        When o produtor o processa
        Then a interação é criada para aquela identidade
        And metadata.matched_by é "login"

    Scenario: Login ambíguo é descartado
        Given duas identidades github conectadas com o mesmo username
        And uma contribuição com actor_id nulo casando aquele login
        When o produtor a processa
        Then nenhuma interação é criada

    Scenario: Contribuidor sem conta conectada é descartado
        Given uma contribuição cujo ator não tem identidade github conectada
        When o produtor a processa
        Then nenhuma interação é criada
        And a linha permanece em github_contributions

    Scenario: Identidade desconectada não recebe contribuição nova
        Given uma identidade github com disconnected_at preenchido
        And uma contribuição nova daquele ator
        When o produtor a processa
        Then nenhuma interação é criada
        And as interações antigas daquela identidade continuam visíveis
```

### Passo 10 — Adoção na conexão

**Contexto.** "Descarta na entrada" só é aceitável porque o lake guarda tudo e a conexão resgata.
O `contents` já faz isso via `ReconcileOrphanEntries`. Diferença: aqui vai em fila, porque
`ExternalIdentityConnected` dispara a **cada** login OAuth (o `AttachProviderToUser` faz
`updateOrCreate` e emite sem condicional), e a varredura do lake não pode segurar o callback.

```php
// NOVO — integration-github/src/Contributions/Jobs/AdoptGithubContributions.php
final class AdoptGithubContributions implements ShouldQueue
{
    public function handle(TrackGithubContribution $producer): void
    {
        if ($this->identity->provider !== IdentityProvider::GitHub) {
            return;
        }

        GithubContribution::query()
            ->where(fn ($q) => $q
                ->where('actor_id', $this->identity->external_account_id)
                ->orWhereRaw('lower(actor_login) = ?', [$this->login()]))
            ->chunkById(500, fn ($rows) => $rows->each($producer->adopt(...)));
    }
}
```

```gherkin
Feature: Conectar o GitHub resgata o passado
    Scenario: Contribuições anteriores são adotadas
        Given 40 contribuições no lake do ator "fulano"
        And o usuário ainda não conectou o GitHub
        When o usuário conecta a conta
        Then um job de adoção é enfileirado
        And após processado existem 40 interações para aquele usuário

    Scenario: Login seguinte não duplica
        Given um usuário com 40 interações já adotadas
        When o usuário faz login OAuth de novo
        Then o job roda outra vez
        And continuam existindo 40 interações

    Scenario: Conectar outro provider não dispara adoção do GitHub
        Given um usuário conectando o Twitch
        When o evento de conexão é processado
        Then nenhum job de adoção do GitHub é enfileirado
```

### Passo 11 — Comando de projeção do que já existe

**Contexto.** As 3.972 linhas reivindicáveis já estão no lake e não são "recently created", então
nenhum evento as alcança. Comando one-off, idempotente, para rodar na subida.

```text
php artisan github:project-contributions [--dry-run]

  lake: 7.974 linhas
   ├─ 3.972 reivindicáveis por 73 identidades conectadas  ──► interactions
   │     dos quais 501 casam só por login (todos commit)
   └─ 4.002 sem identidade conectada                      ──► permanecem só no lake
```

```gherkin
Feature: Projeção do histórico
    Scenario: Projeta só o que tem identidade conectada
        Given 7.974 contribuições no lake
        And 73 identidades github conectadas
        When executo o comando de projeção
        Then são criadas interações apenas para as contribuições reivindicáveis
        And as demais permanecem apenas no lake

    Scenario: Rodar duas vezes não duplica
        Given o comando já foi executado
        When executo o comando de novo
        Then a quantidade de interações não muda

    Scenario: Dry-run não escreve
        Given o lake populado
        When executo o comando com --dry-run
        Then nenhuma interação é criada
        And o relatório informa quantas seriam
```

### Passo 12 — Tela no admin

**Contexto.** A curadoria decidida no passo 4 precisa de onde ser chamada, e os 3.972 registros
precisam de onde ser conferidos. Sem isso, `HideInteraction` nasce sem chamador — o mesmo padrão
de promessa morta que esta spec remove. Filament v5, seguindo a divisão
`Schemas/`/`Tables/`/`Pages/` usada no `panel-admin`.

Colunas: usuário, tipo, origem do match, repo, ocorrido em, visível. Filtros: tipo, provider da
identidade, oculto/visível, origem do match. Ações: ocultar e mostrar.

```gherkin
Feature: Painel de contribuições
    Scenario: Listagem exibe as contribuições
        Given interações registradas
        When um administrador abre o recurso
        Then vê a listagem com usuário, tipo e data

    Scenario: Ocultar pela tabela
        Given uma contribuição visível na listagem
        When o administrador executa a ação de ocultar
        Then a contribuição passa a constar como oculta
        And o registro guarda quem ocultou

    Scenario: Filtrar por origem do match
        Given contribuições casadas por actor_id e por login
        When filtro por origem "login"
        Then vejo apenas as casadas por login
```

---

## Riscos

| Risco                                               | Mitigação                                                                                                                                                                                                |
| --------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 501 das 3.972 casam só por login, mutável no GitHub | `metadata.matched_by` grava a origem; o painel filtra por ela; `metadata.username` da identidade é reescrito a cada login OAuth, então o drift se autocorrige. Ambiguidade descarta em vez de adivinhar. |
| `ExternalIdentityConnected` dispara a cada login    | Job em fila; `firstOrCreate` por `external_ref` torna a repetição um no-op barato.                                                                                                                       |
| `emit: true` no backfill pode gerar rajada          | Só linha nova emite (`wasRecentlyCreated`); o lake já está populado. Listeners em fila.                                                                                                                  |
| `user_id` denormalizado divergir                    | Derivado dentro da action, nunca parâmetro; listener no `AccountsMerged` realinha.                                                                                                                       |

## Dívida assumida

- **Renomear `Interaction` para `Contribution`** (tabela, model, DTO, action, evento, trait).
  O nome deixou de descrever a coisa: um `commit` não é interação. O prefixo do lake
  (`GithubContribution`) já desambigua. Fica para o PR seguinte, com a tabela ainda pequena.
- **`disconnect` mora num Livewire em `app/`**, com `update` cru e sem evento. Não bloqueia esta
  spec porque desconexão não altera interação, mas é lógica de identidade fora do módulo.
- **`actor_id` nulo em commit ao vivo** é limitação do payload. Um passe futuro pode promover
  `matched_by: login` para `actor_id` quando o backfill trouxer o id.

## Verificação

- `vendor/bin/pint --dirty --format agent`
- PHPStan nos módulos tocados
- Testes dos módulos `activity`, `integration-github`, `identity` e `panel-admin`
