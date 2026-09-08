---
type: adr
title: 'Deck Builder da retrospectiva: 3 colunas, preview pelo render path real'
module: panel-admin
status: accepted
date: 2026-08-04
author: Clintonrocha98
related:
    spec: community/2026-07-19-retrospectiva-multi-fonte
    adr: community/0001-retrospectiva-multi-fonte-via-contrato-de-source
---

# ADR-0002: Deck Builder da retrospectiva

**Status:** Accepted
**Date:** 2026-08-04
**Deciders:** Clintonrocha98

## Contexto

A Fase 2 entregou um CRUD Filament completo **em capacidade**: dá para ordenar fontes, ligar e desligar
blocos, escrever os textos, listar exclusions e publicar. O que ele não dá é **noção do resultado**. O
operador edita um repeater de linhas e só descobre o que fez abrindo o preview em outra aba, sem relação
visual entre o campo que mexeu e o slide que mudou.

A Fase 3 é o upgrade de UX desse mesmo poder: montar o deck vendo o deck. Por definição (ADR-0002 do
`community`) ela não inventa capacidade nova; se nunca vier, a feature continua funcionando.

Ao implementar a curadoria apareceu um buraco herdado: `SourceFilters::excludes()` existia desde a Fase 1
e o `deck_config` já gravava os refs, mas **nenhuma fonte chamava o método**. Exclusion era campo morto.
Um picker em cima disso seria UI para um botão que não faz nada.

## Decisão

### O builder substitui a página de edição

A `EditRetrospective` (formulário Filament padrão) sai; entra uma `Page` de resource registrada na chave
`edit` com rota `/{record}/deck`. Manter a chave preserva o clique na tabela e o `getUrl('edit')`; trocar
a rota deixa a URL honesta. `List` e `Create` continuam padrão: criar uma edição é preencher título e
período, não montar deck.

Duas telas editando o mesmo `deck_config` seria duas fontes de verdade de curadoria, com risco de uma
sobrescrever a outra.

### Três colunas: estrutura, preview, inspector

```
┌──────────────────┬───────────────────────────┬───────────────────────┐
│ [Estrutura]      │ [Preview]                 │ [Inspector]           │
│ capa             │  deck embutido no DOM     │  formulário do que    │
│ blocos de fonte  │  (DeckPresentation ->     │  está selecionado     │
│   chips de slide │   ComposeDeck -> mesmas   │  4 modos              │
│ fecho            │   partials do portal)     │                       │
└──────────────────┴───────────────────────────┴───────────────────────┘
   seleciona     ──────►  pula até o slide          edita e salva
                          selecionado
```

O inspector é contextual, com quatro modos, e cada um escreve onde já se escrevia na Fase 2:

| Seleção       | Edita                                                      | Persiste em                             |
| ------------- | ---------------------------------------------------------- | --------------------------------------- |
| Capa          | título, período, ocultar bots, título e introdução da capa | colunas da edição                       |
| Bloco (fonte) | exibir, exclusions daquela fonte                           | `hidden_sources`, `exclusions`, `order` |
| Slide         | exibir                                                     | `hidden_slides`                         |
| Fecho         | mensagem de fecho                                          | coluna `closing_text`                   |

Nenhuma coluna nova, nenhuma migration: o `DeckConfig` da Fase 2 já tinha `hidden_slides` persistindo
sem UI que o editasse.

### O preview divide o render path com a página pública

Nada de reimplementar o deck dentro do painel. Preview que mente é pior que preview nenhum, e a garantia
de que ele não mente é dividir o caminho de render: mesmo `ComposeDeck`, mesmas partials, mesmas props.

> **Emendado em 2026-08-23 (supersede o iframe).** A decisão original era um `<iframe>` apontando para
> `/comunidade/retrospectiva/{id}/preview`. O isolamento do iframe cobrava caro: o builder não conseguia
> falar com o deck (não dá para levar o preview até o slide que o operador acabou de selecionar), e cada
> salvamento recarregava um documento inteiro.
>
> O deck passou a ser embutido no próprio DOM do builder. O que sustentava o medo de divergência foi
> substituído por garantias mais fortes que a fronteira do iframe:
>
> - `DeckPresentation` (no `portal`) é o **único** lugar que monta as props do deck. A página pública, o
>   preview em tela cheia e o builder passam os três por ele — não há um segundo caminho que possa
>   divergir.
> - O CSS do deck é inteiramente escopado sob `.retro` e as partials não usam Tailwind, então importá-lo
>   no painel não alcança o Filament nem é alcançado por ele. `.retro-embed` só troca o `position: fixed`
>   (que existe porque no portal o deck **é** a página) por um containing block local.
>
> Em troca, o builder ganhou controle real: selecionar um slide na coluna de estrutura leva o preview até
> ele (`retro-goto`), e o teclado deixa de ser sequestrado quando o foco está num campo do inspector.

Custo aceito: ao salvar, o deck é recomposto e recriado inteiro (a key carrega a versão), em vez de
atualizar o slide alterado no lugar.

### Reordenar por botões, não por drag

Primeiro corte com subir/desce. Drag and drop exigiria uma dependência de frontend nova (o SortableJS que
o Filament usa é interno, não é API pública) para ordenar entre 2 e 5 blocos. Fica como incremento
posterior, sem mexer no formato persistido.

### Curadoria entra por interface segregada

`CuratableSource` no `community`, com `slideCatalog()` e `exclusionCandidates(Period)`, implementada por
`GithubSource` e `DiscordSource`. O `RetrospectiveSource` não muda (ISP, previsto no ADR-0001 do
`community`). O builder checa `instanceof`: fonte que não cura aparece na timeline com ordem e on/off,
sem catálogo de slides nem picker, e o deck segue montando.

`slideCatalog()` é estático, resolvido sem tocar o banco. `exclusionCandidates()` varre dado, então é
obrigação da implementação manter a consulta escopada pelo período e com `LIMIT` (30 no GitHub, 20 no
Discord), com cache curto por `(fonte, período)`.

### On/off é por kind, não por instância de slide

`github.repos` rende um slide por repositório. O toggle esconde o bloco inteiro de repositórios. Ligar e
desligar repo a repo exigiria identidade estável por instância, que o snapshot congelado não carrega, e
seria capacidade nova (a Fase 3 não inventa capacidade).

### Ref de exclusion é namespaced por prefixo

`DeckConfig` guarda exclusions por fonte, mas `allExclusions()` achata tudo numa lista só antes de virar
`SourceFilters`. Com prefixo distinto por tipo de alvo (`pr:`, `issue:`, `actor:` no GitHub; `message:`,
`member:` no Discord) cada fonte reconhece só o que emite, sem disputa de ref e sem tabela de tradução.
No GitHub o ref de item é o próprio `external_ref` da linha.

### Exclusion passa a valer de verdade

Cada fonte aplica os refs dentro do `collect()`, antes de qualquer agregação: o que é excluído some dos
slides **e dos números**. Isso não é capacidade nova da Fase 3, é a Fase 1 sendo completada (o ADR-0001
do `community` já definia exclusion como filtro que mexe no dado).

Consequência editorial que a UI precisa dizer em voz alta: mexer em exclusion exige **republicar**, porque
recompila o snapshot. Ordem e on/off não, esses re-derivam.

## Alternativas consideradas

- **Manter o Edit e adicionar o builder como página extra** — rejeitado: duas telas escrevendo o mesmo
  `deck_config`.
- **Renderizar o deck dentro do painel (sem iframe)** — rejeitado no primeiro corte, **aceito na emenda
  de 2026-08-23**: não duplica o render path enquanto `DeckPresentation` for o único lugar que monta as
  props, e o CSS do portal é escopado o bastante para conviver com o painel.
- **Editar cada slide (título, máximo de itens, ordenação interna)** — rejeitado: é capacidade nova,
  contraria o contrato da fase e obrigaria o `ComposeDeck` a conhecer semântica de cada kind.
- **Drag and drop já no primeiro corte** — adiado: dependência nova para ordenar poucos blocos.

## Consequências

- O painel passa a depender do contrato de curadoria do `community`, não das fontes concretas. Fonte nova
  ganha builder de graça ao implementar `CuratableSource`.
- Candidatos a exclusion ficam até 5 minutos velhos depois de um backfill (cache por período).
- O picker mostra o topo do recorte, não a tabela inteira. Esconder algo fora desse teto não é possível
  pela UI (o formato persistido aceita qualquer ref, então o caminho existe se um dia for preciso).
- Recompor o deck a cada salvamento custa uma coleta ao vivo em rascunho. Aceitável para o volume de uso
  (uma edição por mês, um operador).
- O painel carrega o CSS do deck nesta página. Enquanto o design system do deck viver sob `.retro`, os
  dois convivem; um seletor global novo no `retrospective.css` passaria a vazar para o Filament.
