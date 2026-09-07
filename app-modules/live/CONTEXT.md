# Contexto: Live

O sistema de registro de **transmissões ao vivo da própria heartdevs**: o ciclo de vida de uma
live, a credencial que autoriza o OBS a publicar, o estado do sinal, a audiência e as regras do
chat. O vídeo em si nunca passa por aqui — quem move mídia é o mediamtx (serviço de infra no
docker-compose); este módulo decide **quem pode publicar, o que está no ar e quem participa**.

É um **módulo de domínio**: sem rota de UI, sem Blade, sem Filament. O `portal` é dono da página
pública `/live`; o `panel-admin` é dono da gestão. As duas únicas rotas HTTP daqui
(`/live/ingest/auth` e `/live/ingest/webhook`) são server-to-server, consumidas pelo mediamtx.

## Glossário

| Termo                     | Definição                                                                                                                                                                                                                                     | Não confundir com                                                                            |
| ------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------- |
| **Live**                  | A entidade editorial: criada sob demanda pelo admin, com título, descrição e stream key própria. Não existe agendamento — uma live nasce quando alguém decide transmitir.                                                                     | A transmissão física. A live existe antes do primeiro sinal e sobrevive a quedas do OBS.     |
| **Live corrente**         | A única live com status ≠ `Ended` (scope `current()`). O invariante "no máximo uma" é garantido na Action de criação **e** por índice único parcial no Postgres.                                                                              | "A live no ar". Uma live corrente pode estar `Created` (aguardando o primeiro sinal).        |
| **LiveStatus**            | O enum editorial: `Created` → `OnAir` → `Ended`. `OnAir` é automático (webhook de sinal); `Ended` é sempre um ato do admin — queda de OBS não encerra.                                                                                        | `StreamStatus` (abaixo), que é o fato físico do momento.                                     |
| **StreamStatus**          | DTO com o estado do _stream_ segundo a Control API do mediamtx (`onAir`, `startedAt`), consultado por `CheckLiveStatusAction` com cache de ~5s. É o que decide se o player aparece.                                                           | O status da Live. Uma live `OnAir` pode ter `StreamStatus` offline (OBS caiu, reconectando). |
| **Stream key**            | Segredo por live (40 chars, cast `encrypted`, rotacionável). Validada no auth hook via `hash_equals` contra a live corrente. Rotacionar ou encerrar a live invalida a key na próxima conexão.                                                 | A "chave de stream" que o OBS pede — essa é o formato composto abaixo.                       |
| **Chave de stream (OBS)** | `live?user=he4rt&pass=<stream key>` (`Live::obsStreamKey()`): path + credenciais em query, porque é assim que o RTMP entrega credenciais. O OBS monta a URL final como `servidor/chave`.                                                      | Colar só a stream key crua no OBS — vira path desconhecido e o mediamtx recusa.              |
| **Auth hook**             | `POST /live/ingest/auth`, chamado pelo mediamtx a cada tentativa de conexão. Default-deny: leitura (`read`/`playback`) é pública; `publish` exige path configurado + key da live corrente; falhas repetidas por IP sofrem rate limit.         | Autenticação de usuário. Não há sessão aqui — é máquina falando com máquina.                 |
| **Webhook de sinal**      | `POST /live/ingest/webhook`, disparado pelos hooks `runOnOnline`/`runOnOffline` do mediamtx, autenticado por secret compartilhado em header. `online` marca `OnAir` (e `started_at` só na primeira vez); `offline` é aceito e ignorado.       | O auth hook. O webhook informa fato consumado; o auth hook decide antes.                     |
| **Mensagem do chat**      | Uma `Message` do **activity** cujo autor é a identidade `He4rtLives` do usuário e cujo `channel_id` é o uuid da Live. XP fixo em 0 nesta fase. Este módulo não tem model de mensagem próprio.                                                 | Mensagem sincronizada do Discord — mesma tabela, provider diferente.                         |
| **Identidade He4rtLives** | `ExternalIdentity` sintética (provider `he4rt-lives`) criada por usuário no primeiro envio — a plataforma de lives tratada como "mais um provedor de atividade", igual Discord/Twitch.                                                        | Uma conta externa real. Não há OAuth nem credencial; é o crachá interno da plataforma.       |
| **Espectador**            | Uma sessão de navegador com heartbeat nos últimos 30s (sorted set no Redis, anônimos contam). Alimenta o contador ao vivo e o `peak_viewers` da Live.                                                                                         | Usuário logado. A live é pública; presença não exige identidade.                             |
| **Amostra**               | Uma linha de `LiveViewerSample` (live, espectadores, instante), gravada por minuto pelo scheduler enquanto há live `OnAir` — a série temporal do gráfico do admin.                                                                            | O contador ao vivo, que é efêmero (Redis) e não persiste.                                    |
| **Canal da live**         | Canal público Reverb `live.{uuid}` com os eventos `LiveStarted` (re-emitido a cada reconexão do sinal), `LiveEnded`, `ChatMessageSent` e `ChatMessageDeleted`. Qualquer aba assina sem autenticação; a trava "só logado envia" fica no envio. | Canal de presença. Não há lista de quem está online — só o contador.                         |

## O que este módulo possui vs. o que não possui

| Responsabilidade                                                             | Aqui?                                                                     |
| ---------------------------------------------------------------------------- | ------------------------------------------------------------------------- |
| Ciclo de vida da Live (criar, marcar no ar, encerrar, rotacionar key)        | **Possui** — `Actions/`                                                   |
| Autorizar o media server (publish/read) e receber o sinal                    | **Possui** — `AuthorizeMediaServerAction`, `IngestWebhookController`      |
| Enviar e moderar mensagens do chat (regras, rate limit, trilha de moderação) | **Possui** — `Chat/Actions/`                                              |
| Presença, pico e amostras de audiência                                       | **Possui** — `Audience/`, `Contracts/ViewerPresenceContract`              |
| Eventos de tempo real da live                                                | **Possui** — `Events/` (broadcast best-effort via `rescue`)               |
| Persistência das mensagens e trilha `moderation_events`                      | **Não** — tabelas do `activity`; este módulo só escreve nelas via Actions |
| Identidades e usuários                                                       | **Não** — `identity` (`ResolveExternalIdentity` é reusado, não duplicado) |
| Página `/live`, player, UI do chat                                           | **Não** — `portal`                                                        |
| Gestão (resource, gráfico, moderação na UI)                                  | **Não** — `panel-admin/src/Live`                                          |
| Mover vídeo (RTMP→HLS), CORS do HLS                                          | **Não** — mediamtx (`docker/mediamtx/`)                                   |
| XP por mensagem de chat                                                      | **Não** — decisão futura da `gamification`; o campo já viaja com 0        |

## Decisões deliberadas

- **A live é um provedor de atividade, não dona de um segundo modelo de mensagem.** O chat entra
  no pipeline do `activity` via `IdentityProvider::He4rtLives`, preservando o invariante "toda
  mensagem tem autor `ExternalIdentity`" e ganhando de graça moderação, agregados e um futuro chat
  unificado com outras plataformas. Ver spec da fase 2.
- **O envio do chat não passa pelo fluxo ETL** (`NewMessage`): aquele fluxo exige id de mensagem
  externa, calcula XP e engole exceções. `SendChatMessage` cria a `Message` diretamente e falha
  alto para o usuário.
- **`OnAir` automático, `Ended` manual.** O sinal é fato físico (webhook); encerrar é decisão
  editorial. Queda de OBS mantém a live corrente — a página volta a "aguardando sinal".
- **Broadcast é best-effort.** Todos os eventos são `ShouldBroadcastNow` embrulhados em `rescue()`:
  Reverb fora do ar nunca derruba uma operação já persistida; o poll de ~10s da página é o
  fallback documentado.
- **Presença atrás de contrato** (`ViewerPresenceContract`): Redis em produção, memória nos
  testes/fallback — e o `pulse()` da página degrada graciosamente se o Redis sumir.
- **Sem agenda.** Uma live é criada e transmitida sob demanda; agendar é escopo explicitamente
  descartado nas decisões de produto.

## Estrutura

```
src/
├── Models/        ← Live · LiveViewerSample
├── Enums/         ← LiveStatus
├── Actions/       ← CreateLive · EndLive · RotateStreamKey · MarkLiveOnline ·
│                    AuthorizeMediaServerAction · CheckLiveStatusAction
├── DTOs/          ← IngestAuthPayload · StreamStatus
├── Events/        ← LiveStarted · LiveEnded · ChatMessageSent · ChatMessageDeleted
├── Chat/          ← Actions (SendChatMessage · DeleteChatMessage · ResolveChatIdentity) ·
│                    DTOs (ChatMessageData) · Exceptions (ChatMessageRejected)
├── Audience/      ← RedisViewerPresence · InMemoryViewerPresence ·
│                    Actions (RecordViewerHeartbeat · CountViewers)
├── Contracts/     ← ViewerPresenceContract
├── Console/       ← SampleLiveViewersCommand (agendado a cada minuto)
├── Exceptions/    ← CurrentLiveAlreadyExists
├── IngestAuthController · IngestWebhookController
└── LiveServiceProvider
```

Specs: `docs/specs/2026-08-29-live-mvp-design.md` (MVP) e
`docs/specs/2026-08-29-live-fase-2-chat-admin-robustez.md` (fase 2, decisões de produto).
