---
type: adr
title: 'A borda HTTP mora no portal; marketing é domínio puro'
module: marketing
status: accepted
date: 2026-08-21
deciders:
    - danielhe4rt
related:
    builds_on: marketing/0002-redirect-temporario-302
---

# ADR-0004: A borda HTTP mora no portal; `marketing` é domínio puro

## Contexto

O encurtador precisa de três coisas do lado HTTP: a rota `GET /l/{slug}`, o controller que decide
entre redirecionar e recusar, e a página exibida quando o slug não resolve.

O instinto é colocar as três dentro do módulo `marketing`, junto do domínio — o módulo fica
autocontido e tudo que é "encurtador" mora num lugar só.

Duas coisas atrapalham. A primeira é o guideline de arquitetura deste repo: _presentation modules
own UI concerns only; domain logic belongs in domain modules_, e módulos de domínio nunca importam
de módulos de apresentação. A segunda é concreta: a página de "link indisponível" precisa do layout,
da navbar e dos componentes de marca, que vivem no `portal`.

## Alternativas consideradas

- **Tudo em `marketing`.** O módulo passaria a registrar rota e renderizar Blade, e precisaria
  importar do `portal` para ter layout — invertendo a direção de dependência que o guideline proíbe.
- **Tudo em `marketing` com `abort(404)` seco.** Respeita a regra, mas entrega uma parede para quem
  clicou num link de evento antigo.
- **Domínio em `marketing`, borda HTTP no `portal`.**

## Decisão

**O `portal` é dono da borda HTTP; o `marketing` é dono da decisão.**

- `marketing` expõe `ResolveShortLink::execute(string $slug): Resolution` — um veredito em forma de
  dado, nunca uma resposta HTTP. O módulo não registra rota e não tem uma única view.
- `portal` registra `GET /l/{slug}` no `PortalServiceProvider`, ao lado de `/`, `/redes` e
  `/artigos` (este repo declara rotas públicas no provider, não em arquivos `routes/*.php`), e
  possui o controller e a view `short-link-unavailable`.
- `panel-admin` possui o CRUD e os dashboards. Os dois dependem de `marketing`; `marketing` não
  depende de nenhum dos dois.

### Respostas indistinguíveis

Os quatro desfechos mortos — slug inexistente, `active = false`, expirado e soft-deletado —
produzem resposta **idêntica**, byte a byte. Revelar qual dos quatro é transformaria a página num
oráculo de enumeração de slug.

Isso custou mais do que parece: o `canonical` padrão do `laravel/head` escrevia o slug dentro do
`<head>` da página 404, fazendo as quatro respostas diferirem. Foi preciso fixar `canonical: '/'` e
`robots: noindex, follow`. Há teste comparando os quatro corpos justamente porque a igualdade é
frágil e some sem avisar.

## Consequências

- Quem procura "o encurtador" encontra código em três módulos. O `CONTEXT.md` do `marketing` e este
  ADR existem para que isso seja navegável em vez de surpreendente.
- Se o `marketing` um dia precisar renderizar algo, a fronteira foi cruzada — é o sinal de que a
  decisão está sendo revertida por acidente.
- O `portal` ganhou uma dependência de domínio, o que é permitido pela regra (apresentação depende de
  domínio, nunca o contrário).
