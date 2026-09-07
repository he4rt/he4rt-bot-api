---
type: spec
title: 'Viabilidade de live streaming (tela, webcam, microfone) via web'
status: research
date: 2026-08-29
author: Clintonrocha98
---

# Viabilidade de live streaming (tela, webcam, microfone) via web

## 1. Contexto e objetivo

O problema de negócio é "one-to-many broadcast": **um** apresentador transmite tela +
webcam + microfone diretamente do navegador, e **muitos** espectadores assistem também
pelo navegador. Isso é deliberadamente diferente de uma videoconferência many-to-many
(tipo Zoom/Meet), onde todo mundo publica e todo mundo assina fluxos de todo mundo — o
custo de fan-out e o modelo de permissões são outros.

> **Premissa revista em 29/08/2026:** a exigência de "transmitir pelo navegador sem
> instalar nada" foi descartada — usar OBS no ingest é aceito e até esperado. Ver o
> [Adendo (seção 9)](#9-adendo-2026-08-29--premissa-revista-ingest-via-obsrtmp-é-aceito),
> que requalifica o Owncast e revisa a comparação final.

Para o heartdevs.com isso importa porque a plataforma já tem eventos, comunidade e um
painel de curadoria de conteúdo (`activity`, `events`, `contents`); live streaming é a
peça que falta para transmitir talks, retrôs e eventos He4rt sem depender de Twitch/YouTube
como intermediário obrigatório.

### O que foi verificado no repositório antes de propor arquitetura

- **`laravel/reverb` (servidor de WebSockets do Laravel) não está presente** no projeto:
  `grep -i reverb` em `composer.json` e `composer.lock` não retornou nenhuma ocorrência.
  Ou seja, hoje não há nenhum servidor de broadcasting/WebSocket rodando ao lado do
  monolito — qualquer solução de live precisa trazer sua própria peça de
  infraestrutura de tempo real, não existe algo para "reaproveitar".
- **O padrão `integration-*` já é estabelecido no repo.** `app-modules/` tem hoje
  `integration-devto`, `integration-discord`, `integration-github`, `integration-twitch`
  e `integration-whatsapp`, todos seguindo a mesma convenção: pacote
  `he4rt/integration-<nome>`, namespace `He4rt\Integration<Nome>`, tipo `library`. Isso é
  precedente direto para modelar a orquestração de live como um módulo do monolito que
  fala com um serviço externo — só que aqui o serviço externo é um media server, não uma
  API HTTP de terceiro.

## 2. Como funciona streaming web hoje

**Em linguagem simples:** para transmitir a tela e a câmera de um navegador sem instalar
nada, o navegador precisa de duas permissões distintas — uma para "gravar minha tela" e
outra para "gravar minha câmera/microfone" — e depois de um jeito padronizado de enviar
esse vídeo para um servidor. Do lado de quem assiste, existem dois caminhos consolidados:
um de latência baixíssima (tipo chamada de vídeo, mas só em uma direção) e um de latência
mais alta só que muito mais fácil de escalar para milhares de pessoas (o mesmo mecanismo
usado por Netflix/YouTube).

**Em detalhe técnico**, os blocos que compõem essa cadeia:

### Captura no navegador: `getUserMedia` e `getDisplayMedia`

- **`getUserMedia`** é o método que pede ao navegador acesso à câmera e ao microfone do
  usuário, definido na spec **Media Capture and Streams** do W3C (atualmente em
  _Candidate Recommendation Draft_, publicada em 9 de outubro de 2025):
  ["a set of JavaScript APIs that allow local media, including audio and video, to be
  requested from a platform"](https://www.w3.org/TR/mediacapture-streams/).
- **`getDisplayMedia`** é a extensão que pede ao navegador acesso à tela, uma janela ou
  uma aba, definida na spec **Screen Capture** do W3C (ainda em _Working Draft_, datada de
  27 de agosto de 2026 — o próprio documento diz "not intended for implementation" por
  ainda estar sujeito a mudanças maiores, mas na prática já é implementado por todos os
  navegadores majoritários há anos):
  ["enables the acquisition of a user's display, or part thereof, in the form of a video
  track"](https://www.w3.org/TR/screen-capture/). Diferença prática importante: em
  `getDisplayMedia` as _constraints_ só se aplicam **depois** que o usuário escolhe o que
  compartilhar — a aplicação não pode restringir a escolha do usuário como faz com a
  câmera.
- Um apresentador com tela+webcam+microfone gera **duas fontes de mídia separadas** (uma
  de cada API). Compor as duas em um único vídeo com PiP (câmera sobre a tela) é trabalho
  de aplicação — normalmente feito no cliente via `<canvas>` (desenhando os dois vídeos e
  recapturando o canvas como uma nova track) ou enviando as tracks separadas e deixando a
  composição para quem assiste/grava. Nenhuma das duas specs resolve esse problema por
  conta própria — elas só entregam a captura bruta.

### WebRTC vs. HLS

- **WebRTC** é o conjunto de APIs/protocolos do navegador para comunicação em tempo real
  peer-to-peer, padronizado como **W3C Recommendation** desde 13 de março de 2025:
  ["media and generic application data to be sent to and received from another browser
  or device implementing the appropriate set of real-time protocols"](https://www.w3.org/TR/webrtc/).
  Na prática: latência de menos de 1 segundo, mas cada conexão é um estado dedicado no
  servidor (não dá simplesmente para colocar um CDN de arquivos estáticos na frente).
- **HLS** (HTTP Live Streaming) não tem uma única spec W3C/IETF centralizada da mesma
  forma — é um formato de segmentação de vídeo entregue via HTTP comum, o que o torna
  cacheável por qualquer CDN genérica. Na prática: latência de vários segundos (ou
  sub-segundo só com "LL-HLS", Low-Latency HLS), mas escala para dezenas de milhares de
  espectadores usando a mesma infraestrutura de CDN que serve qualquer arquivo estático.

### WHIP e WHEP

Esses dois protocolos existem para resolver um problema específico: WebRTC por si só não
define _como_ dois lados combinam a sessão (o "signaling") — cada aplicação inventava o
seu. WHIP e WHEP padronizam esse signaling para os dois papéis do broadcast:

- **WHIP (WebRTC-HTTP Ingestion Protocol)** — como o navegador do apresentador **envia**
  mídia para o servidor via WebRTC de forma padronizada. Publicado como
  **RFC 9725** (Proposed Standard, Standards Track) pela IETF:
  ["a simple HTTP-based protocol that will allow WebRTC-based ingestion of content into
  streaming services and/or Content Delivery Networks (CDNs)"](https://www.rfc-editor.org/rfc/rfc9725).
  Na prática: o navegador faz um único `POST` HTTP com a oferta SDP e recebe de volta a
  resposta SDP — sem WebSocket, sem SDK proprietário, só `fetch()` + `RTCPeerConnection`.
- **WHEP (WebRTC-HTTP Egress Protocol)** — o equivalente para quem **assiste**: como o
  navegador do espectador **recebe** mídia via WebRTC de forma padronizada. Ainda é um
  **Internet-Draft ativo** do grupo de trabalho WISH da IETF (`draft-ietf-wish-whep`,
  versão 04, de 22 de junho de 2026 — ainda não é RFC):
  ["a simplified HTTP-based protocol that allows WebRTC-based viewers to consume media
  from streaming services and CDNs through a SDP offer/answer exchange"](https://datatracker.ietf.org/doc/draft-ietf-wish-whep/).

Ambos deliberadamente reduzem WebRTC a "REST + SDP", o que é o que torna viável assistir
ou publicar direto do navegador sem carregar um SDK JS pesado.

## 3. Tabela comparativa

| Projeto                                                             | Linguagem                               | Licença                        | Ingest                                                                                      | Entrega                                                                                            | WHIP/WHEP nativo do navegador                                                                                                                   | Escala (fonte oficial)                                                                                                                                                                           | Maturidade (GitHub)                                |
| ------------------------------------------------------------------- | --------------------------------------- | ------------------------------ | ------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ | -------------------------------------------------- |
| [Owncast](https://github.com/owncast/owncast)                       | Go                                      | MIT                            | RTMP apenas                                                                                 | HLS                                                                                                | Não — sem WHIP; issue de WebRTC/WHIP fechada como "not planned"                                                                                 | Sem limite fixo; escala é banda + CDN na frente do HLS                                                                                                                                           | 11.478★, release mais recente v0.2.5 (11/04/2026)  |
| [LiveKit](https://github.com/livekit/livekit)                       | Go                                      | Apache-2.0                     | RTMP/WHIP via serviço `Ingress` separado (para OBS/hardware); SDK JS próprio para navegador | WebRTC (SFU) nativo + HLS via serviço `Egress` separado                                            | Ingest: WHIP só via Ingress, não no SDK do navegador (SDK usa protocolo proprietário Protobuf sobre WebSocket). Egress: sem WHEP documentado    | Self-hosted (benchmark oficial): 1 publisher de vídeo / 3.000 subscribers por node de 16 cores a 92% CPU. "Milhões de espectadores" é claim do LiveKit Cloud (SaaS pago), não do OSS self-hosted | 20.573★, release mais recente v1.13.6 (26/08/2026) |
| [mediamtx](https://github.com/bluenviron/mediamtx)                  | Go                                      | MIT                            | RTSP, RTMP, **WHIP**, SRT, HLS, MPEG-TS, RTP                                                | RTSP, RTMP, **WHEP**, SRT, HLS, MPEG-TS, RTP                                                       | **Sim** — publish/read nativos via `fetch` + SDP, sem SDK                                                                                       | Sem benchmark oficial publicado; docs recomendam CDN na frente para muitos leitores                                                                                                              | 19.958★, release mais recente v1.20.1 (18/08/2026) |
| [OvenMediaEngine](https://github.com/OvenMediaLabs/OvenMediaEngine) | C++                                     | AGPL-3.0 (+ licença comercial) | WebRTC/**WHIP** (simulcast), SRT, RTMP, MPEG-2 TS                                           | LL-HLS, HLS legado, SRT, WebRTC via **signaling proprietário via WebSocket** (não WHEP confirmado) | Ingest sim (WHIP puro); egress **não confirmado** — docs oficiais e release notes não mencionam WHEP apesar de claim de terceiro não verificado | Docs oficiais afirmam entrega para "hundreds of thousands" de espectadores com latência sub-segundo                                                                                              | 3.267★, release mais recente v0.21.0 (13/08/2026)  |
| [Janus Gateway](https://github.com/meetecho/janus-gateway)          | C                                       | GPL-3.0                        | WebRTC genérico (ICE/DTLS/SRTP); WHIP só via camada externa (`simple-whip-server`)          | WebRTC genérico via plugins (VideoRoom, Streaming)                                                 | Não nativo — precisa de tradutor HTTP↔Janus-API em frente                                                                                       | Sem benchmark de escala de espectadores publicado nas fontes oficiais consultadas                                                                                                                | 9.157★, última tag v1.4.1                          |
| [mediasoup](https://github.com/versatica/mediasoup)                 | C++ (worker) + Node.js/Rust (interface) | ISC                            | Não é servidor pronto — é biblioteca/toolkit SFU "signaling agnostic"; sem WHIP embutido    | Idem — sem WHEP embutido                                                                           | Não — é preciso construir a aplicação e o signaling em cima                                                                                     | Não se aplica (não é um servidor com números de escala publicados)                                                                                                                               | 7.348★, última tag `rust-0.27.0` (18/08/2026)      |
| [SRS](https://github.com/ossrs/srs)                                 | C++                                     | MIT                            | RTMP, WebRTC/**WHIP**, SRT                                                                  | HLS, WebRTC/**WHEP**, HTTP-FLV, HTTP-TS, MPEG-DASH                                                 | **Sim** — URLs WHIP/WHEP documentadas e testáveis via HTTP puro                                                                                 | Sem benchmark oficial de espectadores publicado                                                                                                                                                  | 29.178★, release mais recente v6.0-r1 (12/08/2026) |
| [Galène](https://github.com/jech/galene)                            | Go                                      | MIT                            | WebRTC/**WHIP**                                                                             | WebRTC (protocolo de cliente próprio); WHEP não documentado                                        | Ingest sim; egress não                                                                                                                          | ~300 participantes de palestra por núcleo de CPU (uso one-to-many); ~20-40 por núcleo em reunião many-to-many                                                                                    | 1.390★, última tag `galene-1.1`                    |

## 4. Análise por opção

### Owncast

Owncast é um servidor de streaming pessoal auto-hospedado escrito em Go, com frontend em
React, licenciado sob [MIT](https://github.com/owncast/owncast). O ingest é
**exclusivamente RTMP**: ["Owncast is compatible with any software that uses RTMP to
broadcast to a remote server"](https://owncast.online/docs/broadcasting/), recebendo na
porta TCP 1935 por padrão. Isso desqualifica Owncast para o requisito central deste
documento — "apresentador transmite pelo navegador sem instalar nada" — porque exige OBS,
ffmpeg ou outro encoder RTMP como intermediário. Uma proposta de suporte a WebRTC/WHIP via
integração com o projeto `broadcast-box` foi discutida e fechada como
["not planned"](https://github.com/owncast/owncast/issues/3429) pelos mantenedores.

A entrega é via HLS, e a [documentação oficial de escalabilidade](https://owncast.online/docs/scaling/)
recomenda colocar um CDN na frente do vídeo para audiências maiores, e reconhece
explicitamente que o teste de milhares de conexões de chat simultâneas é "anecdotal
experience rather than a tested or guaranteed limit". A integração com um app externo é
via [API HTTP autenticada por token de acesso e Webhooks](https://owncast.online/docs/api/)
que fazem `POST` com um campo `type` e um `eventData` — modelo simples e compatível com um
módulo Laravel, mas o gargalo do ingest via navegador continua de pé.

### LiveKit

LiveKit é um "end-to-end realtime stack" em Go, licenciado sob
[Apache 2.0](https://github.com/livekit/livekit), com SDKs oficiais de cliente para
JS/TypeScript, Swift, Kotlin, Flutter, React Native e Rust. É importante separar duas
coisas que a busca inicial confunde: (1) o **SDK do navegador** (`livekit-client`) captura
mídia local e publica na sala, mas o faz através do **protocolo de sinalização próprio do
LiveKit** — WebSocket com Protocol Buffers, não WHIP:
["LiveKit clients use a WebSocket to communicate with the server over Protocol
Buffers"](https://docs.livekit.io/reference/internals/client-protocol/); (2) o serviço
**Ingress**, que é uma peça separada, sim
[suporta RTMP e WHIP](https://docs.livekit.io/home/ingress/overview/) — mas para trazer
fontes externas (OBS, hardware) para dentro da sala, não para a captura direta do navegador
via `getUserMedia`/`getDisplayMedia`.

A autenticação é via **tokens JWT** assinados no backend com uma API key/secret, contendo
_video grants_ como `roomJoin`, `canPublish` e `canSubscribe`
([documentação oficial de VideoGrant](https://docs.livekit.io/reference/server-sdk-js/interfaces/VideoGrant.html)) —
o modelo que permite modelar exatamente "um apresentador pode publicar, todos os outros só
assinam". O SDK oficial de servidor em PHP não é publicado pela organização `livekit` no
GitHub; existe um SDK **comunitário** listado na documentação oficial,
[`agence104/livekit-server-sdk-php`](https://github.com/agence104/livekit-server-sdk-php) —
gerar o JWT manualmente com uma lib como `firebase/php-jwt` também é viável, já que o
formato de claims é documentado.

Sobre escala: o [benchmark oficial de self-hosting](https://docs.livekit.io/home/self-hosting/benchmark/)
roda em uma instância de 16 núcleos (Google Cloud `c2-standard-16`) e reporta 1 publisher
de vídeo com 3.000 subscribers a 92% de CPU num único node — e a doc é explícita que
["each room must fit within a single node"](https://docs.livekit.io/home/self-hosting/benchmark/),
ou seja, escalar além disso exige múltiplos nodes ou migrar para entrega via HLS+CDN. O
claim de "millions of simultaneous viewers" está atrelado ao **LiveKit Cloud**, o serviço
pago: ["LiveKit Cloud is a new type of WebRTC CDN, supporting the same scale as
HLS"](https://livekit.com/use-cases/livestreaming) — não é uma característica do software
open source self-hosted.

### mediamtx

mediamtx (ex-`rtsp-simple-server`) é descrito pela própria documentação como
["a ready-to-use, open source, live media router"](https://mediamtx.org), escrito em Go e
licenciado sob [MIT](https://github.com/bluenviron/mediamtx). É o único dos projetos
pesquisados com suporte **nativo e simétrico** a WHIP (ingest) e WHEP (egress) usável
puramente do navegador sem SDK: a
[documentação de publish via WebRTC](https://mediamtx.org/docs/publish/webrtc-clients) diz
literalmente ["WHIP is a WebRTC extension that allows to publish streams by using a URL,
without passing through a web page"](https://mediamtx.org/docs/publish/webrtc-clients),
com endpoint `http://host:8889/streamkey/whip`; a
[documentação de leitura via WebRTC](https://mediamtx.org/docs/read/webrtc) confirma o
mesmo para WHEP, com endpoint `http://host:8889/mystream/whep`. Isso significa que um
apresentador pode publicar com um punhado de linhas de JavaScript (`getDisplayMedia` +
`getUserMedia` + `RTCPeerConnection` + `fetch`) sem depender de nenhuma biblioteca de
terceiro, e o mesmo vale para quem assiste.

Para integração externa, o mediamtx expõe uma
[Control API](https://mediamtx.org/docs/references/control-api) e três mecanismos de
[autenticação](https://mediamtx.org/docs/features/authentication): credenciais internas no
arquivo de config, um **servidor HTTP externo** que recebe um payload JSON (usuário, senha,
token, IP, ação desejada — `publish`/`read`/`playback`/`api`/`metrics`/`pprof` —, caminho e
protocolo) e responde com sucesso/falha, ou um provedor **JWT externo** via JWKS com uma
claim `mediamtx_permissions`. O primeiro modelo é exatamente o papel que um módulo Laravel
de orquestração cumpriria. Além disso, os
[hooks](https://mediamtx.org/docs/features/hooks) (`runOnConnect`, `runOnPublish` [via
`runOnReady`/`runOnNotReady` conforme o path], `runOnRead`, `runOnRecordSegmentComplete`
etc.) disparam comandos externos — tipicamente um `curl` para uma URL — a cada evento de
conexão/publicação/leitura, dando ao Laravel visibilidade em tempo real do que está
acontecendo no media server sem polling. Não há benchmark oficial de número máximo de
espectadores publicado pelos mantenedores; a única cifra relacionada encontrada foi um
relato de usuário em uma _GitHub Discussion_ (não uma declaração de mantenedor nem
benchmark oficial), então não deve ser tratada como número confiável — um spike de carga é
necessário antes de comprometer capacidade.

### OvenMediaEngine

OvenMediaEngine (OME) é um media server em C++ da OvenMediaLabs (anteriormente sob a conta
GitHub `AirenSoft`), licenciado sob
[AGPL-3.0](https://api.github.com/repositories/139194755) com opção de licença comercial
para quem não quer as obrigações do AGPL. O ingest via WHIP é nativo e segue o draft do
IETF: a documentação afirma implementar o `draft-ietf-wish-whip/` com o endpoint
`http[s]://<Host>[:<Port>]/<App>/<Stream>?direction=whip`
([WebRTC/WHIP | OvenMediaEngine](https://ovenmedia.com/docs/ome/live-source/webrtc)), sem
exigir SDK — igual ao mediamtx e ao SRS nesse quesito.

A diferença relevante está na **saída**: a
[documentação de publicação WebRTC](https://ovenmedia.com/docs/ome/streaming/webrtc-publishing)
descreve um "Self-Defined Signaling Protocol" embutido, baseado em WebSocket
(`ws[s]://<Host>[:<Port>]/<App>/<Stream>`), e **não menciona WHEP em nenhum momento**. Uma
busca por menções a WHEP nos releases mais recentes do repositório
(`OvenMediaLabs/OvenMediaEngine`) via API do GitHub não retornou nenhuma ocorrência — ou
seja, ao contrário do que um artigo de terceiro (não citável como fonte técnica por regra
deste documento) afirma, **não foi possível confirmar suporte a WHEP nas fontes primárias
consultadas** (docs oficiais + release notes) na data desta pesquisa. Isso é uma lacuna
relevante para o caso de uso: viewers no navegador precisariam do protocolo proprietário
via WebSocket, não do padrão IETF.

Sobre escala, a [documentação de performance tuning](https://docs.ovenmediaengine.com/performance-tuning)
afirma que o OME permite ["platforms/services/systems that transmit high-definition video
to hundreds-thousand viewers with sub-second latency"](https://docs.ovenmediaengine.com/performance-tuning),
com parâmetros de tuning (`StreamWorkerCount`, `AppWorkerCount`) para distribuir a carga de
criptografia SRTP/TLS entre threads. A integração externa é via API REST e
_Admission Webhooks_ para controle de acesso.

### Janus Gateway

Janus é descrito pelos próprios mantenedores (Meetecho) como
["an open source, general purpose, WebRTC server"](https://github.com/meetecho/janus-gateway),
escrito em C e licenciado sob [GPL-3.0](https://github.com/meetecho/janus-gateway). Ele
implementa o núcleo WebRTC (ICE, DTLS, SRTP) e expõe funcionalidade através de **plugins**
(VideoRoom, Streaming, AudioBridge, SIP, RecordPlay etc.), controlados via API REST ou
WebSockets. Diferente de mediamtx/SRS/OvenMediaEngine, Janus **não tem um plugin nativo
`janus.plugin.whip`** — segundo o blog oficial da Meetecho (empresa mantenedora do
projeto), WHIP é servido colocando uma camada de tradução HTTP↔Janus-API na frente, como o
[`simple-whip-server`](https://github.com/meetecho/simple-whip-server) (Node.js), que
mapeia chamadas WHIP para o plugin VideoRoom
([WHIP-ing WebRTC to Janus!](https://www.meetecho.com/blog/whip-janus/)). Isso adiciona uma
peça extra de infraestrutura (o tradutor) que os outros projetos não exigem, e não há
suporte documentado a WHEP. Não foram encontradas nas fontes oficiais consultadas cifras
de escala de espectadores.

### mediasoup

mediasoup é explicitamente **não um servidor pronto para uso**, mas uma
["biblioteca/toolkit SFU"](https://github.com/versatica/mediasoup) com um _worker_ em C++
sobre `libuv` e uma interface de aplicação em Node.js ou Rust, licenciado sob
[ISC](https://github.com/versatica/mediasoup). O projeto é deliberadamente
["signaling agnostic: do not mandate any signaling protocol"](https://github.com/versatica/mediasoup) —
ou seja, não vem com WHIP, WHEP, nem nenhum protocolo de sinalização definido; cabe a quem
o usa desenhar e implementar essa camada do zero. Para o caso de uso deste documento isso
representa esforço de engenharia significativamente maior do que qualquer outra opção da
lista — construir um serviço de sinalização (provavelmente em Node.js, já que é a
linguagem primária dos exemplos oficiais) só para orquestrar o worker, antes mesmo de
chegar na parte de integrar com Laravel. Não é recomendável para este projeto dado o custo
de implementação e o fato de já existirem soluções prontas (mediamtx, SRS) que cobrem o
mesmo espaço de protocolos (ICE/DTLS/RTP/RTCP) com WHIP/WHEP embutido.

### SRS (Simple Realtime Server)

SRS é um media server em C++ licenciado sob [MIT](https://github.com/ossrs/srs), com
29.178 estrelas — o mais popular por número de estrelas entre os projetos pesquisados — e
lançamentos frequentes (`v6.0-r1` em 12/08/2026). Suporta WHIP e WHEP nativamente com URLs
de teste documentadas: publish em
`http://localhost:1985/rtc/v1/whip/?app=live&stream=livestream` e playback em
`http://localhost:1985/rtc/v1/whep/?app=live&stream=livestream`
([WebRTC | SRS](https://ossrs.net/lts/en-us/docs/v6/doc/webrtc)), o que o coloca no mesmo
patamar do mediamtx quanto à captura/entrega direto do navegador sem SDK proprietário. Além
de WebRTC, cobre RTMP, SRT, HLS, HTTP-FLV, HTTP-TS e MPEG-DASH — leque de protocolos mais
amplo que o mediamtx para entrega, mas a documentação de WebRTC consultada **não publica
nenhum benchmark oficial** de número de espectadores suportados; o texto só discute
diferenças conceituais entre arquiteturas TURN/SFU/MCU. A integração externa é via HTTP
API/callbacks documentados em `ossrs.net`.

### Galène

Galène é um sistema de videoconferência em Go, licenciado sob
[MIT](https://github.com/jech/galene), que a própria documentação descreve como
["originally designed for lectures, conferences and student tutorials, but is also
suitable for traditional meetings"](https://galene.org/) — ou seja, nasceu para o cenário
"poucos falam, muitos assistem" mas também serve para many-to-many. Isso o torna
relevante para este documento mesmo sendo primariamente um SFU de conferência. A
[documentação oficial](https://galene.org/) apresenta orientações concretas de capacidade
por núcleo de CPU: cerca de 300 participantes de palestra por núcleo, contra ~20
participantes por núcleo (ou ~40 em 4 núcleos) num cenário de reunião full-mesh — uma
diferença de ordem de grandeza que evidencia como o padrão de tráfego (um publisher/muitos
subscribers vs. todos publicando) domina o custo de CPU num SFU. Suporta WHIP para ingest,
mas não há menção a suporte WHEP na documentação oficial consultada — quem assiste usa o
protocolo de cliente próprio do Galène. Comparado a mediamtx/SRS, Galène tem uma proposta
de produto mais fechada (traz UI de sala embutida, chat, gravação) e uma comunidade bem
menor (1.390★).

## 5. Viabilidade de PHP do zero

**Em linguagem simples:** um servidor de mídia (a peça que recebe o vídeo de quem
transmite e reencaminha para quem assiste) não é só "mais uma API HTTP" — ele precisa
manter, para cada participante, uma conexão de rede criptografada e de baixíssima
latência, processando pacotes de vídeo/áudio dezenas de vezes por segundo, ano após ano,
sem travar nem vazar memória. Isso é um tipo de trabalho para o qual PHP nunca foi
desenhado, e a resposta curta é: **não construa o processamento de mídia em si em PHP**.
O papel do Laravel aqui deve ser outro — controlar _quem_ pode transmitir e _quem_ pode
assistir, não processar os pacotes de vídeo.

**Em detalhe técnico**, o que um SFU (Selective Forwarding Unit — o tipo de servidor de
mídia usado em todos os projetos da seção 3, que recebe uma cópia do stream do publisher e
encaminha uma cópia para cada subscriber sem misturar/recodificar, ao contrário de um MCU)
precisa fazer, e por que isso é hostil ao modelo de execução do PHP:

- **Negociação ICE** (Interactive Connectivity Establishment) — descobrir, para cada
  participante, o caminho de rede real (atrás de NAT, IPv4/IPv6, etc.) usando STUN/TURN, e
  manter esse estado vivo por toda a duração da chamada com keepalives constantes.
- **Handshake DTLS** (Datagram TLS) — estabelecer criptografia sobre UDP para cada conexão,
  incluindo troca de certificados e derivação de chaves.
- **SRTP** (Secure RTP) — criptografar/descriptografar **cada pacote de mídia** em tempo
  real usando as chaves derivadas do DTLS.
- **Manipulação de pacotes UDP em tempo real e RTP repacketization** — o SFU recebe
  pacotes RTP do publisher e precisa reescrevê-los (SSRC, sequence numbers, timestamps,
  possivelmente re-empacotar para simulcast/SVC) antes de reenviar a cada subscriber — isso
  acontece dezenas a centenas de vezes por segundo, por participante.
- **Congestion control / BWE** (Bandwidth Estimation, ex. algoritmo GCC — Google Congestion
  Control) — estimar continuamente a banda disponível para cada subscriber e ajustar a
  taxa de bits enviada, para não travar o stream de quem tem uma conexão ruim.
- Tudo isso roda em um **processo de longa duração, com estado por conexão, de baixíssima
  latência** — o oposto do modelo request-response e "stateless por requisição" no qual o
  PHP tradicionalmente roda (`php-fpm`).

### O que existe no ecossistema PHP hoje

Pesquisamos especificamente se ReactPHP, Swoole ou AMPHP implementam WebRTC nativo
(ICE/DTLS/SRTP), não apenas sinalização:

- **Swoole**: não tem suporte nativo a WebRTC. Em uma issue de 2019 ainda referenciada
  como discussão canônica no próprio repositório, um mantenedor explica que embora
  DataChannels (sobre SCTP) e MediaChannels (sobre SRTP) básicos sejam tecnicamente
  possíveis, ["full" WebRTC also requires SDP, ICE, STUN, etc, and full WebRTC support also
  requires media access"](https://github.com/swoole/swoole-src/issues/2828) — e a
  recomendação da própria comunidade Swoole é integrar com servidores de mídia dedicados
  (mediasoup, Janus) via sockets Unix/IPC, não implementar WebRTC dentro do Swoole.
- **AMPHP**: a organização oficial no GitHub (`amphp`) não lista nenhum pacote de WebRTC
  entre seus componentes (HTTP, WebSocket cliente/servidor, Postgres, Redis, Parallel,
  etc.) — [não há biblioteca de WebRTC no ecossistema AMPHP](https://github.com/amphp).
- **PHP-WebRTC/webrtc** (`quasarstream/webrtc` no Packagist) é a única biblioteca
  encontrada que reivindica ["a complete WebRTC implementation written entirely in PHP"](https://github.com/PHP-WebRTC/webrtc),
  cobrindo ICE, DTLS, SRTP, SCTP e RTP sobre ReactPHP, com submódulos dedicados a DTLS,
  STUN e SRTP na mesma organização. Avaliando sua maturidade com dados objetivos da API do
  GitHub: repositório criado em **14/05/2025**, último _push_ de código em **08/10/2025**
  (ou seja, sem commits novos há cerca de 10 meses até a data desta pesquisa), release mais
  recente `v1.0.3` (também de 08/10/2025), **129 estrelas**, **5 forks**, **3 issues
  abertas** e um único autor principal identificável (`aminyazdanpanah`). O próprio README
  exige `PHP ≥ 8.4` com extensões `FFI` e `GMP`, funciona hoje só em Linux ("Windows/macOS
  support planned"), e menciona que uma implementação de SFU e um pacote Laravel estão em
  "desenvolvimento privado" — ou seja, ainda não públicos. Esse conjunto de sinais (projeto
  jovem, mono-mantenedor, sem atividade recente, dependência de extensões não-padrão,
  suporte de plataforma incompleto) caracteriza um projeto **experimental/early-stage**,
  não algo em que valha a pena apostar a confiabilidade de uma feature de produto voltada a
  eventos ao vivo.

### A distinção que importa: processar mídia vs. orquestrar

Vale separar duas coisas que a pergunta "dá pra fazer em PHP?" mistura:

1. **Servidor de mídia em PHP** (processar os pacotes RTP/RTCP, fazer ICE/DTLS/SRTP,
   fazer BWE) — **inviável/desaconselhado**. Não existe implementação madura no ecossistema
   PHP (a única achada é experimental, sem tração), o modelo de execução do PHP (mesmo com
   Swoole/ReactPHP/AMPHP fornecendo I/O assíncrono) não tem as primitivas de baixo nível
   necessárias (não há biblioteca DTLS/SRTP de produção em PHP), e mesmo que existisse,
   reimplementar congestion control e ICE do zero é o tipo de trabalho que projetos como
   Pion (Go), libwebrtc (C++) ou os SFUs da seção 3 levaram anos para amadurecer.
2. **Orquestração em PHP** (emitir tokens/stream-keys, decidir quem pode publicar em qual
   sala, receber webhooks do media server quando alguém conecta/desconecta, expor o player
   embutido no portal, agendar lives, moderar) — **totalmente viável**, e é exatamente o
   papel que o Laravel deve cumprir. Todos os projetos da seção 3 foram desenhados
   assumindo que esse papel é de um sistema externo: mediamtx tem um hook de autenticação
   HTTP explícito para isso, LiveKit assina tokens JWT gerados no backend, Owncast tem
   webhooks e API por token — nenhum deles espera reimplementar seu próprio banco de
   usuários ou regras de agenda.

## 6. Arquitetura recomendada

A recomendação estrutural é: **media server como serviço separado** (container ou binário
próprio, com portas UDP abertas, CPU e banda dedicados) rodando ao lado do monolito, mais
um **módulo Laravel de orquestração** (`app-modules/live/`, seguindo a mesma convenção dos
módulos `integration-*` já existentes) cuidando de autenticação, emissão de tokens/stream
keys, agenda de lives, player embutido no portal e recebimento de webhooks.

```text
                         PRESENTER (navegador)                         VIEWERS (navegadores, N)
                    ┌───────────────────────────┐              ┌───────────────────────────────┐
                    │ getDisplayMedia (tela)     │              │  <video> + cliente WHEP        │
                    │ getUserMedia (webcam/mic)  │              │  (fetch + RTCPeerConnection)   │
                    │        │                   │              │              ▲                 │
                    │        ▼                   │              └──────────────┼─────────────────┘
                    │ RTCPeerConnection           │                             │
                    └────────────┼───────────────┘                             │
                                 │ (1) POST /whip  (SDP offer)                  │ (5) POST /whep (SDP offer)
                                 │     Authorization: Bearer <stream-token>     │     por espectador
                                 ▼                                             │
                    ┌─────────────────────────────────────────────────────────┴───────┐
                    │                     MEDIA SERVER (serviço à parte)               │
                    │           ex.: mediamtx / SRS — Go/C++, portas UDP abertas       │
                    │                                                                  │
                    │   WHIP (ingest) ──► SFU (fan-out 1→N) ──► WHEP (egress)          │
                    │                              │                                   │
                    └──────────────┬───────────────┼───────────────────────────────────┘
                                    │ (2) valida token   │ (6) evento: viewer entrou/saiu
                                    │  (auth HTTP hook)  │      (hook/webhook)
                                    ▼                    ▼
                    ┌───────────────────────────────────────────────────────────────┐
                    │        app-modules/live/  (módulo Laravel de orquestração)      │
                    │                                                                 │
                    │  • Auth/emissão de stream-key ou JWT   (3) responde 200/401     │
                    │  • Agenda de lives (Filament)                                   │
                    │  • Recebe webhooks do media server      (7) grava métricas      │
                    │  • Expõe endpoint HTTP de auth p/ o media server consultar      │
                    │  • Serve player embutido no portal      (4) portal carrega      │
                    │    (Livewire/Blade, aponta pro endpoint /whep)                  │
                    └───────────────────────────────────────────────────────────────┘
                                    ▲
                                    │  visita a página da live
                          ┌─────────┴─────────┐
                          │  portal (Blade)    │
                          │  módulo `portal`   │
                          └───────────────────┘
```

Legenda do fluxo numerado: **(1)** apresentador autenticado no portal recebe um
`stream-token` do módulo `live` e publica direto para o media server via WHIP; **(2)-(3)**
a cada tentativa de publish/read, o media server consulta de volta um endpoint HTTP do
Laravel para validar o token (mesmo padrão de auth externo do mediamtx); **(4)** o portal
Laravel serve a página da live com um player WHEP embutido, sem exigir instalação de nada
no espectador; **(5)** cada espectador abre sua própria sessão WHEP direto com o media
server (o Laravel não fica no caminho do vídeo, só na decisão de acesso); **(6)-(7)** o
media server notifica o Laravel via webhook a cada conexão/desconexão, alimentando métricas
e presença de espectadores exibidas no painel.

### Comparação dos caminhos mais fortes

**A. Owncast standalone + iframe/redirect no portal**

- Prós: instalação e operação mais simples de todo o comparativo; já vem com chat, HLS e
  scaling documentado via CDN.
- Contras: **desqualifica o requisito central** — não há ingest via navegador
  (`getDisplayMedia`/`getUserMedia`), só RTMP, então o "apresentador" precisaria rodar
  OBS/ffmpeg, não simplesmente abrir uma página. Acoplamento com o portal fica raso (só um
  iframe), então recursos como controle de quem pode transmitir, agenda integrada ao
  calendário de eventos do heartdevs e branding do player ficam limitados ao que o Owncast
  expõe.

**B. LiveKit (Ingress/Egress) + player customizado no portal**

- Prós: SDK de cliente maduro que abstrai captura, simulcast e reconexão; modelo de tokens
  JWT com grants (`canPublish`/`canSubscribe`) mapeia bem para "um transmite, muitos
  assistem"; webhooks de sala/track disponíveis; benchmark oficial de capacidade por node
  documentado.
- Contras: operacionalmente mais pesado — para cobrir o cenário do navegador nativamente
  seria preciso rodar o server LiveKit **e** o serviço de Egress (para HLS em escala) como
  peças adicionais; o SDK de cliente **não usa WHIP padrão** (protocolo proprietário via
  WebSocket), então o "navegador publica direto" fica dependente do SDK JS da LiveKit, não
  de um protocolo aberto; PHP não tem SDK de servidor oficial (só comunitário).

**C. mediamtx ou SRS com WHIP/WHEP nativos + player customizado no portal**

- Prós: aderência mais alta aos requisitos do enunciado — presenter e viewers usam só
  `fetch()`+`RTCPeerConnection` do navegador, sem SDK de terceiro; hooks/auth HTTP externos
  já pensados para um backend como o Laravel decidir quem publica/assiste; binário único em
  Go (mediamtx) ou C++ (SRS), fácil de containerizar; licença permissiva (MIT) nos dois;
  aderência total ao padrão `integration-*` do repo — o "serviço externo" é só mais uma
  peça de infra que o módulo Laravel orquestra via HTTP, igual a qualquer outro
  `integration-*`.
- Contras: sem player pronto — é preciso implementar (ou adaptar um player WHEP
  open-source existente) a lógica de `RTCPeerConnection` no portal; sem benchmark oficial
  de escala publicado (mediamtx) ou benchmark ausente (SRS) — exige spike de carga próprio
  antes de comprometer capacidade de audiência; menos "baterias inclusas" que LiveKit
  (sem simulcast documentado da mesma forma, sem SDK de reconexão pronto).

### Considerações de hospedagem e acoplamento

Em qualquer um dos caminhos B ou C, o media server precisa de:

- **Portas UDP abertas** para o tráfego RTP/RTCP — diferente do resto do monolito, que só
  precisa de HTTP/HTTPS (TCP 80/443). Isso implica um host ou container com regras de
  firewall/security group distintas do restante da infraestrutura Laravel.
- **CPU e banda dedicados e proporcionais ao número de espectadores simultâneos**, não ao
  tráfego HTTP do resto do site — o dimensionamento desse serviço é uma decisão de
  capacidade separada da do monolito.
- Isolamento de falha: um pico de audiência numa live não deve poder derrubar o resto do
  site, e vice-versa — outro argumento a favor de rodá-lo como processo/container
  separado, nunca embutido no mesmo processo PHP.

O acoplamento com o resto do sistema fica limitado a: (1) o módulo `live` chamando a
Control API do media server via HTTP para criar/checar streams, e (2) o media server
chamando de volta um endpoint HTTP do Laravel para autenticação e outro para webhooks — a
mesma forma de acoplamento que os módulos `integration-*` já usam com APIs de terceiro
(Discord, dev.to, Twitch), só que aqui o "terceiro" é um serviço que a própria heartdevs
hospeda.

## 7. Recomendação final e próximos passos

**Caminho recomendado: opção C — mediamtx (ou SRS) com WHIP/WHEP nativos, como serviço
separado, orquestrado por um novo módulo `app-modules/live/`.**

Justificativa: é a única combinação que cumpre literalmente o requisito "transmite pelo
navegador sem instalar nada" nos dois lados (publish e watch) usando só protocolos padrão
IETF, sem depender de um SDK proprietário (ao contrário do LiveKit) e sem exigir um encoder
externo (ao contrário do Owncast). Tem licença permissiva, é escrito em Go/C++ (alinhado à
preferência já demonstrada por soluções em Go), e o modelo de integração via HTTP
auth-hook + webhooks se encaixa diretamente no padrão `integration-*` já estabelecido no
repositório. Entre mediamtx e SRS especificamente, mediamtx tem a vantagem de ser um único
binário Go mais simples de operar e com uma Control API/hooks mais explicitamente
documentados para o caso "backend externo decide quem pode publicar/ler"; SRS tem o
ecossistema mais estrelado e um leque maior de protocolos de entrega (útil se, no futuro,
quiser LL-HLS/DASH para escalar além de WebRTC). Uma decisão final entre os dois deve vir
de um spike técnico, não desta pesquisa de gabinete.

Próximos passos concretos, antes de comprometer a arquitetura:

1. **Spike técnico de uma tarde**: subir mediamtx (ou SRS) localmente em Docker, publicar
   de um navegador via WHIP com um HTML estático (getDisplayMedia + getUserMedia +
   RTCPeerConnection) e assistir via WHEP em outra aba — validar na prática a promessa de
   "zero SDK" antes de desenhar o módulo Laravel.
2. **Spike de carga**: como nenhum dos dois candidatos (mediamtx/SRS) publica benchmark
   oficial de espectadores simultâneos por node, rodar um teste de carga próprio (N
   viewers WHEP simultâneos contra 1 publisher) para descobrir o número real na
   infraestrutura-alvo antes de prometer capacidade a organizadores de evento.
3. **Decidir o modelo de autenticação do media server** (auth HTTP externo vs. JWT/JWKS,
   no caso do mediamtx) e desenhar o contrato do endpoint que o `app-modules/live/` vai
   expor para ele consultar.
4. **Prototipar o player WHEP** no portal (Livewire/Blade) — decidir se é uma
   implementação própria minimalista ou se vale adotar um player WHEP open-source
   existente como base.
5. **Decidir hospedagem do media server** (mesma VM do monolito vs. host dedicado com
   portas UDP liberadas) — isso tem implicações de custo e operação que vão além do escopo
   desta pesquisa técnica.
6. Só depois desses spikes, desenhar o schema de dados do módulo `live` (lives, stream
   keys, presença de espectadores) e a UI de agendamento no Filament.

## 8. Fontes

### Specs W3C/IETF

- [Media Capture and Streams (getUserMedia)](https://www.w3.org/TR/mediacapture-streams/) — W3C Candidate Recommendation Draft, 9/10/2025
- [Screen Capture (getDisplayMedia)](https://www.w3.org/TR/screen-capture/) — W3C Working Draft, 27/08/2026
- [WebRTC](https://www.w3.org/TR/webrtc/) — W3C Recommendation, 13/03/2025
- [RFC 9725 — WebRTC-HTTP Ingestion Protocol (WHIP)](https://www.rfc-editor.org/rfc/rfc9725) — IETF Proposed Standard
- [draft-ietf-wish-whep — WebRTC-HTTP Egress Protocol (WHEP)](https://datatracker.ietf.org/doc/draft-ietf-wish-whep/) — IETF Internet-Draft ativo, versão 04 (22/06/2026)

### Owncast

- [github.com/owncast/owncast](https://github.com/owncast/owncast)
- [Broadcasting (ingest RTMP)](https://owncast.online/docs/broadcasting/)
- [Scaling Owncast](https://owncast.online/docs/scaling/)
- [API/Webhooks](https://owncast.online/docs/api/)
- [Issue #3429 — WHIP via broadcast-box, fechada como not planned](https://github.com/owncast/owncast/issues/3429)

### LiveKit

- [github.com/livekit/livekit](https://github.com/livekit/livekit)
- [Ingress overview (RTMP/WHIP para fontes externas)](https://docs.livekit.io/home/ingress/overview/)
- [Client Protocol (WebSocket + Protobuf, não WHIP)](https://docs.livekit.io/reference/internals/client-protocol/)
- [Authentication (tokens JWT)](https://docs.livekit.io/home/get-started/authentication/)
- [VideoGrant reference (canPublish/canSubscribe/roomJoin)](https://docs.livekit.io/reference/server-sdk-js/interfaces/VideoGrant.html)
- [Self-hosting benchmark](https://docs.livekit.io/home/self-hosting/benchmark/)
- [Use case: Livestreaming (claim de LiveKit Cloud)](https://livekit.com/use-cases/livestreaming)
- [agence104/livekit-server-sdk-php (SDK comunitário)](https://github.com/agence104/livekit-server-sdk-php)

### mediamtx

- [github.com/bluenviron/mediamtx](https://github.com/bluenviron/mediamtx)
- [Publish via WebRTC clients (WHIP)](https://mediamtx.org/docs/publish/webrtc-clients)
- [Read via WebRTC (WHEP)](https://mediamtx.org/docs/read/webrtc)
- [Authentication (HTTP externo / JWT)](https://mediamtx.org/docs/features/authentication)
- [Hooks/Webhooks](https://mediamtx.org/docs/features/hooks)
- [Control API reference](https://mediamtx.org/docs/references/control-api)

### OvenMediaEngine

- [github.com/OvenMediaLabs/OvenMediaEngine](https://github.com/OvenMediaLabs/OvenMediaEngine)
- [WebRTC/WHIP (ingest)](https://ovenmedia.com/docs/ome/live-source/webrtc)
- [WebRTC publishing (egress, signaling próprio)](https://ovenmedia.com/docs/ome/streaming/webrtc-publishing)
- [Performance tuning (claim de escala)](https://docs.ovenmediaengine.com/performance-tuning)

### Janus Gateway

- [github.com/meetecho/janus-gateway](https://github.com/meetecho/janus-gateway)
- [github.com/meetecho/simple-whip-server](https://github.com/meetecho/simple-whip-server)
- [WHIP-ing WebRTC to Janus! (blog oficial da Meetecho)](https://www.meetecho.com/blog/whip-janus/)

### mediasoup

- [github.com/versatica/mediasoup](https://github.com/versatica/mediasoup)

### SRS

- [github.com/ossrs/srs](https://github.com/ossrs/srs)
- [WebRTC (WHIP/WHEP)](https://ossrs.net/lts/en-us/docs/v6/doc/webrtc)

### Galène

- [github.com/jech/galene](https://github.com/jech/galene)
- [galene.org](https://galene.org/)

### Ecossistema PHP

- [PHP-WebRTC/webrtc](https://github.com/PHP-WebRTC/webrtc)
- [Swoole issue #2828 — discussão sobre (não-)suporte a WebRTC](https://github.com/swoole/swoole-src/issues/2828)
- [github.com/amphp (ausência de pacote WebRTC)](https://github.com/amphp)

### Repositório heartdevs.com

- `composer.json` / `composer.lock` (ausência de `laravel/reverb`)
- `app-modules/` (precedente `integration-devto`, `integration-discord`, `integration-github`, `integration-twitch`, `integration-whatsapp`)

## 9. Adendo (2026-08-29) — premissa revista: ingest via OBS/RTMP é aceito

A premissa original deste documento — "o apresentador transmite pelo navegador sem
instalar nada" — foi revista após a pesquisa: usar OBS (ou qualquer encoder RTMP) é
aceitável e até esperado, porque dá ao apresentador liberdade de composição (cenas,
overlays, mistura de tela + webcam + microfone) que uma página de captura no navegador
não oferece.

### O que muda na análise

- **Owncast volta ao jogo.** O único critério que o desqualificava (seção 4) era a
  ausência de ingest via navegador. Com OBS como encoder, o ingest RTMP do Owncast passa
  a ser um caminho plenamente válido.
- **WHIP deixa de ser requisito.** A página de publish no portal e o token de publish via
  navegador saem do escopo; o controle de quem transmite vira validação de stream key no
  ingest RTMP — todos os candidatos (Owncast, mediamtx, SRS, OME, LiveKit via Ingress)
  aceitam RTMP.
- **A composição tela+webcam (seção 2) deixa de ser problema da aplicação** — o OBS
  resolve a mistura das fontes antes do envio.
- **O critério decisivo migra para o lado do espectador**: latência da entrega e
  controle de acesso de quem assiste.
- O spike da seção 7 muda de "publicar via WHIP de uma aba" para "publicar do OBS via
  RTMP e assistir via WHEP e/ou HLS no navegador".

### Comparação revista (com OBS no ingest)

|                      | Owncast standalone                                            | mediamtx/SRS + módulo `live`                                          |
| -------------------- | ------------------------------------------------------------- | --------------------------------------------------------------------- |
| Ingest               | RTMP com stream key própria                                   | RTMP com stream key validada pelo Laravel (auth hook)                 |
| Entrega              | HLS (latência de vários segundos)                             | WHEP (sub-segundo) e/ou HLS, à escolha                                |
| Player/chat          | Prontos (página própria + embed via iframe)                   | Player a implementar no portal (hls.js ou cliente WHEP)               |
| Acesso do espectador | Instância pública; sem autenticação de espectador documentada | Autorização por espectador via o mesmo auth hook (inclusive para HLS) |
| Integração ao portal | Rasa (iframe, API de status, webhooks)                        | Total (identidade visual, login, métricas, agenda)                    |
| Operação             | Mínima (1 binário, tudo incluso)                              | 1 binário + desenvolvimento do módulo/player                          |

### Decisões tomadas (2026-08-29)

1. **Acesso: live pública** — qualquer pessoa assiste sem login.
2. **Latência: alguns segundos são aceitáveis** — entrega via HLS (player no navegador),
   que escala com CDN e dispensa player WebRTC custom.
3. **Experiência: embutida no portal**, com dois requisitos novos levantados na decisão:
    - **Chat próprio da heartdevs**: qualquer visitante vê o chat e a live; só usuários
      logados enviam mensagem. Isso descarta o chat embutido do Owncast (não integra com o
      login da heartdevs) e introduz a necessidade de infraestrutura de tempo real no
      monolito — Laravel Reverb (hoje ausente do projeto) ou, num MVP, polling via
      Livewire.
    - **Painel de admin**: gestão de lives (agenda, stream key, iniciar/encerrar,
      métricas de audiência) dentro do `panel-admin` (Filament) já existente.

### Recomendação consolidada

**mediamtx** como media server (OBS → RTMP com stream key validada pelo Laravel via auth
hook; entrega HLS pública) + módulo **`app-modules/live/`** (domínio: lives, stream keys,
presença; endpoints de auth hook e webhooks) + página de live no **portal** (player HLS +
chat Livewire) + resources de gestão no **`panel-admin`**.

O Owncast fica descartado nesta configuração: com o chat atrelado ao login da heartdevs e
a experiência embutida no portal, suas "baterias inclusas" (player, chat e página
prontos) deixam de agregar valor, e restaria a ele só o papel de engine RTMP→HLS — papel
que o mediamtx cumpre com modelo de integração (auth hook + hooks de eventos + Control
API) mais direto para um backend externo.
