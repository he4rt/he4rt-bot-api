---
title: Lives
icon: heroicon-o-video-camera
order: 1
---

# Lives

The **Lives** resource manages the platform's own live streams: creating a live, wiring it up in OBS, following the audience while it runs, moderating the chat, and closing it out when the broadcast ends.

## Creating a live

From the lives list, use the **Criar live** action and fill in a title and an optional description. Only one live can be current (any status other than "Encerrada") at a time — if you try to create a second one while another is still open, the panel shows a notification and no record is created. Close the current live first.

## Wiring up OBS

Open the live you just created. The **Ingest (OBS)** section shows the two values in the exact format OBS expects, under Settings → Stream (service: Custom):

- **Server** — paste into OBS's "Server" field (e.g. `rtmp://localhost:1935`).
- **Stream key** — paste into OBS's "Stream Key" field. It already carries the path and credentials (`live?user=he4rt&pass=<key>`); don't edit it.
- The stream key is masked by default. The eye icon reveals it; the copy icon always copies the real value, even while masked.

## Ending a live

Use the **Encerrar live** header action on the live's page. This action requires confirmation and is only available while the live has not already ended — once a live is "Encerrada" its stream key stops being accepted by the ingest server.

## Rotating the stream key

If a stream key leaks or you simply want a fresh one, use **Rotacionar stream key**. This also requires confirmation and is only available before the live ends. The previous key stops working immediately.

## Reading the audience chart

The audience chart on the live's page plots the number of concurrent viewers sampled over time, from oldest to newest. A flat or empty chart usually means the live hasn't started collecting samples yet, or has not received any viewers.

## Moderating the chat

The chat table on the live's page lists every message sent during the live, most recent first, together with its author and timestamp. Use the **Remover** row action to delete an inappropriate message — this requires confirmation and records the moderation event before removing the message.
