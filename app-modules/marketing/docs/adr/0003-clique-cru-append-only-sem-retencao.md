---
type: adr
title: 'Clique gravado cru, append-only e sem retenção'
module: marketing
status: accepted
date: 2026-08-21
deciders:
    - danielhe4rt
related:
    spec: marketing/2026-08-21-encurtador-de-links
---

# ADR-0003: Clique gravado cru, append-only e sem retenção

## Contexto

A motivação nº 1 do módulo é **métrica de campanha**: saber quantos cliques cada canal gerou e
comparar Discord, X, newsletter e dev.to. A pergunta de projeto foi quanto detalhe guardar por
clique, e por quanto tempo.

O contexto interno pesa aqui: a tabela `messages` deste mesmo projeto já bate 2,3 GB, produzida
exatamente pelo padrão "guarda tudo, para sempre". Um link viral postado no Discord é o caso que
gera volume.

## Alternativas consideradas

- **Só contador agregado.** `clicks_count` no link mais uma tabela diária. Nunca cresce, mas jamais
  responde "de qual canal veio aquele pico?" depois do fato.
- **Híbrido**: linha por clique com retenção de ~90 dias + rollup diário permanente. Drill-down
  recente e série histórica, sem crescimento indefinido.
- **Raw completo, sem retenção.** Uma linha por clique, guardada para sempre, com IP e User-Agent
  completos.

## Decisão

**Raw completo, sem retenção** — decisão explícita do produto, tomada com o custo na mesa.

- Uma linha em `marketing_short_link_clicks` por clique **resolvido**. Slug morto (inexistente,
  desativado, expirado) não é tráfego de campanha e não grava nada.
- Campos: `clicked_at`, `ip_address`, `user_agent`, `referer`, `country_code`, `device_type`,
  `browser`, `os`, `is_bot`, `bot_name`, `utm_source/medium/campaign`, `user_id`.
- **Chave primária `bigIncrements`, divergindo do padrão UUID do projeto.** É uma tabela append-only
  de alto volume; UUID v4 em índice B-tree grande fragmenta e infla. A divergência é consciente e
  está documentada na migration para não parecer descuido em review.
- **Gravação assíncrona.** O redirect responde antes do INSERT; um job na fila faz o parse de UA
  (`matomo/device-detector`), o INSERT e os incrementos.
- **Bot é gravado, não descartado.** Discord, WhatsApp, X e Slack batem no link para gerar preview
  (unfurl) — 5 a 10 acessos fantasma por post. A linha entra com `is_bot = true` e existe um
  contador separado (`human_clicks_count`), para que a contagem visível não minta.
- **`country_code` vem do header `CF-IPCountry`**, já disponível porque o Cloudflare está na frente
  da aplicação. Sem MaxMind, sem base para atualizar.

## Consequências

### Dívida de LGPD — em aberto

`ip_address` e `user_agent` são **dado pessoal**. Guardados indefinidamente, exigem base legal
declarada (provavelmente legítimo interesse, art. 7º IX) e um caminho de exclusão a pedido do
titular (art. 18).

**Este repositório não tem política de privacidade nem termos de uso.** A base legal não está
declarada em lugar nenhum. Isso não bloqueou a implementação, mas é dívida real e aberta, e deve
virar issue própria de documentação.

Se em algum momento se quiser reduzir a exposição sem perder capacidade analítica, o caminho mais
barato é trocar `ip_address` por `sha256(ip + salt + dia)`: mantém "visitantes únicos por dia" e
deixa de guardar dado pessoal recuperável. Fica registrado como opção, não como plano.

A tabela de cliques **não expõe IP nem User-Agent na interface**. Continuam gravados conforme esta
decisão, mas não vão para a tela sem necessidade.

### Volume

~400 bytes por linha com IP e UA completos: 1 milhão de cliques ≈ 400 MB mais índices. Não é
problema no ano 1; vira problema se um link estourar. A mitigação futura é partição declarativa por
mês em `clicked_at` no Postgres — aditiva, sem mudar o código de escrita.

### Operacional

Como a gravação é assíncrona, **sem worker consumindo a fila o clique nunca vira linha** — e o
redirect continua funcionando normalmente. É falha silenciosa: os links funcionam e o dashboard fica
zerado. Worker supervisionado é pré-requisito de deploy, não detalhe de infra.
