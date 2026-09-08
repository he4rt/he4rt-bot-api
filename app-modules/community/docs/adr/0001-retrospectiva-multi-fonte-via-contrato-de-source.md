---
type: adr
title: 'Retrospectiva multi-fonte via contrato de Source em community'
module: community
status: accepted
date: 2026-07-19
author: Clintonrocha98
related:
    spec: community/2026-07-19-retrospectiva-multi-fonte
---

# ADR-0001: Retrospectiva multi-fonte via contrato de Source em `community`

**Status:** Accepted
**Date:** 2026-07-19
**Deciders:** Clintonrocha98

## Contexto

A retrospectiva precisa agregar várias plataformas (GitHub, Discord, WhatsApp, Twitch) num deck único,
mas a regra dura do repo é **domínio nunca importa de Integration**. Além disso, o dado de cada
plataforma tem valor distinto: um PR mergeado e um sub da Twitch não são "+1" equivalentes.

Não há vínculo de identidade entre plataformas, então **cada fonte é a sua própria verdade** e uma Source
é estruturalmente **cega** às irmãs (não sabe que as outras existem).

## Decisão

O **contrato** (Strategy) e a entidade/lifecycle da retrospectiva vivem no módulo de domínio `community`;
cada **implementação** vive no módulo dono do dado.

```
interface RetrospectiveSource {
    key(): string
    collect(Period, SourceFilters): SourceResult
}
```

- **`SourceResult` = `HeadlineMetrics` (envelope de chips por fonte) + `Slide[]` (lista ordenada).**
  Cada `Slide` é um DTO tipado por `kind` (`github.repos`, `discord.voice_board`...), com classe
  concreta no módulo que emite; `community` só define a interface `Slide`. A fonte emite **dado, nunca
  markup**; o `portal` mapeia `kind -> Blade`. É isso que deixa um módulo de Integration produzir saída
  de retrospectiva sem "possuir a apresentação".
- **`HeadlineMetrics` é uma lista de `Metric` (valor + dica de formato), por fonte.** Não há soma
  cruzada: o cover justapõe os chips de cada fonte. Qualquer total realmente cruzado só poderia nascer na
  orquestração (`portal`), nunca dentro de uma Source, porque a Source é cega às irmãs.
- **Descoberta por tagged services** (tag `retrospective.source`). Adicionar plataforma = nova classe +
  1 linha de tag no módulo dela; o `portal` nunca muda.
- **Interface mínima, cresce por ISP.** `collect()` é o único método na Fase 1. A curadoria
  (`slideCatalog()`, `exclusionCandidates()`) entra como uma interface **segregada** (`CuratableSource`),
  implementada só por fontes curáveis; o admin checa `instanceof`. Cresce por adição de interface, não por
  mutação do contrato. **Implementado na Fase 3** (`GithubSource` e `DiscordSource`), com o
  `RetrospectiveSource` intacto — ver ADR-0002 do `panel-admin` para o consumidor.
- **Duas camadas de curadoria.** Filtro que mexe no dado (`hideBots`, exclusions) entra no `collect()`
  via `SourceFilters`, para o headline sair consistente com os slides. Curadoria de apresentação (ordem,
  on/off, título, max itens) fica na orquestração.

### Como a curadoria ficou (Fase 3)

`SourceFilters::excludes()` existia desde a Fase 1 e o `deck_config` já gravava os refs, mas **nenhuma
fonte chamava o método**: exclusion era campo morto. A Fase 3 fechou o buraco sem inventar capacidade:

- **Cada fonte aplica os refs dentro do `collect()`, antes de qualquer agregação.** O que é excluído sai
  dos slides **e dos números** — é a leitura literal de "filtro que mexe no dado" acima, não uma nova
  camada. Consequência editorial: alterar exclusion **exige republicar** (recompila o snapshot); ordem e
  on/off não, esses re-derivam.
- **Ref namespaced por prefixo** (`pr:`, `issue:`, `actor:` no GitHub; `message:`, `member:` no Discord).
  `DeckConfig::allExclusions()` achata tudo numa lista só antes de virar `SourceFilters`, então o prefixo
  distinto é o que faz cada fonte reconhecer só o que emite, sem disputa de ref nem tabela de tradução.
- **`slideCatalog()` é estático**, resolvido sem tocar o banco. **`exclusionCandidates()` varre dado**,
  então é obrigação da implementação escopar pelo `Period`, aplicar `LIMIT` (30 no GitHub, 20 no Discord)
  e cachear por `(fonte, período)`.

## Considered options

- **Contrato + impl ambos em `community`** — rejeitado: `community` importaria `integration-*`, violando a
  regra de dependência.
- **Tudo no `portal`** — rejeitado: a entidade, o lifecycle e as regras são domínio, não podem morar na
  apresentação.
- **Option A: DTO normalizado único** — rejeitado: achata o valor distinto de cada plataforma (PR
  mergeado vira "+1" igual a um sub).
- **Option B: payload próprio por fonte, orquestrador conhece cada tipo** — rejeitado: acopla o
  orquestrador a toda fonte concreta e perde o cover uniforme.
- **Option C (escolhida): híbrido `SourceResult` = `HeadlineMetrics` + `Slide[]`.**

## Consequências

- `integration-github` passa a expor `GithubSource` (normaliza seu dado); o boundary estica um pouco, mas
  segue devolvendo **dado, não Blade** (templates ficam no `portal`).
- `DiscordSource` mora no `activity` (domínio), não no `integration-discord`, porque o dado da
  retrospectiva (`Voice`/`Message`/`Reaction`/`MembershipEvent`) é modelado no `activity`. Dependência
  `activity -> community` (domínio -> domínio, sem ciclo). Como domínio não importa de Integration, se um
  slide precisar de nome de canal (que vive no `integration-discord`), a resolução usa o que o `activity`
  já denormaliza (ex.: `Voice.channel_name`) ou acontece no `portal`, nunca na `DiscordSource`.
- Slides compõem-se como **blocos de fonte** (todos os slides de uma fonte contíguos) entre `cover` e
  `closing` compartilhados. Pessoas contadas por fonte, sem dedup no MVP.
- **Divergência consciente da spec-base:** ela previa "cross-source unified totals" no cover; aqui o
  cover é chips por fonte, sem soma. Motivo: sem vínculo de identidade a soma de pessoas mente, e as
  unidades (PR, msg, sub, minuto) são incomensuráveis.
