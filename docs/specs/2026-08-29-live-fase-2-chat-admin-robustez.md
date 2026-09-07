---
type: spec
title: 'Live fase 2 — chat via Reverb, gestão no admin e robustez'
module: live
status: approved
date: 2026-08-29
author: Clintonrocha98
related:
    spec: 2026-08-29-live-mvp-design
    plan: 2026-08-29-live-mvp
---

# Live fase 2 — chat, admin e robustez

## Contexto

O MVP ([spec](./2026-08-29-live-mvp-design.md)) entregou o pipeline OBS → mediamtx →
página `/live`, com stream key fixa no `.env` e sem banco. A fase 2 transforma a live em
entidade de domínio (model `Live`, key por live), adiciona chat em tempo real via Laravel
Reverb, gestão no `panel-admin` e os follow-ups de robustez registrados no review do MVP.

Módulos afetados: `live` (domínio), `activity` (mensagens/moderação), `identity`
(provider novo), `portal` (página `/live` + chat), `panel-admin` (resources), infra
(`docker/mediamtx`, Reverb).

## Decisões estruturais (entrevista de 2026-08-29 — não reabrir)

1. **A live é um provedor de atividade.** As mensagens do chat entram na tabela
   `messages` do `activity` — chat da live é atividade de membro de primeira classe, como
   mensagens sincronizadas do Discord. Novo caso `IdentityProvider::He4rtLives =
'he4rt-lives'`; cada usuário ganha uma `ExternalIdentity` sintética desse provider
   (find-or-create no primeiro envio: `model` = User, `external_account_id` = id do
   usuário, `connected_at` preenchido, sem credencial — mesmo padrão das identidades
   criadas por ETL).
2. **Sem agenda.** Uma live é criada sob demanda no admin; no máximo **uma live corrente**
   (status ≠ `Ended`) por vez.
3. **Reuso do pipeline, não do fluxo ETL.** O envio do chat NÃO passa por
   `NewMessage`/`NewMessageDTO` (que são ETL-shaped: exigem `provider_message_id`,
   calculam XP, engolem exceções com log no canal do bot). Uma Action própria do módulo
   `live` cria a `Message` diretamente.
4. **Broadcast via Reverb, canal público por live** (`live.{uuid}`) — qualquer aba assina
   sem autenticação; a trava "só logado envia" fica no envio (Livewire/HTTP), não na
   escuta. Eventos `ShouldBroadcastNow` (sem depender de fila para latência).
5. **Agregados existentes passam a incluir o chat intencionalmente**
   (`DailyActivitySeries` do admin e `totalMessages` da home). Exceção obrigatória: o
   `DiscordSource` da retrospectiva ganha filtro de provider Discord — retrospectiva do
   Discord só conta Discord.
6. **XP do chat = 0 nesta fase** (`obtained_experience` é não-nullable; gamificação de
   chat é decisão futura, o campo já fica pronto).

## Domínio

### Model `Live` (módulo `live`, tabela `lives`)

| Coluna         | Tipo                       | Notas                                     |
| -------------- | -------------------------- | ----------------------------------------- |
| `id`           | uuid                       | Vira o `channel_id` das mensagens do chat |
| `title`        | string                     |                                           |
| `description`  | text nullable              | Exibida na página `/live`                 |
| `status`       | string (enum `LiveStatus`) | `Created` / `OnAir` / `Ended`             |
| `stream_key`   | text, cast `encrypted`     | Única por live; validada no auth hook     |
| `started_at`   | timestampTz nullable       | Primeiro sinal online                     |
| `ended_at`     | timestampTz nullable       | Encerramento pelo admin                   |
| `peak_viewers` | integer default 0          | Atualizado pelo heartbeat                 |
| timestamps     | timestampsTz               |                                           |

- Invariante "uma corrente por vez": garantido na Action de criação **e** por índice
  único parcial (`WHERE status <> 'ended'`).
- Enum `LiveStatus` implementa os contratos Filament (`HasLabel`/`HasColor`/
  `HasDescription`), cores distintas por caso (não é escala ordenada): `Created` gray,
  `OnAir` success, `Ended` danger.
- **Renomeação:** o DTO `He4rt\Live\DTOs\LiveStatus` do MVP passa a se chamar
  `StreamStatus` (ele descreve o estado do _stream_ no mediamtx; `LiveStatus` vira o enum
  do model). Módulo novo, sem consumidores externos além do portal — rename seguro.
- Factory + PHPDoc `@property` + `#[Table]`/`#[UseFactory]` conforme guidelines.

### Ciclo de vida

```
                        (webhook runOnOnline
                         do mediamtx)
  [Created] ──sinal on──► [OnAir] ──admin encerra──► [Ended]
      │                     │  ▲
      │                     │  │ (queda de OBS não muda status:
      │                     └──┘  reconexão re-emite LiveStarted)
      └──admin encerra──► [Ended]
```

- Admin cria (`Created`) e encerra (`Ended` + `ended_at`; key deixa de valer).
- `OnAir` + `started_at` são automáticos: o mediamtx chama um webhook no primeiro sinal.
- Queda de OBS **não** encerra: o status editorial segue `OnAir`; a página volta ao
  estado "aguardando sinal" pelo error handling do player e re-monta ao receber novo
  `LiveStarted` (re-emitido a cada `runOnOnline`).

### Auth hook do ingest (evolução do MVP)

```php
// ANTES (MVP): key fixa do .env
$streamKey = config()->string('live.stream_key');
return hash_equals($streamKey, $payload->password);

// DEPOIS (fase 2): key da live corrente + validação de path + rate limit
// - path do publish deve ser config('live.path'), senão 403;
// - sem live corrente (status != Ended inexistente) → 403;
// - rate limit por IP em falhas de publish (RateLimiter, ~5 falhas/min) → 403 direto;
// - hash_equals(stream key decriptada da live corrente, senha recebida).
```

`LIVE_STREAM_KEY` sai do `.env`/`.env.example` — a key agora vive no banco, por live.

### Webhook de sinal (novo endpoint)

- `POST /live/ingest/webhook` (fora do `web`, como o auth hook), autenticado por secret
  compartilhado (`config('live.webhook_secret')` ← `LIVE_WEBHOOK_SECRET`) em header.
- Chamado pelos hooks do mediamtx v1.20.1 (`runOnOnline`/`runOnOffline` — nomes da
  v1.20.1; `runOnReady` é de versões antigas). `online` → marca `OnAir`/`started_at`
  (primeira vez) e broadcasta `LiveStarted`; `offline` → nenhuma mudança de status
  (aceito e ignorado nesta fase; a página trata a queda no cliente).
- **Imagem docker muda de variante:** `bluenviron/mediamtx:1.20.1` (scratch, sem shell)
  → `bluenviron/mediamtx:1.20.1-ffmpeg` (Alpine, com shell + wget — verificado), pois
  hooks do mediamtx são comandos. O comando usa `wget --post-data` com o secret vindo de
  env do container.

## Chat

### Fluxo

```
 navegador (todos)                 Laravel (host :8000)                Reverb (host :8080)
┌─────────────────────┐  Livewire  ┌──────────────────────────┐ broadcast ┌────────────┐
│ /live               │ ─────────► │ SendChatMessage (live)    │ ────────► │ canal      │
│  histórico (50)     │  (POST,    │  1. auth + UserSituation  │  Chat     │ público    │
│  form só p/ logado  │  logado)   │  2. RateLimiter 5/10s     │  Message  │ live.{uuid}│
│                     │            │  3. identidade He4rtLives │  Sent     └─────┬──────┘
│  Echo (todos) ◄─────┼────────────┼─  (find-or-create)        │                 │
│  MessageSent        │  WebSocket │  4. cria Message           │   ◄─────────────┘
│  MessageDeleted     │            │     (activity, XP 0)      │   todos os inscritos
│  LiveStarted/Ended  │            └──────────────────────────┘
└─────────────────────┘
```

### Regras

- **Todos leem** (histórico = últimas 50 mensagens da live, índice
  `messages_channel_id_sent_at_index` já existe); **só logado escreve** (deslogado vê CTA
  de login no lugar do form).
- Envio negado a `UserSituation::Banned`/`Suspended` (estado que já existe no `User`).
- Rate limit de envio: 5 mensagens/10s por usuário (`RateLimiter`, padrão do repo).
- Conteúdo: 1–500 caracteres, texto puro (escape na exibição).
- Gravação em `messages`: `external_identity_id` = identidade He4rtLives do usuário,
  `channel_id` = uuid da Live, `kind = Default`, `source_kind = User`,
  `obtained_experience = 0`, `provider_message_id = null`, `sent_at = now()`.

### Moderação (fase 2)

- Admin apaga mensagem no panel-admin → Action de domínio: hard delete da `Message` +
  `ModerationEvent` (novo caso `ModerationType::MessageDeleted`, com snapshot do conteúdo
  em `metadata`) + broadcast `ChatMessageDeleted` (a mensagem some da tela de todos).
- Novo caso de enum preenche os contratos Filament existentes do `ModerationType`
  (match sem `default`).

## Audiência

- **Heartbeat anônimo:** a página `/live` (logado ou não) toca o servidor a cada ~10s
  (mesmo ciclo do poll de status). Cada batida: `ZADD live:viewers:{id}` no Redis
  (member = id de sessão, score = timestamp). Espectadores atuais = `ZCOUNT` na janela
  dos últimos 30s (+ `ZREMRANGEBYSCORE` de limpeza).
- **Pico:** `peak_viewers` da Live atualizado quando o atual supera.
- **Série temporal:** scheduler a cada minuto, se houver live `OnAir`, grava uma linha em
  `live_viewer_samples` (`live_id`, `viewers`, `sampled_at` timestampTz) → gráfico no
  admin. Sem tempo médio assistido nesta fase.

## Reverb / infra de tempo real

- `composer require laravel/reverb`; `npm i laravel-echo pusher-js`.
- `BROADCAST_CONNECTION=reverb` + bloco `REVERB_*` no `.env.example`.
- `php artisan reverb:start` entra no `concurrently` do `composer dev` (padrão do repo:
  PHP roda no host; docker só para infra não-PHP).
- Canal público `live.{uuid}` — eventos: `ChatMessageSent`, `ChatMessageDeleted`,
  `LiveStarted`, `LiveEnded`. Todos `ShouldBroadcastNow`.
- Echo configurado no bundle JS do portal (entry da página live).

## Panel-admin

- `He4rt\PanelAdmin\Live\Resources\LiveResource` (novo diretório em `discoverResources`,
  padrão Moderation/Marketing). `panel-admin/composer.json` ganha `"he4rt/live": "^1.0.0"`.
- Criar live (Filament Action → Domain Action `CreateLive`, que gera a stream key e
  valida o invariante de live única corrente).
- View da live: URL RTMP + stream key revelável/copiável (para colar no OBS), status,
  métricas (atual/pico), gráfico de audiência (widget lendo `live_viewer_samples`).
- Actions: **Encerrar** (`EndLive` — status, `ended_at`, broadcast `LiveEnded`) e
  **Rotacionar key** (`RotateStreamKey` — nova key, OBS atual cai na próxima conexão).
- RelationManager/tabela de mensagens do chat (query em `Message` por `channel_id`) com
  ação de deleção moderada (wrapping a Domain Action de moderação).

## Robustez (follow-ups do review do MVP — todos nesta fase)

1. **Player:** error handling do hls.js com transição para "aguardando sinal",
   `hls.destroy()` no teardown/morph, re-mount ao receber `LiveStarted`.
2. **Auth hook:** rate limit por IP em falhas de publish; validação do path.
3. **`CheckLiveStatus`:** cache curto (~5s) — o poll de N visitantes deixa de fazer N
   GETs síncronos na Control API.
4. **Teste de regressão** da classe `@vite`/multi-root da página live (o CI builda assets
   antes do Pest — `.github/workflows/_pest.yml`).
5. **CORS do HLS pinado** à origem do portal (`hlsAllowOrigin` no `mediamtx.yml`, via env
   do compose, em vez do default `*`).

## Fix decorrente no activity

```php
// ANTES — DiscordSource::messages() conta qualquer mensagem no período:
return Message::query()
    ->whereBetween('sent_at', [$period->since, $period->until])
    // ...

// DEPOIS — retrospectiva do Discord só conta mensagens de identidades Discord:
return Message::query()
    ->whereBetween('sent_at', [$period->since, $period->until])
    ->whereHas('provider', fn (Builder $q) => $q->where('provider', IdentityProvider::Discord))
    // ... (mesma restrição nas subqueries de reações/moderação keyed por identidade)
```

## Comportamento esperado

- **Dado** uma live `Created` com key K, **quando** o OBS publica com K, **então** o hook
  autoriza, o mediamtx chama o webhook, a live vira `OnAir` com `started_at`, e as abas
  abertas em `/live` trocam para o player ao receber `LiveStarted` (sem esperar poll).
- **Dado** uma live `Ended` (ou nenhuma live), **quando** o OBS tenta publicar com
  qualquer key, **então** 403 — nada vai ao ar.
- **Dado** um IP que errou a key 5 vezes em 1 minuto, **quando** tenta de novo, **então**
  403 direto sem comparar key.
- **Dado** um visitante deslogado em `/live` com live no ar, **então** vê player, chat
  (leitura) e contador; o form de envio é substituído por CTA de login.
- **Dado** um usuário logado ativo, **quando** envia mensagem, **então** ela persiste em
  `messages` (provider He4rtLives, XP 0) e aparece em tempo real para todas as abas.
- **Dado** um usuário banido/suspenso, **quando** tenta enviar, **então** o envio é
  negado com mensagem clara.
- **Dado** a 6ª mensagem em 10s do mesmo usuário, **então** o envio é limitado.
- **Dado** um admin que apaga uma mensagem, **então** ela some da tela de todos em tempo
  real, e fica registrado `moderation_event` `MessageDeleted` com snapshot do conteúdo.
- **Dado** o OBS caindo no meio da live, **então** a página volta a "aguardando sinal"
  (status segue `OnAir`) e re-monta o player quando o sinal volta.
- **Dado** a retrospectiva do Discord de um período com chat de live, **então** as
  mensagens do chat NÃO entram na contagem; **dado** a home do portal e a timeline de
  contribuições do admin, **então** elas SIM incluem o chat (semântica intencional).
- **Compatibilidade:** a página `/live` continua funcionando sem Reverb no ar (poll de
  10s como fallback de status); mensagens só ficam em tempo real com Reverb ativo.

## Testes

- `live`: auth hook contra live do banco (key correta/errada/live Ended/sem live/path
  inválido/rate limit); webhook (secret válido/inválido, online marca OnAir uma vez,
  re-online não duplica started_at); `SendChatMessage` (persistência, identidade
  find-or-create única, XP 0, banido/suspenso negado, rate limit, broadcast via
  `Event::fake`); `EndLive`/`RotateStreamKey`/`CreateLive` (invariante de live única);
  heartbeat/contagem/pico (Redis fake ou banco de teste); sample scheduler.
- `identity`: caso novo do enum (contratos Filament completos).
- `activity`: `DiscordSource` filtrando provider (mensagem He4rtLives fora da retrô);
  `ModerationType::MessageDeleted`.
- `portal`: página com chat (histórico, form só p/ logado, CTA deslogado), regressão da
  classe `@vite` multi-root.
- `panel-admin`: LiveResource (listar/criar/encerrar/rotacionar), deleção moderada de
  mensagem via tabela.

## Fora do escopo

XP por mensagem de chat (> 0), reações no chat, timeout/mute específico de chat, tempo
médio assistido, lista de presença, múltiplas lives simultâneas, agenda, gravação/DVR,
LL-HLS/WHEP, deploy.
