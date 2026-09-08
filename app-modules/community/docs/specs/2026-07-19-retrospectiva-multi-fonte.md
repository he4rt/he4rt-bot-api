---
type: spec
title: 'Retrospectiva multi-fonte'
module: community
status: draft
date: 2026-07-19
author: Clintonrocha98
related:
    adr: community/0001-retrospectiva-multi-fonte-via-contrato-de-source
---

# Spec: Retrospectiva multi-fonte

## Contexto

A retrospectiva de hoje vive inteira no `portal` (`He4rt\Portal\Retrospective\CommunityRetrospective`),
lê **direto** de `GithubContribution` e monta um read model **ao vivo** a cada request, filtrado por
query params na URL. Ela só conhece o GitHub, não é persistida e não tem noção de edição publicada.

O projeto detém atividade de outras plataformas (Discord no módulo `activity`, WhatsApp no
`integration-whatsapp`, Twitch no `integration-twitch`), mas nada disso aparece na retrospectiva, e não
existe nenhuma abstração de "fonte".

Esta spec desenha a evolução: a retrospectiva vira uma peça editorial **multi-fonte**, onde adicionar
ou remover uma fonte é trivial, o operador cura o conteúdo e a edição publicada é imutável.

Partimos de uma spec-base que foi destrinchada contra o projeto de hoje. Três pontos dela foram
**conscientemente divergidos** (ver seção própria).

## Goals

- **Multi-fonte plugável.** Adicionar uma fonte = criar 1 classe no módulo dono do dado + 1 linha de
  tag. O `portal` nunca muda.
- **Peça editorial curada.** O operador (Marketing/Admin) monta a edição; o visitante só assiste.
- **Publicação imutável e barata.** Ao publicar, o dado é congelado num snapshot; a página pública lê o
  congelado, sem tocar as fontes.

## Non-goals

- **Dedup de identidade entre plataformas.** Não há vínculo de usuário cross-plataforma; cada fonte é a
  sua própria verdade. Pessoas são contadas por fonte.
- **Soma cruzada de métricas no cover.** PR, mensagem, sub e minuto em call são incomensuráveis; o cover
  mostra chips por fonte, sem total unificado.
- **Cadence (Weekly/Monthly/Annual).** A edição usa datas livres `since`/`until`.
- **Multi-tenancy.** A tenancy foi removida no #413.

## Arquitetura

### Contrato (Strategy, dono em `community`)

```
interface RetrospectiveSource {
    key(): string
    label(): string                       // identidade estática (CRUD lista/ordena sem coletar)
    collect(Period, SourceFilters): SourceResult
}

SourceResult = HeadlineMetrics (Metric[] chips, por fonte) + Slide[] (DTO tipado por kind)
interface Slide { kind(): string }        // classes concretas no modulo que emite

// Fase 3 (IMPLEMENTADO), adicionado por ISP sem mexer no contrato acima:
interface CuratableSource {
    slideCatalog(): SlideDescriptor[]              // estatico, sem tocar o banco
    exclusionCandidates(Period): ExclusionCandidate[]  // varre dado: escopado + LIMIT + cache
}
```

`GithubSource` e `DiscordSource` implementam as duas interfaces. Uma fonte que **não** implementa
`CuratableSource` continua entrando no deck e no Deck Builder com ordem e on/off, só sem catálogo de
slides nem picker de exclusions — o painel checa `instanceof`.

Cada fonte emite **dado, nunca markup**. O `portal` mapeia `kind` (ex.: `github.repos`,
`discord.voice_board`) para um componente Blade. As fontes são descobertas por **tagged services**
(tag `retrospective.source`); o orquestrador resolve o iterador e monta um mapa `key -> source`.

### Fluxo

```
DRAFT      cada fonte.collect() AO VIVO -> operador cura -> preview
PUBLICAR   congela SourceResult[] no snapshot (numeros ficam fixos)
PUBLICA    view = compoe(snapshot congelado, deck_config)   [cacheavel, sem query nas fontes]
           cover (chips agregados) -> bloco github.* -> bloco discord.* -> ... -> closing
           visitante ASSISTE, sem filtros
```

Duas camadas de curadoria: filtro que **mexe no dado** (`hideBots`, exclusions) entra no `collect()`
para o headline sair consistente; curadoria de **apresentação** (ordem, on/off, título, max itens) fica
na orquestração, nunca na fonte.

Daí a regra editorial que a UI diz em voz alta: **exclusion exige republicar** (recompila o snapshot),
ordem e on/off não (re-derivam na composição). Os refs são namespaced por prefixo (`pr:`, `issue:`,
`actor:`, `message:`, `member:`) porque `DeckConfig::allExclusions()` achata tudo numa lista só — cada
fonte reconhece apenas o que emite.

### Mapa de módulos

| Módulo                       | Papel                                                                               | Depende de                              |
| ---------------------------- | ----------------------------------------------------------------------------------- | --------------------------------------- |
| `community` (domínio)        | contrato, entidade `Retrospective`, VOs `Period`/`SourceFilters`, interface `Slide` | nada novo                               |
| `integration-github`         | `GithubSource`                                                                      | community (Integration -> Domain)       |
| `activity` (domínio)         | `DiscordSource`                                                                     | community (Domain -> Domain, sem ciclo) |
| `integration-whatsapp`       | `WhatsAppSource` (posterior)                                                        | community (Integration -> Domain)       |
| `portal` (apresentação)      | orquestrador + blades                                                               | community + fontes                      |
| `panel-admin` (apresentação) | List/Create + Deck Builder (F3 substituiu o CRUD da F2)                             | community                               |

`DiscordSource` fica no `activity` (e não no `integration-discord`) porque o dado da retrospectiva
(`Voice`, `Message`, `Reaction`, `MembershipEvent`) é modelado no `activity`. A regra é "a fonte mora no
módulo dono do dado", não "toda fonte mora em integration".

### Entidade

```
Retrospective   (community · tabela community_retrospectives)
  id            uuid
  title         string              identidade editorial ("Retro de Junho 2026")
  since         timestamptz
  until         timestamptz
  status        RetrospectiveStatus (enum draft|published, contratos Filament)
  cover_title   string|null         copia editorial (so texto, sem os numeros)
  cover_intro   text|null
  closing_text  text|null
  hide_bots     bool  (default true)
  deck_config   DeckConfig VO  (jsonb)   ordem, on/off por fonte, per-slide, exclusions, textos
  snapshot      RetrospectiveSnapshot VO|null (jsonb)   SourceResult[] congelado; null enquanto draft
  published_at  timestamptz|null
  created_at / updated_at  timestamptz
```

`deck_config` e `snapshot` são VOs tipados via cast dedicado (regra de typed-json do repo), nunca `array`
solto. Ao reidratar o snapshot, cada slide volta como um `FrozenSlide` (Slide genérico do `community`
que carrega `kind` + props) em vez de reinstanciar a classe concreta: o domínio `community` não pode
importar as classes de slide de `integration-github` (Domain -> Integration é proibido), e a renderização
por convenção `kind -> Blade` só precisa do contrato `Slide`, não do tipo original.

### Slides e shell

`cover` e `closing` são shell compartilhada (source-agnostic); o cover agrega os `Metric[]` de todas as
fontes. Os demais slides pertencem a um bloco de fonte:

- GitHub (refatorado 1:1 do que existe hoje): `github.panorama`, `github.repos`, `github.highlights`,
  `github.core`, `github.community`.
- Discord (novo, 5 slides): `discord.voice_board`, `discord.messages`, `discord.new_members`,
  `discord.reactions`, `discord.top_message`.

A cópia editorial (`cover_title`/`cover_intro`/`closing_text`) guarda **só texto**; números, avatares e
período são dado computado, renderizados à parte. A atribuição fixa "gerado a partir da GitHub API"
existente vira genérica ("gerado a partir de GitHub, Discord...").

### Escala e performance

As tabelas de origem são grandes em prod (`messages` ~2GB; `voice_messages`, `activity_reactions`,
`membership_events` equivalentes ou maiores). Regras que caem disso:

- **`collect()` agrega em SQL, nunca carrega linhas em PHP.** Cada slide é uma query GROUP BY/COUNT/SUM
  escopada pelo período com LIMIT, devolvendo resultado pequeno. `SourceFilters` (hideBots/exclusions)
  entra no WHERE.
- **Custo proporcional à janela, não ao total**, porque há índice na coluna de tempo. Os índices já
  existem (recriados em `2026_07_15_001258_recreate_activity_post_tenancy_indexes`): `messages(sent_at)`,
  `messages(channel_id, sent_at)`, `voice_messages(occurred_at) WHERE state='joined'`,
  `membership_events(kind, occurred_at)`, `activity_reactions(emoji_key, created_at)`.
- **Publicar roda como job na fila** (todas as fontes sobre o período de uma vez pode ser pesado num
  range anual; job evita timeout e dá UX uniforme "publicando..." -> "publicado").
- **Draft cacheia o `collect()`** por (fonte, período, filtros) enquanto o operador cura, com "atualizar
  dado" para invalidar; senão cada ajuste re-roda queries pesadas.
- **Filtrar sempre por `occurred_at`/`sent_at` (tempo do evento), nunca `created_at`** (dados de prod têm
  `created_at` uniforme de backfill, ex.: `2026-05-02`; usar `created_at` daria um retro errado).

### Notas de dados (Discord)

- `hideBots` usa `source_kind = 'bot'` (populado em prod); ressalva: linhas históricas podem vir null.
- `messages` não tem nome de canal, só `channel_id`; o nome resolve no `portal` (via `integration-discord`).
- `content` pode conter spam/scam/moderação; exibir conteúdo em público exige curadoria/exclusion.
- `activity_reactions` são agregados sem tempo de evento próprio; escopar por período junta com o
  `sent_at` da mensagem reagida ou usa o timestamp do agregado (resolver na implementação do slide).

## Divergências da spec-base

1. **Sem soma cruzada no cover** (era "cross-source unified totals"). Chips por fonte; sem dedup de
   pessoas. Ver ADR-0001.
2. **Sem `Cadence`** (era o conceito central de período). Datas livres; presets viram açúcar de UI; a
   identidade da edição vem do `title`. Ver ADR-0002.
3. **Sem `tenant_id`** (a spec era toda "per tenant"). Forçada pela remoção da tenancy no #413.

## Entrega faseada (visão)

Branch de integração `feature/retrospective-multi-source` -> `4.x`, com um PR por fase (cada um verde por
si só):

- **PR-A (Fase 1):** contrato + DTOs + registro por tag + `GithubSource` (refactor 1:1) + `DiscordSource`
  (5 slides) + orquestração. Página ao vivo, multi-fonte.
- **PR-B (Fase 2):** entidade `Retrospective` + datas/textos + snapshot ao publicar + view pública lê
  snapshot + **CRUD Filament completo em capacidade** (feio, mas cura tudo).
- **PR-C (Fase 3):** Deck Builder 3 colunas + exclusions com UI. Puro upgrade de UX, zero capacidade
  nova (se nunca vier, a feature funciona 100%). O builder **substitui** a página de edição (ocupa a chave
  `edit` com rota `/{record}/deck`), e a curadoria entra por `CuratableSource`. Única exceção ao "zero
  capacidade nova": exclusion, que era campo morto desde a Fase 1, passou a valer de verdade dentro do
  `collect()` — completar a Fase 1, não inventar. Ver ADR-0002 do `panel-admin`.
- **PR aditivo (após PR-A):** `WhatsAppSource` (parsing do lake + regra de privacidade: sem telefone).

## Trade-offs / Alternativas consideradas

- **Formato do contrato** (option A normalizado / B payload por fonte / C híbrido): escolhido C. Ver
  ADR-0001.
- **Conteúdo do snapshot** (deck final composto vs SourceResults crus + config separada): escolhido
  crus mais config, por segurança editorial (ajustar curadoria não recomputa números). Ver ADR-0002.
- **Período** (cadence vs datas livres): escolhido datas livres. Ver ADR-0002.
- **Descoberta** (tagged services vs config central vs auto-scan): escolhido tagged services.
