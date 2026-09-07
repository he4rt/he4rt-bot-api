---
type: adr
title: "Galeria de fotos como sub-domínio de Events"
module: events
status: accepted
date: 2026-09-07
author: danielhe4rt
related:
  plan: events/2026-09-07-galeria-de-fotos
---

# 0009 — Galeria de fotos como sub-domínio de Events

## Contexto

A issue #504 pede uma galeria pública com fotos dos encontros da comunidade.
As fotos nascem em eventos, mas nem todo encontro fotografado vira um registro
de `Event` (pub, confraternização, visita). Havia três lugares possíveis para o
domínio: um módulo novo, o `portal` (que só renderiza) ou o `events`.

## Decisão

- A galeria vive em `app-modules/events/src/Gallery/`, como sub-domínio de
  Events, seguindo a estratégia de agrupamento por sub-domínio já usada em
  `identity` e `moderation`.
- `Album` é entidade própria, com `event_id` **opcional** (`nullOnDelete`).
  Apagar o evento não apaga o álbum.
- As fotos são a coleção `photos` do Spatie Media Library no próprio `Album`.
  Legenda e destaque são `custom_properties` da media, lidos pelo DTO `Photo`.
  Não existe tabela `events_photos`.
- Um álbum só é público quando `published_at` está no passado **e** tem ao
  menos uma foto. Rascunho e álbum vazio respondem 404 no portal.
- Events não registra rota nem UI. O `portal` é dono da borda pública
  (`/galeria`, `/galeria/{slug}`) e o `panel-admin` do CRUD e da curadoria,
  no mesmo desenho do ADR-0004 de `marketing`.

## Consequências

- **Positivas**: reaproveita o media library já configurado (disco `public`,
  conversões em fila), zero tabela nova além de `events_albums`, e o vínculo
  com `Event` fica disponível para pré-preencher local e data no painel.
- **Negativas**: a tabela `media` guarda `model_id` como texto. Comparar com o
  `uuid` de `events_albums` em `whereHas`/`withCount` exige o cast feito em
  `PhotosRelation`. Qualquer outro dono com UUID vai precisar do mesmo cast.
- **Fila**: as conversões `thumb`/`large` rodam em fila. Sem worker, o portal
  serve o original até a conversão existir (`PhotoView` faz o fallback).

## Alternativas descartadas

- **Módulo `gallery` separado**: mais um módulo para um agregado só, sem
  regra de negócio própria além de publicação.
- **Fotos no `Event`**: obrigaria criar `Event` para todo encontro
  fotografado, com política de inscrição e tudo, só para guardar fotos.
- **Tabela `events_photos` própria**: duplicaria o que a `media` já guarda
  (ordem, nome, tamanho, conversões) e perderia o `SpatieMediaLibraryFileUpload`
  do Filament.
