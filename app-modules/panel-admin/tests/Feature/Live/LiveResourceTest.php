<?php

declare(strict_types=1);

use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use He4rt\Activity\Message\Models\Message;
use He4rt\Identity\User\Models\User;
use He4rt\Live\Chat\Actions\SendChatMessage;
use He4rt\Live\Console\SimulateLiveChatCommand;
use He4rt\Live\Enums\LiveStatus;
use He4rt\Live\Models\Live;
use He4rt\PanelAdmin\Live\Resources\LiveResource\Pages\ListLives;
use He4rt\PanelAdmin\Live\Resources\LiveResource\Pages\ViewLive;
use He4rt\PanelAdmin\Live\Resources\LiveResource\Widgets\LiveChatMessages;
use Illuminate\Support\Facades\Process;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    config([
        'he4rt.admins' => 'danielhe4rt',
        'app.display_timezone' => 'America/Sao_Paulo',
    ]);

    $this->actingAs(User::factory()->create(['username' => 'danielhe4rt']));

    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

it('lista lives', function (): void {
    $lives = Live::factory()->count(2)->create(['status' => LiveStatus::Ended]);

    livewire(ListLives::class)
        ->loadTable()
        ->assertCanSeeTableRecords($lives);
});

it('cria live pela action da listagem', function (): void {
    livewire(ListLives::class)
        ->callAction('createLive', ['title' => 'Retrô de agosto', 'description' => 'Balanço'])
        ->assertNotified();

    expect(Live::query()->sole()->title)->toBe('Retrô de agosto');
});

it('bloqueia criação com live corrente aberta', function (): void {
    Live::factory()->create();

    livewire(ListLives::class)
        ->callAction('createLive', ['title' => 'Outra', 'description' => null])
        ->assertNotified();

    expect(Live::query()->count())->toBe(1);
});

it('encerra a live', function (): void {
    $live = Live::factory()->onAir()->create();

    livewire(ViewLive::class, ['record' => $live->id])
        ->callAction('endLive')
        ->assertNotified();

    expect($live->refresh()->status)->toBe(LiveStatus::Ended);
});

it('rotaciona a stream key', function (): void {
    $live = Live::factory()->create();
    $original = $live->stream_key;

    livewire(ViewLive::class, ['record' => $live->id])
        ->callAction('rotateStreamKey')
        ->assertNotified();

    expect($live->refresh()->stream_key)->not->toBe($original);
});

it('modera mensagem do chat a partir da view da live', function (): void {
    $live = Live::factory()->onAir()->create();
    $author = User::factory()->create();
    resolve(SendChatMessage::class)->execute($author, $live, 'mensagem imprópria');
    $message = Message::query()->sole();

    livewire(LiveChatMessages::class, ['record' => $live])
        ->callAction(TestAction::make('deleteChatMessage')->table($message))
        ->assertNotified();

    expect(Message::query()->count())->toBe(0);
});

it('esconde a action de simular comentários fora do ambiente local', function (): void {
    $live = Live::factory()->onAir()->create();

    livewire(ViewLive::class, ['record' => $live->id])
        ->assertActionHidden('simulateChat');
});

it('liga a simulação de comentários e dispara o comando em background', function (): void {
    app()->detectEnvironment(fn (): string => 'local');
    Process::fake();
    $live = Live::factory()->onAir()->create();

    livewire(ViewLive::class, ['record' => $live->id])
        ->assertActionVisible('simulateChat')
        ->callAction('simulateChat')
        ->assertNotified();

    expect(SimulateLiveChatCommand::cacheStore()->get(SimulateLiveChatCommand::cacheKey($live)))->toBeTrue();
    Process::assertRan(fn ($process): bool => str_contains($process->command, 'live:simulate-chat')
        && str_contains($process->command, $live->id)
        && $process->path === base_path());
});

it('desliga a simulação já ativa', function (): void {
    app()->detectEnvironment(fn (): string => 'local');
    Process::fake();
    $live = Live::factory()->onAir()->create();
    SimulateLiveChatCommand::cacheStore()->put(SimulateLiveChatCommand::cacheKey($live), value: true);

    livewire(ViewLive::class, ['record' => $live->id])
        ->callAction('simulateChat')
        ->assertNotified();

    expect(SimulateLiveChatCommand::cacheStore()->get(SimulateLiveChatCommand::cacheKey($live)))->toBeFalsy();
});

it('mantém a action como "parar" ao recarregar a página com a simulação ativa', function (): void {
    app()->detectEnvironment(fn (): string => 'local');
    Process::fake();
    $live = Live::factory()->onAir()->create();

    livewire(ViewLive::class, ['record' => $live->id])
        ->callAction('simulateChat');

    // Um novo mount simula a página sendo recarregada num novo request.
    livewire(ViewLive::class, ['record' => $live->id])
        ->assertActionHasLabel('simulateChat', 'Parar simulação de comentários');
});

it('desabilita a action de simular comentários em live encerrada', function (): void {
    app()->detectEnvironment(fn (): string => 'local');
    $live = Live::factory()->ended()->create();

    livewire(ViewLive::class, ['record' => $live->id])
        ->assertActionDisabled('simulateChat');
});

it('exibe o ingest nos dois campos do OBS com a chave mascarada por padrão', function (): void {
    $live = Live::factory()->create();

    livewire(ViewLive::class, ['record' => $live->id])
        ->assertSee('Servidor')
        ->assertSee(config()->string('live.rtmp_server'))
        ->assertSee('Chave de stream')
        ->assertSee(str_repeat('•', 12))
        ->set('revealStreamKey', value: true)
        ->assertSee(sprintf('live?user=he4rt&pass=%s', $live->stream_key));
});
