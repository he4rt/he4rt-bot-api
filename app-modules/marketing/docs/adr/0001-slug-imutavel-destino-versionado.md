---
type: adr
title: 'Slug imutável, destino versionado'
module: marketing
status: accepted
date: 2026-08-21
deciders:
    - danielhe4rt
related:
    spec: marketing/2026-08-21-encurtador-de-links
---

# ADR-0001: Slug imutável, destino versionado

## Contexto

Uma das três dores que originaram o encurtador é **link já publicado que não pode mudar de alvo**:
o convite do Discord expira, o formulário de inscrição muda de URL, o board de vagas troca de
endereço — e o link continua circulando em post fixado, bio, slide de talk e adesivo impresso.

Um encurtador ingênuo trata o par slug↔destino como uma linha só, editável. Isso resolve a dor
imediata mas cria uma segunda, mais silenciosa: **a métrica passa a mentir**. Um link com 4.812
cliques não diz nada se metade foi para um destino que não existe mais. Pior: não há como saber
que houve troca, nem quando.

Também precisamos decidir o que acontece com um slug depois que o link morre. Se o slug voltar ao
pool, um link antigo ainda circulando no Discord pode, meses depois, apontar para a campanha de
outra pessoa — que é exatamente o mecanismo de sequestro de link.

## Alternativas consideradas

- **Linha única editável.** Uma tabela, `UPDATE` no destino. Simples e errado pelos motivos acima.
- **Slug imutável + destino versionado.** Duas tabelas: o link (identidade estável) e um histórico
  append-only de intervalos de vigência `[valid_from, valid_until)`.
- **Novo link a cada troca de destino.** Preserva a história, mas quebra a dor original — o link
  publicado passaria a apontar para o lugar errado, que é o problema que viemos resolver.

## Decisão

**O `slug` é a identidade permanente do link; o destino é um fato datado.**

- `marketing_short_links.slug` tem índice único e **nunca muda** depois de criado.
- Toda troca de destino fecha o intervalo vigente (`valid_until = now()`) e abre outro
  (`valid_from = now()`) em `marketing_short_link_destinations`, dentro de uma transação. Sem buraco
  e sem sobreposição entre vigências.
- Mudança que **não** é de destino (tag, expiração, ativar/desativar) não gera linha de histórico.
  O histórico registra mudança de destino, não qualquer edição.
- O soft delete **não libera o slug**: a linha deletada continua ocupando o índice único, então o
  slug nunca é reusado.

## Consequências

- O gráfico de cliques pode ser lido junto do histórico: dá para saber que entre 14/07 e 21/08 o
  link apontava para o convite antigo.
- Toda escrita passa por `UpdateShortLink`. Um `$link->update()` direto pula o versionamento — por
  isso a camada de apresentação delega à Action de domínio, nunca escreve no model.
- O custo é uma tabela a mais e uma transação por edição. Barato para o volume de staff.
- Como o slug é imutável, o formulário de edição **não** expõe o campo. Editar slug seria criar um
  link novo, e a UI deve dizer isso em vez de fingir que dá.
