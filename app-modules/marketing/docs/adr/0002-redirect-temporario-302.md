---
type: adr
title: 'Redirect temporário (302), nunca permanente (301)'
module: marketing
status: accepted
date: 2026-08-21
deciders:
    - danielhe4rt
related:
    builds_on: marketing/0001-slug-imutavel-destino-versionado
---

# ADR-0002: Redirect temporário (302), nunca permanente (301)

## Contexto

`/l/{slug}` precisa devolver um status HTTP de redirecionamento. A escolha parece cosmética e não é:
ela decide se as duas razões do módulo existir continuam funcionando.

O instinto de quem vem de SEO é usar **301 Moved Permanently** — passa autoridade de link para o
destino e é mais rápido para o visitante recorrente, porque o browser nem chega ao servidor.

É justamente esse "nem chega ao servidor" que destrói tudo.

## Alternativas consideradas

- **301 Moved Permanently.** O browser cacheia a resposta, potencialmente para sempre.
- **302 Found.** Sem cache; toda requisição chega ao servidor.
- **307 Temporary Redirect.** Como o 302, mas preserva o método HTTP. Link curto é sempre `GET`,
  então não há ganho prático.
- **Página intersticial com redirect em JS.** Permitiria avisar que o visitante está saindo do
  domínio e capturar dado client-side, mas põe atrito em todo clique e quebra o preview (unfurl) no
  Discord.

## Decisão

**302 Found.**

Com 301, o segundo clique da mesma pessoa nunca chega ao servidor. Isso significa:

1. **A métrica para de existir.** O clique recorrente vira invisível — e reincidência é exatamente
   o sinal que se quer medir numa campanha.
2. **O destino versionado do [ADR-0001](0001-slug-imutavel-destino-versionado.md) deixa de valer.**
   O visitante continuaria indo para o destino antigo depois da edição, sem nenhuma forma de
   invalidar o cache do browser dele. A feature de destino mutável viraria mentira.

Um 301 não é uma otimização deste sistema; é a desativação silenciosa das duas features que o
justificam.

## Consequências

- Cada clique custa uma requisição ao servidor. Mitigado por cache de resolução por slug, que tira
  o Postgres do caminho quente.
- Nenhum ganho de SEO é transferido ao destino. Aceitável: link curto de divulgação não existe para
  ranquear, existe para ser colado e medido.
- **Isto não é ajustável sem quebrar o produto.** Se alguém no futuro trocar por 301 buscando
  performance, os cliques recorrentes somem do dashboard e a edição de destino para de surtir efeito
  — os dois sintomas aparecendo _depois_, sem erro nenhum no log. Há teste cobrindo o código 302
  exatamente para que essa mudança falhe em CI em vez de falhar em produção.
