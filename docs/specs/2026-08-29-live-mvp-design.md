---
type: spec
title: 'MVP de live streaming local — OBS → mediamtx → página /live no portal'
module: live
status: approved
date: 2026-08-29
author: Clintonrocha98
related:
    spec: 2026-08-29-live-streaming-viabilidade
---

# MVP de live streaming local

## Contexto

A pesquisa de viabilidade ([spec relacionada](./2026-08-29-live-streaming-viabilidade.md))
definiu: apresentador transmite via OBS/RTMP, espectadores assistem via HLS no navegador,
live pública, latência de alguns segundos, experiência embutida no portal. Chat, painel
admin, banco de dados, agenda e deploy ficam para fases seguintes.

Este MVP entrega o fluxo mínimo de ponta a ponta em ambiente local: abrir o OBS com uma
stream key, transmitir, e qualquer navegador assistir em `/live`.

## Decisões estruturais

1. **Sem projeto à parte.** O mediamtx é uma imagem Docker pronta (`bluenviron/mediamtx`);
   entra como serviço no `docker-compose.yml` deste repo, seguindo o padrão existente
   (`docker/*.Dockerfile` + imagem `*.local`).
2. **Módulo de domínio `app-modules/live/`** (namespace `He4rt\Live`), sem migrations no
   MVP. A stream key vem de `LIVE_STREAM_KEY` no `.env`.
3. **Página no portal** (presentation → domain, nunca o inverso): componente Livewire
   full-page com rota registrada no `PortalServiceProvider`, padrão das páginas irmãs.
4. **Dependência nova aprovada:** `hls.js` via npm (Chrome/Firefox não tocam HLS nativo).

## Componentes

### Infra (docker)

- `docker/mediamtx.Dockerfile` — `FROM bluenviron/mediamtx` (versão pinada) copiando a
  config; imagem `mediamtx.local`.
- `docker/mediamtx/mediamtx.yml` — RTMP ligado (1935), HLS ligado (8888), Control API
  (9997, não exposta ao host), demais protocolos desligados; `authMethod: http` apontando
  para o endpoint do Laravel via `host.docker.internal` (com `extra_hosts:
host-gateway`, pois o app roda no host via `php artisan serve`, porta 8000).
- Serviço `mediamtx` no `docker-compose.yml` expondo 1935 (ingest) e 8888 (HLS).
- Para publicar (OBS), suba o Laravel com `php artisan serve --host=0.0.0.0`: o
  bind padrão (`127.0.0.1`) não é alcançável pelo container via `host.docker.internal`.

### Módulo `app-modules/live/`

- `config/live.php`: `stream_key`, URL pública do HLS (para o player), URL interna da
  Control API.
- `IngestAuthPayload` — DTO `final readonly` do JSON enviado pelo mediamtx no auth hook
  (`user`, `password`, `ip`, `action`, `path`, `protocol`, `query`).
- `AuthorizeMediaServerAction` — Action invokável: ações de leitura (`read`, `playback`,
  `api` interno não passa por aqui) sempre autorizadas (live pública); `publish` só se a
  senha bater com `config('live.stream_key')` via `hash_equals`. Responde 200/403.
- Rota `POST /live/ingest/auth` em `routes/live-routes.php`, fora do middleware `web`
  (server-to-server: sem sessão, sem CSRF).
- `CheckLiveStatus` — Action que consulta a Control API
  (`GET /v3/paths/get/{path}`) e devolve se a live está no ar (`ready`) e desde quando.
- Registro obrigatório de módulo novo: linha `mod:live` na tabela de
  `.ai/guidelines/workflow/02-triage-labels.blade.php` + label criada no GitHub.

### Portal — página `/live`

- `LivePage` (Livewire full-page, `He4rt\Portal\Live\`), rota `Route::get('/live', ...)`
  no `PortalServiceProvider`, layout `portal::components.layouts.app`.
- Dois estados: **offline** (placeholder com `wire:poll` ~10s consultando
  `CheckLiveStatus`) e **ao vivo** (player `hls.js` apontando para a URL HLS pública,
  com fallback nativo no Safari).

## Fluxo

```text
 OBS (host)                          docker-compose                       navegador
┌─────────────┐  rtmp://localhost:1935  ┌──────────────────────┐  http://localhost:8888/live/index.m3u8
│ tela+webcam │────── key no query ────►│  mediamtx            │◄────────── player hls.js ──────────┐
└─────────────┘                         │  RTMP ──► HLS        │                                    │
                                        └────┬─────────────────┘                                    │
                             (1) POST auth   │      ▲ (3) GET /v3/paths/get/live                    │
                                 (publish?)  ▼      │                                               │
                                ┌────────────────────────────┐        ┌───────────────────────┐     │
                                │ Laravel (host, :8000)      │        │ página /live (portal) │─────┘
                                │  app-modules/live/         │◄───────│  wire:poll status     │
                                │  (2) 200 se key confere    │        └───────────────────────┘
                                └────────────────────────────┘
```

## Comportamento esperado

- **Dado** mediamtx no ar e OBS publicando com a key correta, **quando** um visitante
  (deslogado, inclusive) abre `/live`, **então** vê o vídeo com poucos segundos de atraso.
- **Dado** um OBS com key errada ou ausente, **quando** tenta transmitir, **então** o hook
  responde 403 e o mediamtx recusa a conexão — nada vai ao ar.
- **Dado** nenhuma live ativa, **quando** o visitante abre `/live`, **então** vê o estado
  offline; **quando** a live começa, a página troca para o player em até ~10s sem
  recarregar.
- **Compatibilidade:** nenhum comportamento existente muda — módulo novo, rota nova,
  serviço novo no compose.

## Testes

- Feature (módulo `live`): hook autoriza `read` sem key; autoriza `publish` com key
  correta; rejeita `publish` com key errada/ausente; `CheckLiveStatus` com `Http::fake`
  (no ar, offline, Control API fora do ar).
- Feature (portal): `LivePage` renderiza estado offline e renderiza player quando o
  status responde "no ar".

## Fora do escopo

Chat, painel admin (Filament), model/migrations, agenda, métricas de audiência,
WHEP/baixa latência, gravação, deploy/hospedagem.
