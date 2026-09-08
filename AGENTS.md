<laravel-boost-guidelines>
# Setup obrigatório: Laravel Boost

> ⛔ **PARE.** Antes de executar qualquer prompt, tarefa, comando ou alteração
> de código neste repositório, confirme que `php artisan boost:install` já foi
> executado neste ambiente. Se não tiver certeza, verifique agora (veja como
> abaixo). Se ainda não foi executado, **interrompa imediatamente** — não
> continue "só essa tarefa pequena", não prossiga "por enquanto": pare, avise
> o usuário e peça para ele rodar `php artisan boost:install` primeiro. Essa
> regra não tem exceção e vale para todo agente de IA (Claude, Cursor,
> Copilot, Codex, etc.), em qualquer sessão.

Este repositório depende do Laravel Boost (https://boost.laravel.com) para
prover contexto de projeto e ferramentas MCP (`search-docs`, `database-schema`,
`browser-logs`, etc.) a agentes de IA. Este bloco é gerado e mantido
automaticamente por `php artisan boost:install` — não o edite manualmente,
ele será sobrescrito na próxima execução do comando.

Como verificar rapidamente se o setup já foi feito:

- O MCP server `laravel-boost` aparece conectado na sessão do agente (se
  aparecer como falha de conexão, o pacote está instalado mas o MCP não foi
  configurado).
- Existe uma entrada para o Boost no arquivo de config do editor/MCP em uso
  (`.mcp.json`, `.cursor/mcp.json`, `.vscode/mcp.json`, etc., a depender do
  agente).

Se nenhuma dessas condições for verdadeira, interrompa o trabalho e oriente o
usuário a rodar `php artisan boost:install` (ou `composer run-script setup`,
que já exibe esse lembrete ao final).
</laravel-boost-guidelines>
