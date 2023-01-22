# He4rtBot Discord API
<p align="center">
  <a href="https://discord.gg/he4rt">
    <img src="./.github/logo.png" height="220">
  </a>
</p>

<h1 align="center">
He4rt Discord Bot API
</h1>
<p align="center">
  <a href="https://discord.gg/he4rt"><img src="https://img.shields.io/endpoint?url=https://github.com/he4rt/he4rt-bot-api/blob/main/version.json"></a>
  <a href="https://discord.gg/he4rt"><img src="https://img.shields.io/github/license/he4rt/he4rt-bot-api?color=A655FF&style=for-the-badge"></a>
<p>

## Features

## Endpoints

TODO: documentar

### Requirements

- [Docker](https://docs.docker.com/get-docker/)

### Configuring

```bash
docker-compose up -d
php artisan key:generate
docker exec discord-bot-api php artisan migrate --seed
```

### Running tests

```bash
docker exec app php artisan migrate --seed --database=testing
docker exec app vendor/bin/phpunit
```
