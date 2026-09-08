<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Laravel\Prompts\Elements\BulletedList;

use function Laravel\Prompts\callout;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\warning;

#[Description(description: 'Informative reminder to run php artisan boost:install after setup')]
#[Signature(signature: 'he4rt:boost-reminder
        {--who= : Show the fun facts for a specific contributor (GitHub handle)}')]
final class He4rtBoostReminder extends Command
{
    private const string COLOR = '#782bf1';

    public function handle(): void
    {
        $this->displayLogo();

        intro('✦ He4rt Developers :: Antes de começar ✦');

        warning('O Laravel Boost ainda não foi configurado neste ambiente.');

        callout(
            'Antes de usar agentes de IA',
            [
                'Rode `php artisan boost:install` para habilitar o MCP e as guidelines do projeto.',
                new BulletedList([
                    'Claude, Cursor, Copilot e outros agentes não devem executar tarefas até esse comando rodar.',
                    'As regras completas estão em `CLAUDE.md` e `AGENTS.md`.',
                ]),
            ],
        );

        $this->displayFunFact();
    }

    private function displayFunFact(): void
    {
        /** @var list<array{name: string, github: string, facts: list<string>}> $contributors */
        $contributors = config('fun-facts', []);

        if ($contributors === []) {
            return;
        }

        $who = $this->option('who');
        $who = is_string($who) ? $who : null;

        $contributor = $who === null
            ? Arr::random($contributors)
            : Arr::first($contributors, fn (array $c): bool => strcasecmp($c['github'], $who) === 0);

        if (!$contributor) {
            warning("Nenhum contribuidor encontrado no config/fun-facts.php com o GitHub '{$who}'.");

            return;
        }

        if ($contributor['facts'] === []) {
            warning("{$contributor['name']} (@{$contributor['github']}) ainda não tem fun facts cadastrados.");

            return;
        }

        $fact = Arr::random($contributor['facts']);

        callout("Você sabia? — {$contributor['name']} (@{$contributor['github']})", $fact);
    }

    private function displayLogo(): void
    {
        $lines = [
            '  ██╗  ██╗ ███████╗ ██╗  ██╗ ██████╗  ████████╗',
            '  ██║  ██║ ██╔════╝ ██║  ██║ ██╔══██╗ ╚══██╔══╝',
            '  ███████║ █████╗   ███████║ ██████╔╝    ██║   ',
            '  ██╔══██║ ██╔══╝   ╚════██║ ██╔══██╗    ██║   ',
            '  ██║  ██║ ███████╗      ██║ ██║  ██║    ██║   ',
            '  ╚═╝  ╚═╝ ╚══════╝      ╚═╝ ╚═╝  ╚═╝    ╚═╝   ',
        ];

        $this->newLine();

        foreach ($lines as $line) {
            $this->line(sprintf('<fg=%s>%s</>', self::COLOR, $line));
        }
    }
}
