---
type: spec
title: 'Módulo contents — acervo canônico de conteúdo, começando por artigos'
module: contents
status: draft
date: 2026-08-19
author: danielhe4rt
---

# Módulo `contents` — acervo canônico de conteúdo

## Contexto

### Hoje um artigo não existe

Não há entidade `Article`, `Post` ou `Content` em lugar nenhum do repositório. Um artigo do
dev.to é **uma linha em `interactions`** (`type='article'`, `provider='devto'`) com todo o dado
específico enterrado no `metadata` jsonb:

```php
metadata: [
    'devto_article_id' => 123,
    'title' => '…',
    'url' => '…',
    'tags' => [...],
    'engagement_snapshot' => ['reactions' => 0, 'comments' => 0, 'bookmarks' => 0],
]
```

Isso tem cinco consequências diretas:

1. **`Interaction` é registro de contribuição, não de conteúdo.** Ela responde "quem publicou
   algo, quando, valendo quanto" — não "o que é esse artigo".
2. **`source_type`/`source_id` da `Interaction` estão `NULL`.** O slot morph que existe
   exatamente para apontar para a coisa que originou a contribuição nunca foi preenchido.
3. **O corpo nunca é guardado** — só a URL. O acervo não é pesquisável nem legível internamente.
4. **Consultar por tag ou título é full scan** no jsonb, sem índice.
5. **`metadata => 'array'` é cast solto**, banido pela diretriz `06-typed-json-casts`.

### O `SyncDevToArticles` descarta a maior parte do acervo

O comando atual tem três pontos de queda silenciosa, em série:

```
artigo do feed da org
   ├─ sem user.username      → skipped
   ├─ sem ExternalIdentity   → skipped   ("author not linked")
   └─ User sem Character     → skipped
```

O segundo é o dominante: com zero identidades `devto` conectadas, **todo** artigo caía no
`skipped`. O PR #505 (API key como método de conexão) destravou a possibilidade de conectar,
mas não muda o desenho: quem não conectou continua invisível, e o artigo dessa pessoa é
descartado em vez de guardado.

### A prova de que isso dói: o portal já contornou

A página `/artigos` do portal — em construção — **não lê o banco**. Ela chama a API do dev.to
em tempo de request, com cache de 30 minutos, e o motivo está escrito no próprio código:

```php
/**
 * Read model do acervo de artigos publicados na organização do dev.to.
 *
 * A fonte é a API, não o banco: `devto:sync-articles` só persiste artigos de
 * autores com identidade He4rt vinculada, então a tabela cobre um subconjunto —
 * enquanto esta página precisa creditar todo mundo que escreveu pela org.
 */
final class ArticleFeed
```

Ou seja: existe uma página institucional cuja disponibilidade depende de um terceiro estar de
pé, porque o banco não é confiável como fonte. Esse comentário é o requisito desta spec dito
em outras palavras — **o acervo precisa existir independentemente de quem linkou conta**.

### A janela

A tabela `interactions` está **vazia** (0 linhas, 0 `devto`, 0 `article`). Não há backfill,
dual-write nem período de paridade a coordenar. É a única janela em que trocar o desenho sai de
graça.

---

## Objetivos

- Um **registro canônico** de conteúdo, dono do próprio ciclo de vida, independente de
  gamificação e de identidade resolvida.
- Um **contrato por provedor de artigos**, com o denominador comum que se sustenta em dev.to,
  Hashnode, Medium e RSS — não só no dev.to.
- **Nenhum artigo descartado**: autor não vinculado gera registro órfão, adotado depois.
- Preencher o slot `source` da `Interaction`, ligando contribuição ao conteúdo que a originou.
- Uma extensão para **vídeo** (YouTube etc.) que seja uma tabela nova e um alias de morph — não
  uma reescrita das consultas existentes.

## Não-objetivos

- **UI.** Nenhum Filament resource, nenhuma página. O portal continua lendo a API neste ciclo;
  migrá-lo para o banco é o passo seguinte.
- **Histórico de engajamento.** Guardamos o valor corrente, sobrescrito. Série temporal foi
  avaliada e recusada (ver _Trade-offs_).
- **Vídeo.** A modelagem acomoda, a implementação não entra.
- **Tela de aprovação de `Interaction`.** Continua faltando — ver _Consequências_.
- **Moderação de conteúdo externo.** Fora do escopo; o acervo espelha o provedor.

---

## Arquitetura

### Fronteiras e sentido das dependências

```
   integration-devto  ──────────┐  implementa o contrato
   (transport + credencial)     │
                                ▼
                        ┌───────────────────┐
   identity ───────────▶│     contents      │  dono do registro canônico
   (User, ExternalId.)  │  (domínio, novo)  │  não conhece HTTP nem dev.to
                        └─────────┬─────────┘
                                  │ ArticlePublished (evento)
                                  ▼
                        ┌───────────────────┐
                        │     activity      │  dono da contribuição
                        │  Interaction/XP   │  ActivityType::Article já é dele
                        └───────────────────┘
```

O `contents` **não conhece o dev.to**. O `integration-devto` conhece o `contents`, como manda a
regra do `CONTEXT-MAP` (_Integration pode depender de Domain; Domain nunca de Integration_).
O listener mora no `activity` porque `ActivityType::Article` já é vocabulário dele — quem emite
o evento não deve saber quem escuta.

### Delegated types

Espelha o padrão que já existe em `activity/Timeline` (`Timeline` → `postable` →
`PostEntry` | `ModerationEvent`): **uma raiz carrega o único morph**, e tudo que quiser se
relacionar com conteúdo aponta para a raiz, não para o subtipo.

```
                    content_entries
                    (ContentEntry — a raiz)
                           │
                    contentable morph
                     ┌─────┴─────┐
                     ▼           ▼
             content_articles   content_videos
                (Article)        (Video, depois)
```

A regra da fronteira: **uma coluna sobe para a raiz quando alguma consulta que ignora o tipo
precisa dela** — ordenar o feed por data, filtrar por provedor, montar o card, ranquear autor.
O subtipo fica só com o que é genuinamente exclusivo do tipo.

### Schema

```
content_entries                                   content_articles
──────────────────────────────────────            ──────────────────────────────
 id                uuid    pk                      id                uuid  pk
 contentable_type  string  ┐ morph                 description       text  null
 contentable_id    uuid    ┘                       reading_time_minutes int null
 author_id         uuid    null → identity_users   canonical_url     text  null
 author_handle     string                          body_markdown     text  null
 provider          string  ┐ unique                body_html         text  null
 external_id       string  ┘                       source_edited_at  timestamptz null
 title             string                          timestampsTz()
 url               text
 thumbnail_url     text    null
 tags              jsonb          ◄── VO tipado
 published_at      timestamptz
 reactions_count   int     null   ┐
 comments_count    int     null   ├─ null ≠ 0
 saves_count       int     null   ┘
 metrics_synced_at timestamptz null
 timestampsTz()

ÍNDICES
  unique (provider, external_id)                     ← idempotência do ingest
  index  (contentable_type, contentable_id)          ← resolução do morph
  index  (published_at desc)                         ← ordenação do feed
  index  (provider, author_handle) WHERE author_id IS NULL   ← fila de órfãos (parcial)
```

Três pontos que não são óbvios:

- **`author_id` é `User`, nunca `Character`.** Autoria é fato; gamificação é consequência. O
  `contents` jamais escreve em `Character`.
- **`author_handle` é gravado sempre**, inclusive quando o autor é resolvido. É a chave da
  reconciliação e a única memória de quem escreveu quando não há conta.
- **Contadores nuláveis.** `null` significa "este provedor não mede isso" (RSS não tem reação
  nenhuma); `0` significa "medido, e é zero". Colapsar os dois envenena qualquer regra de
  recompensa por engajamento.

### Vocabulário de provedor

`ContentProvider` é um enum novo, com **apenas `DevTo`** — nenhum caso especulativo. Implementa
`HasLabel`/`HasColor`/`HasDescription` conforme a diretriz `07-enum-filament-contracts`.

Não reusamos `IdentityProvider` porque ele é o vocabulário de _conexões de conta_ (28 casos,
espelhando os connection types do Discord: Xbox, PlayStation, Crunchyroll…). Um feed RSS não é
conta de ninguém, e "conecte sua conta RSS" não é uma frase que deva ser possível de escrever.

A ponte é explícita e parcial:

```php
public function toIdentityProvider(): ?IdentityProvider
```

`null` significa "este provedor não tem dono resolvível" — o conteúdo entra no acervo, mas não
vira `Interaction`, porque não existe conta para creditar. Como `TrackActivityDTO::$provider` é
tipado como `IdentityProvider`, essa ponte é obrigatória e é onde a impossibilidade fica
representada em vez de improvisada.

> **Regra de extensão:** todo provedor novo entra neste enum e no `CONTEXT.md` do módulo. Está
> registrado lá para não virar convenção oral.

### Contratos e capacidades

Um contrato base mais capacidades opcionais, em vez de uma interface gorda com métodos que
metade dos provedores implementaria devolvendo array vazio:

```php
interface ArticleProvider
{
    public function provider(): ContentProvider;
}

interface DiscoversBySource extends ArticleProvider
{
    /** @return iterable<ArticleDTO> */
    public function fetchFromSource(): iterable;
}

interface DiscoversByIdentity extends ArticleProvider
{
    /** @return iterable<ArticleDTO> */
    public function fetchForIdentity(ExternalIdentity $identity): iterable;
}

interface HydratesDetail extends ArticleProvider
{
    public function fetchDetail(ArticleDTO $shallow): ArticleDTO;
}
```

Os dois modos de descoberta produzem resultados diferentes e ambos são necessários:

```
DESCOBERTA POR FONTE                      DESCOBERTA POR IDENTIDADE
GET /articles?username=he4rt              GET /articles/me/published
──────────────────────────────            ─────────────────────────────
✔ acha artigo de quem NÃO conectou        ✖ só de quem já conectou
   └─ é o que produz o órfão                 └─ nunca gera órfão
✖ só o que saiu pela organização          ✔ pega o blog pessoal também
✔ existe em todo provedor (RSS É isso)    ✖ Medium/RSS não têm equivalente
```

Sem descoberta por fonte, a reconciliação nunca tem o que reconciliar. Sem descoberta por
identidade, a API key que o PR #505 destravou continua sem uso prático.

`DevToArticleProvider` implementa as três; um futuro `RssArticleProvider` implementa só
`DiscoversBySource`. O orquestrador pergunta com `instanceof` — a ausência de capacidade fica no
sistema de tipos, não num comentário.

### Registro

```
ContentsServiceProvider::register()
    └─ singleton ArticleProviderRegistry

IntegrationDevToServiceProvider::boot()
    └─ resolve(ArticleProviderRegistry)->register(new DevToArticleProvider(...))
```

O comando itera o registry e nunca cita um provedor pelo nome. Provedor novo entra sem tocar
uma linha do `contents`.

### O contrato de dados

```php
final readonly class ArticleDTO
{
    // OBRIGATÓRIO — o que todo provedor de artigo tem
    public string $externalId;
    public string $authorHandle;
    public string $title;
    public string $url;
    public DateTimeImmutable $publishedAt;

    // NULÁVEL — cada provedor preenche o que consegue
    public ?string $description;
    public ?string $thumbnailUrl;
    public ?string $canonicalUrl;
    public ?int $readingTimeMinutes;
    public ?string $bodyMarkdown;
    public ?string $bodyHtml;
    public ?DateTimeImmutable $sourceEditedAt;
    public ?ArticleEngagementDTO $engagement;

    /** @var list<string> */
    public array $tags;          // default [] — ausência não é ambiguidade

    public bool $detailHydrated; // ver "Hidratação sob demanda"
}
```

`ArticleEngagementDTO` carrega `?int $reactions`, `?int $comments`, `?int $saves` — nuláveis
pelo mesmo motivo das colunas.

O corpo é guardado **nos dois formatos**. O dev.to entrega ambos; um provedor RSS entrega só
HTML; um futuro provedor pode entregar só markdown. Guardar os dois evita conversão com perda e
evita amarrar a renderização a um único caminho.

### Hidratação sob demanda

A API do dev.to foi consultada ao vivo. A **listagem** já traz quase tudo:

```
JÁ VEM NA LISTAGEM                      SÓ NO DETALHE (GET /articles/{id})
──────────────────                      ─────────────────────────────────
id · title · url · description          body_markdown
canonical_url · cover_image             body_html
published_at · edited_at ◄──┐           reading_list_count (saves)
tag_list · reading_time_minutes │
public_reactions_count          │
comments_count · user{} · organization{}│
                                │
  edited_at permite pular o detalhe quando o artigo não mudou
```

O N+1 de hoje (um request por artigo, toda passada, sempre) só é necessário para o corpo e para
os saves. A decisão de hidratar é do `contents`, não do provedor — o provedor não tem acesso ao
banco e não deve ganhar:

```
para cada DTO raso vindo da listagem:
    existente = ContentEntry por (provider, external_id)
    precisa = existente === null
           || existente.article.source_edited_at != dto.sourceEditedAt

    dto = precisa && provider instanceof HydratesDetail
        ? provider->fetchDetail(dto)     // detailHydrated = true
        : dto                            // detailHydrated = false

    UpsertArticle->execute(provider, dto)
```

Em regime, com 200 artigos, isso sai de ~200 requests a cada 30 minutos para ~7.

> **Regra crítica:** um DTO com `detailHydrated = false` **não escreve** os campos de detalhe.
> O DTO raso carrega `bodyMarkdown = null` porque a listagem não traz corpo — não porque o
> artigo perdeu o corpo. Sem essa regra, cada re-sync apagaria o texto de todo o acervo.

### Ciclo de vida completo

```
 AUTOR                              SISTEMA
  │                                    │
  │  📝 publica no dev.to              │
  │                                    │
  │            ⏱ cron 30min ──────────►│  contents:sync-articles
  │                                    │  foreach (ArticleProviderRegistry)
  │                                    │
  │                                    │  DevToArticleProvider::fetchFromSource()
  │                                    │    GET /articles?username=he4rt
  │                                    │    ├─ novo ou edited_at mudou?
  │                                    │    │    └─ fetchDetail()  → corpo + saves
  │                                    │    └─ senão: DTO raso
  │                                    │
  │                                    │  UpsertArticle
  │                                    │   upsert por (provider, external_id)
  │                                    │   author_handle = 'fulano'
  │                                    │   author_id     = NULL      ◄── órfão
  │                                    │   validação: ✓
  │                                    │
  │                                    │  ✖ ArticlePublished não emitido
  │                                    │     (sem autor não há contribuição)
  │                                    │
  │  👆 conecta a API key do dev.to    │
  │ ─────────────────────────────────► │  ConnectApiKeyIdentity        (PR #505)
  │                                    │  AttachProviderToUser         (trilho OAuth)
  │                                    │     └─► ExternalIdentityConnected   ◄── EVENTO NOVO
  │                                    │
  │                                    │  contents: ReconcileOrphanEntries
  │                                    │   WHERE provider = devto
  │                                    │     AND author_handle = 'fulano'
  │                                    │     AND author_id IS NULL
  │                                    │   → adota 3 entries
  │                                    │   → ArticlePublished ×3
  │                                    │
  │                                    │  activity: TrackContentContribution
  │                                    │   user->character ?
  │                                    │    ├─ sim → TrackActivity
  │                                    │    │        type   = Article
  │                                    │    │        source = ContentEntry  ◄── slot antes
  │                                    │    │        status = Pending           sempre NULL
  │                                    │    └─ não → log, sem Interaction
  │                                    │            (a entry continua sendo dela)
  │                                    │
  │    "dev.to conectado"              │
  │ ◄──────────────────────────────────│
  │                                    │
  │    ┌──────────────────────────┐    │
  │    │ Ver meus artigos      ✖  │    │ ◄── não existe ainda (ciclo seguinte)
  │    └──────────────────────────┘    │
```

### O evento que falta no `identity`

O `identity` hoje emite **apenas** `AccountsMerged`. Não há sinal de que uma conexão nasceu —
e há dois caminhos que criam uma:

| Caminho | Ação                               | Módulo                              |
| ------- | ---------------------------------- | ----------------------------------- |
| OAuth   | `AttachProviderToUser::execute()`  | `identity/Auth/Actions`             |
| API key | `ConnectApiKeyIdentity::execute()` | `identity/ExternalIdentity/Actions` |

Ambos passam a emitir `ExternalIdentityConnected`. O evento é genérico de propósito: o GitHub e
o Twitch vão querer o mesmo gatilho de reconciliação.

### Estrutura de arquivos

```
app-modules/contents/                      app-modules/integration-devto/
├── CONTEXT.md      ◄── regra de extensão  └── src/Articles/
├── docs/specs/                                ├── DevToArticleProvider.php
├── database/migrations/                       └── DevToArticleMapper.php
│   ├── …_create_content_entries_table.php          implements DiscoversBySource,
│   └── …_create_content_articles_table.php                    DiscoversByIdentity,
├── src/                                                       HydratesDetail
│   ├── ContentsServiceProvider.php
│   ├── Enums/ContentProvider.php          app-modules/activity/
│   ├── Models/ContentEntry.php            └── src/Tracking/Listeners/
│   ├── Data/TagList.php                       └── TrackContentContribution.php
│   ├── Casts/AsTagList.php
│   └── Articles/                          app-modules/identity/
│       ├── Models/Article.php             └── src/ExternalIdentity/Events/
│       ├── Contracts/…                        └── ExternalIdentityConnected.php
│       ├── ArticleProviderRegistry.php
│       ├── DTOs/{ArticleDTO,ArticleEngagementDTO}.php
│       ├── Actions/{UpsertArticle,ReconcileOrphanEntries}.php
│       ├── Events/ArticlePublished.php
│       └── Console/SyncArticlesCommand.php
└── tests/
```

Aliases de morph: `content_entry` e `content_article` — prefixados para não colidir com o valor
`article` do `ActivityType`, que vive num espaço de nomes diferente mas usa a mesma palavra.

---

## O que muda no código existente

### `SyncDevToArticles` deixa de existir

**Antes** — um arquivo fazendo cinco coisas, com o dev.to hardcoded:

```php
#[Signature(signature: 'devto:sync-articles')]
class SyncDevToArticles extends Command
{
    private function processArticle(array $article): string
    {
        $devToUsername = $article['user']['username'] ?? null;
        if ($devToUsername === null) { return 'skipped'; }

        $externalIdentity = ExternalIdentity::query()
            ->where('provider', IdentityProvider::DevTo)
            ->where('metadata->username', $devToUsername)
            ->first();

        if ($externalIdentity === null) {
            Log::info('DevTo sync: author not linked', [...]);
            return 'skipped';                       // ← o artigo é perdido
        }

        $articleDetails = $this->apiClient->getArticle($article['id']);  // ← sempre, N+1

        $this->trackActivity->handle(new TrackActivityDTO(
            characterId: (string) $character->id,
            metadata: ['devto_article_id' => …, 'title' => …, 'url' => …],  // ← jsonb solto
        ));
    }
}
```

**Depois** — o comando não conhece provedor, e nada é descartado:

```php
#[Signature(signature: 'contents:sync-articles')]
final class SyncArticlesCommand extends Command
{
    public function handle(ArticleProviderRegistry $registry, UpsertArticle $upsert): int
    {
        foreach ($registry->all() as $provider) {
            if ($provider instanceof DiscoversBySource) {
                foreach ($provider->fetchFromSource() as $dto) {
                    $upsert->execute($provider->provider(), $this->hydrateIfStale($provider, $dto));
                }
            }

            if ($provider instanceof DiscoversByIdentity) {
                foreach ($this->connectedIdentitiesFor($provider) as $identity) {
                    foreach ($provider->fetchForIdentity($identity) as $dto) {
                        $upsert->execute($provider->provider(), $this->hydrateIfStale($provider, $dto));
                    }
                }
            }
        }

        return self::SUCCESS;
    }
}
```

### A `Interaction` ganha origem

**Antes** — dado do artigo duplicado no jsonb, morph vazio:

```php
'source_type' => null,
'source_id'   => null,
'metadata'    => ['devto_article_id' => 123, 'title' => '…', 'url' => '…', 'tags' => [...]],
```

**Depois** — uma verdade só, apontada:

```php
'source_type' => 'content_entry',
'source_id'   => $entry->id,
'metadata'    => null,          // o conteúdo mora no contents, não aqui
'external_ref' => 'devto:article:123',   // mantém a dedupe do TrackActivity
```

---

## Comportamento esperado

**Artigo de autor não vinculado**

- **Dado** um artigo no feed da organização cujo `user.username` não tem `ExternalIdentity`
- **Então** existe uma `ContentEntry` com `author_id = null` e `author_handle` preenchido
- **E** existe um `Article` com o corpo em markdown e HTML
- **E** nenhuma `Interaction` é criada
- **E** nenhum `ArticlePublished` é emitido

**Artigo de autor já vinculado**

- **Dado** um artigo cujo autor tem identidade `devto` conectada e `Character`
- **Então** a `ContentEntry` nasce com `author_id` preenchido
- **E** uma `Interaction` é criada com `source_type = 'content_entry'`, `source_id` da entry,
  `type = Article` e `status = Pending`

**Adoção na conexão**

- **Dado** três entries órfãs com `author_handle = 'fulano'` e provider `devto`
- **Quando** alguém conecta a identidade `devto` com `metadata.username = 'fulano'`
- **Então** as três recebem `author_id` daquele usuário
- **E** três `Interaction` são criadas, uma por entry

**Re-sync sem alteração**

- **Dado** uma entry cujo `source_edited_at` é igual ao `edited_at` da listagem
- **Quando** o sync roda de novo
- **Então** nenhum `GET /articles/{id}` é disparado para aquele artigo
- **E** só os contadores e o `metrics_synced_at` são atualizados
- **E** `body_markdown` e `body_html` permanecem intactos

**Re-sync após edição no provedor**

- **Dado** uma entry cujo `edited_at` na listagem avançou
- **Então** o detalhe é buscado e título, tags, corpo e `source_edited_at` são sobrescritos

**Provedor indisponível**

- **Dado** que o provedor lança exceção durante a descoberta
- **Então** a falha é registrada e o sync segue para o próximo provedor
- **E** nenhuma entry existente é apagada ou esvaziada

**Autor vinculado sem `Character`**

- **Dado** um autor com identidade conectada mas sem `Character`
- **Então** a entry recebe `author_id` normalmente
- **E** nenhuma `Interaction` é criada e nenhum `Character` é criado

**Idempotência**

- **Dado** que o sync roda duas vezes seguidas sem mudança no provedor
- **Então** o número de `ContentEntry`, `Article` e `Interaction` não muda

**Provedor sem identidade correspondente**

- **Dado** um `ContentProvider` cujo `toIdentityProvider()` devolve `null`
- **Então** a entry é criada e permanece sem autor
- **E** nenhuma `Interaction` é tentada

---

## Trade-offs e alternativas consideradas

### Delegated types, e não tabela única com discriminador

Tabela única com `kind` + jsonb tipado por tipo seria mais barata de escrever hoje e evitaria o
join. Perdemos tipagem real das colunas específicas e ganharíamos um jsonb por tipo — que é a
forma de dado que esta spec existe para eliminar. O repositório já usa delegated types em
`activity/Timeline`; seguir o padrão da casa vale mais que a economia de um join.

### Sem histórico de engajamento

Série temporal (uma linha por artigo por dia) daria curva de crescimento, "top da semana" e
detecção de artigo que estourou, a um custo de dezenas de milhares de linhas por ano. Foi
recusada em favor do valor corrente sobrescrito.

**Consequência aceita e explícita:** quando alguém quiser um gráfico de crescimento, a série
começa do zero naquele dia — dado passado não é recuperável. A migração é aditiva (uma tabela
nova alimentada pelo mesmo sync), então a porta não fica trancada, só não é aberta agora.

### Contrato mínimo, não contrato rico

Exigir todos os campos e preencher com valor neutro seria mais simples de consumir. Tornaria
"zero reações" indistinguível de "este provedor não mede reações" — o que quebra qualquer regra
de recompensa por engajamento no dia em que ela existir, silenciosamente e de forma difícil de
diagnosticar. Nulabilidade é o preço de conseguir dizer a verdade.

### `ContentProvider` próprio, não `IdentityProvider`

Reusar o enum existente eliminaria o mapeamento. Faria `rss` aparecer como opção em qualquer UI
que liste providers de identidade, e obrigaria `getCredentialsType()`/`supportedProviders()` a
ganhar braços sem sentido. O mapeamento parcial não é cerimônia — é onde "este conteúdo não tem
dono possível" fica escrito.

### `ActivitySourceContract` é substituído

Já existe `He4rt\Activity\Tracking\Contracts\ActivitySourceContract`
(`fetchActivities(): TrackActivityDTO[]`) com **zero implementações** — a primeira tentativa
desta mesma ideia, que nunca saiu do papel. Ele acopla o provedor ao vocabulário de recompensa
(`TrackActivityDTO`) em vez do vocabulário de conteúdo, que é justamente a inversão que esta
spec desfaz. Deve ser removido junto.

---

## Consequências

### O que esta entrega **não** resolve

**A `Interaction` continua nascendo `Pending` e não existe tela para aprová-la.**
`ActivityType::Article` é tier `high`, e `auto_approve_tiers` contém apenas `low` e `medium`.
A ação `ApproveInteraction` existe; a tela, não. Este é o mesmo aviso registrado no PR #505, e
ele permanece verdadeiro depois desta entrega: artigos passam a ser guardados corretamente, mas
o efeito visível para o membro continua zero até a tela existir.

### Dívidas e pendências criadas ou herdadas

- **`composer.json` da raiz declara `"he4rt/contents": ">=1"`**, violando a diretriz que exige o
  estilo `^1.0.0` para toda dependência intra-repo. Precisa ser corrigido junto com o próximo
  `composer update`.
- **A página `/artigos` do portal continua lendo a API.** Migrá-la para ler `content_entries`
  remove a dependência de tempo de request num terceiro e faz o `ArticleFeed` cair para uma
  query. É o candidato natural ao próximo ciclo, e o comentário que hoje justifica a API deixa
  de ser verdade assim que órfãos passarem a ser guardados.
- **`polling_interval_minutes` do `integration-devto` deixa de ser configuração morta** — o
  `ArticleFeed` já a usa como TTL de cache. O agendamento do `contents:sync-articles` segue em
  `everyThirtyMinutes()` fixo.
- **`tags` usa VO tipado com cast dedicado**, seguindo `06-typed-json-casts` e o precedente do
  ADR `profile/0002`. Não há tabela de tags; buscar por tag é `WHERE tags @> …`, adequado ao
  volume atual e revisável se virar filtro de primeira classe.
- **Sem `retry`/`timeout` nas chamadas do provedor** foi herdado do `DevToApiClient` e não é
  corrigido aqui; a falha é contida por provedor, mas não é resiliente.
