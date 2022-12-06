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

* [x] Gerenciamento de Usuários 
* [x] Pontos Diários
* [] Sistemas de Temporada 
* [] Sistema de Apostas
* [] Ranking
* [] Sistema de Leveling 

## Endpoints

TODO: documentar

### Requirements

- [PHP 7.4](https://php.net)
- [Composer 2](https://getcomposer.org)

### Run

```bash
php artisan migrate --seed

php -S 0.0.0.0:8000
vendor/bin/phpunit 
```

### Gerar Documentação

A documentação dos endpoints é gerada via [scribe](https://scribe.knuckles.wtf/laravel/getting-started).

Para gerar a Doc localmente, basta rodar o comando abaixo.

```bash
php artisan scribe:generate
```

A doc ficará disponível no endpoint `/docs`.

## Differences to [v1](https://github.com/he4rt/He4rt-Bot)

- PHP 5.6 > PHP 7.4;
- Lumen 6 > Lumen 8;
- Implementação de Actions
- Adição de Testes de Integração
- Documentação Apropriada

### Lista de Refatoração

- Banco de dados
- Isolar Requests
