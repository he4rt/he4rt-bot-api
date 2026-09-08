---
type: adr
title: 'Super admin como role do spatie/laravel-permission'
module: identity
status: accepted
date: 2026-09-08
author: danielhe4rt
---

# ADR-0002: Super admin como role do spatie/laravel-permission

## Contexto

O acesso ao painel `/admin` dependia de uma lista de usernames em `HE4RT_ADMINS_USERNAMES`,
lida por `User::isAdmin()`. Trocar um admin exigia deploy, e o dado ficava fora do banco,
invisível para quem opera o painel.

## Decisão

- O pacote `spatie/laravel-permission` passa a ser a única fonte de autorização.
- Existe uma única role, `super-admin` (guard `web`), enumerada em `UserRole`. Nenhuma
  permission granular nesta fase: o painel inteiro é super admin ou nada.
- `Gate::before` em `IdentityServiceProvider` libera qualquer ability para quem tem a role e
  devolve `null` para os demais, então policies futuras continuam funcionando.
- O papel é atribuído pelo próprio `UserResource` do painel admin. Um admin não altera os
  próprios papéis, para não se trancar fora.
- O bootstrap em produção e a recuperação de lockout ficam no comando
  `identity:grant-super-admin {username}`.
- Fora de produção o painel continua aberto a qualquer usuário autenticado, como antes.

## Consequências

- `model_has_roles.model_id` e `model_has_permissions.model_id` são `uuid`, porque `users.id`
  é uuid. A migration vive no módulo identity, não na raiz, e não segue o stub do pacote ao pé
  da letra.
- `model_type` grava `user`, por causa do `Relation::morphMap` já existente.
- `config('he4rt.admins')` e `User::isAdmin()` deixam de existir. Testes usam
  `User::factory()->superAdmin()`.
- Novas roles entram como case em `UserRole` e no `RolesSeeder`, e aparecem no painel sem
  mudança de UI.
