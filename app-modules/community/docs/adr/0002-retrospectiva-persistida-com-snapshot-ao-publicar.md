---
type: adr
title: 'Retrospectiva persistida: operador cura, snapshot ao publicar'
module: community
status: accepted
date: 2026-07-19
author: Clintonrocha98
related:
    spec: community/2026-07-19-retrospectiva-multi-fonte
    adr: community/0001-retrospectiva-multi-fonte-via-contrato-de-source
---

# ADR-0002: Retrospectiva persistida, operador cura e snapshot ao publicar

**Status:** Accepted
**Date:** 2026-07-19
**Deciders:** Clintonrocha98

## Contexto

Hoje a retrospectiva é um read model ao vivo, filtrado pelo visitante via URL, recomputado a cada
request e a cada mudança de filtro. Queremos que ela vire uma **peça editorial**: o operador
(Marketing/Admin) monta e publica; o visitante só assiste ao resultado curado. Isso exige persistir a
edição e decidir o que acontece com o dado ao publicar.

## Decisão

**Operador cura, visitante assiste.** A edição publicada é fixa (estilo "Wrapped"): sem filtros na
página pública. Os antigos filtros do visitante viram **configuração editorial** guardada na edição.

**Período por datas livres, sem `Cadence`.** A edição guarda `since`/`until` diretos. O contrato
`collect(Period)` já aceita qualquer período, então a entidade fica tão flexível quanto ele. Os presets
("semana", "mês", "tudo") viram açúcar de UI que preenche as datas; a identidade da edição vem do
`title`.

**Snapshot ao publicar = `SourceResult[]` crus congelados + curadoria separada.**

```
DRAFT      collect() AO VIVO (operador ve dado fresco enquanto cura)
PUBLICAR   congela SourceResult[] no snapshot -> NUMEROS ficam fixos
PUBLICA    view = compoe(snapshot congelado, deck_config)   [cacheavel, sem query nas fontes]
```

- O snapshot guarda os `SourceResult` crus (números fixos no publish). A curadoria (ordem, on/off,
  exclusions, textos) mora separada, na própria edição (`deck_config`).
- A view pública compõe `snapshot + deck_config` a cada render (barato: JSON em memória, cacheável).
- Editar uma edição publicada (mexer ordem/texto/exclusion) **re-deriva do snapshot congelado**, então os
  números não "andam" sem querer. Recomputar só acontece num refresh explícito de dado.

**Publicar roda como job na fila.** As tabelas de origem são grandes (`messages` ~2GB em prod, e as demais
equivalentes ou maiores), então computar o snapshot de todas as fontes sobre o período pode estourar o
timeout do request num range grande (retro anual). O publish despacha um job
(`status = publishing` -> computa -> `published`); o draft cacheia o `collect()` enquanto o operador cura.
O custo é proporcional à janela de datas, não ao total, porque há índice na coluna de tempo
(`sent_at`/`occurred_at`); as agregações rodam em SQL, nunca carregando linhas em PHP.

## Considered options

- **Sem snapshot, sempre recomputar** — rejeitado: página pública cara e edição publicada não seria
  imutável (o dado da fonte muda/some depois).
- **Snapshot do deck final composto (pós-curadoria)** — rejeitado: mais simples de renderizar, mas editar
  uma retro publicada exigiria despublicar -> draft ao vivo -> republicar, e republicar recoleta dado
  fresco, fazendo um ajuste de texto mudar todos os números sem querer (footgun editorial).
- **Cadence (Weekly/Monthly/Annual)** — rejeitado: rígido para o fluxo editorial real; a flexibilidade
  de datas livres cobre qualquer recorte.

## Consequências

- Entidade `Retrospective` (tabela `community_retrospectives`) com `deck_config` e `snapshot` como VOs
  tipados via cast (jsonb), `status` enum com contratos Filament, colunas `timestamptz`, sem `tenant_id`.
- O admin da **Fase 2** é CRUD Filament **completo em capacidade** (toggles de fonte, repeater de ordem,
  select de exclusions, textos, publicar). Feio, mas produz e publica um deck curado de verdade. O Deck
  Builder da **Fase 3** é puro upgrade de UX (drag-drop, preview ao vivo, inspector), zero capacidade
  nova: se nunca vier, a feature funciona 100%.
- Preview do admin e página pública **compartilham o mesmo render path** (o compositor
  `snapshot + config`), então "ver rascunho" bate com o publicado.
- **Divergência consciente da spec-base:** ela era toda "per tenant" e centrada em `Cadence`. Aqui não há
  tenancy (removida no #413) e o período é datas livres.

## Notas de implementação (Fase 2)

Detalhes que emergiram na implementação, dentro do que foi decidido acima:

- **`FrozenSlide` em vez de registro `kind -> classe`.** O snapshot reidrata cada slide como um `Slide`
  genérico (`FrozenSlide`, dono no `community`, carrega `kind` + props). Um registro `kind -> classe`
  exigiria o domínio `community` conhecer as classes de slide de `integration-github` (Domain -> Integration,
  proibido). Como a renderização é por convenção `kind -> Blade` sobre `toArray()`, o contrato `Slide`
  basta — o tipo concreto do slide só importa na produção do dado (no `collect()`), não no consumo do
  snapshot.
- **`label()` no contrato `RetrospectiveSource`.** A identidade da fonte (key + label) virou estática para
  o CRUD listar/ordenar as fontes sem coletar dado. Antes o label era hardcoded dentro do `collect()`.
- **Exclusions recompilam; ordem/on-off re-derivam.** Ordem e on/off (fonte/slide) são curadoria de
  apresentação: aplicadas na composição (`ComposeDeck`) sobre o snapshot congelado, baratas. Exclusions
  mexem no DADO (entram no `SourceFilters` do `collect`, ADR-0001), então alterá-las só reflete numa nova
  publicação (recompila o snapshot) — não são reaplicadas na composição.
- **`publishing` é um estado transitório do enum.** `RetrospectiveStatus` tem `draft | publishing |
  published`; `publishing` cobre a janela em que o job congela o snapshot.
- **Preview autenticado sem rota de login web.** O portal não tem rota `login` nomeada, então o guard do
  preview vive no `mount()` do componente (`abort_unless(auth()->check(), 403)`), não num middleware `auth`
  que dependeria da rota inexistente.
