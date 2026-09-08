<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use He4rt\Contents\Models\ContentEntry;
use He4rt\Identity\User\Models\User;
use He4rt\PanelAdmin\Filament\Resources\ContentEntries\ContentEntryResource;
use He4rt\PanelAdmin\Filament\Resources\ContentEntries\Pages\EditContentEntry;
use He4rt\PanelAdmin\Filament\Resources\ContentEntries\Pages\ListContentEntries;
use He4rt\PanelAdmin\Filament\Resources\ContentEntries\Pages\ViewContentEntry;
use He4rt\PanelAdmin\Filament\Resources\ContentEntries\Widgets\ContentEntryStatsWidget;
use Illuminate\Foundation\Console\QueuedCommand;
use Illuminate\Support\Facades\Queue;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    config([
        'app.display_timezone' => 'America/Sao_Paulo',
    ]);

    $this->admin = User::factory()->superAdmin()->create();

    $this->actingAs($this->admin);

    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

test('a listagem carrega para admin', function (): void {
    $entries = ContentEntry::factory()->count(3)->create();

    livewire(ListContentEntries::class)
        ->loadTable()
        ->assertOk()
        ->assertCanSeeTableRecords($entries);
});

test('a capa abre a listagem, antes do título', function (): void {
    ContentEntry::factory()->create(['thumbnail_url' => 'https://example.test/capa.png']);

    $columns = array_keys(
        livewire(ListContentEntries::class)->loadTable()->instance()->getTable()->getColumns(),
    );

    expect($columns[0])->toBe('thumbnail_url')
        ->and($columns[1])->toBe('title');
});

test('artigo não pode ser criado nem apagado pelo painel', function (): void {
    $entry = ContentEntry::factory()->create();

    expect(ContentEntryResource::canCreate())->toBeFalse()
        ->and(ContentEntryResource::canDelete($entry))->toBeFalse()
        ->and(ContentEntryResource::canDeleteAny())->toBeFalse()
        ->and(ContentEntryResource::getPages())->not->toHaveKey('create');
});

test('o form só permite editar o vínculo de autor', function (): void {
    $entry = ContentEntry::factory()->create();

    livewire(EditContentEntry::class, ['record' => $entry->getKey()])
        ->assertSchemaComponentExists('author_id')
        ->assertSchemaComponentDoesNotExist('title')
        ->assertSchemaComponentDoesNotExist('url')
        ->assertSchemaComponentDoesNotExist('tags')
        ->assertSchemaComponentDoesNotExist('reactions_count');
});

test('vincular um autor persiste na entrada', function (): void {
    $entry = ContentEntry::factory()->create(['author_id' => null]);
    $author = User::factory()->create();

    livewire(EditContentEntry::class, ['record' => $entry->getKey()])
        ->fillForm(['author_id' => $author->getKey()])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($entry->refresh()->author_id)->toBe($author->getKey());
});

test('o filtro de artigos sem autor mostra só os não vinculados', function (): void {
    $unlinked = ContentEntry::factory()->create(['author_id' => null]);
    $linked = ContentEntry::factory()->authoredBy($this->admin)->create();

    livewire(ListContentEntries::class)
        ->loadTable()
        ->filterTable('unlinked_author')
        ->assertCanSeeTableRecords([$unlinked])
        ->assertCanNotSeeTableRecords([$linked]);
});

test('o filtro de métricas desatualizadas ignora as sincronizadas na semana', function (): void {
    $fresh = ContentEntry::factory()->create(['metrics_synced_at' => now()->subDay()]);
    $stale = ContentEntry::factory()->create(['metrics_synced_at' => now()->subMonth()]);
    $never = ContentEntry::factory()->create(['metrics_synced_at' => null]);

    livewire(ListContentEntries::class)
        ->loadTable()
        ->filterTable('stale_metrics')
        ->assertCanSeeTableRecords([$stale, $never])
        ->assertCanNotSeeTableRecords([$fresh]);
});

test('a ação de sincronizar enfileira o comando em vez de rodar na requisição', function (): void {
    Queue::fake();

    livewire(ListContentEntries::class)
        ->loadTable()
        ->callAction('sync')
        ->assertNotified();

    Queue::assertPushed(QueuedCommand::class);
});

test('o cabeçalho da view carrega título, provider e data em vez de repeti-los no corpo', function (): void {
    $entry = ContentEntry::factory()->create([
        'title' => 'Como criei um agente de IA',
        'published_at' => now()->subDay(),
    ]);
    $entry->contentable->update(['reading_time_minutes' => 9]);

    $page = livewire(ViewContentEntry::class, ['record' => $entry->getKey()]);
    $instance = $page->instance();

    expect($instance->getTitle())->toBe('Como criei um agente de IA')
        ->and($instance->getBreadcrumbs())->toBe([
            ContentEntryResource::getUrl() => 'Artigos',
            'Detalhes',
        ])
        ->and($instance->getSubheading())->toContain('Dev.to')
        ->and($instance->getSubheading())->toContain('9 min de leitura');

    // O título não volta como campo do infolist.
    $page->assertSchemaComponentDoesNotExist('title');
});

test('a URL canônica só aparece quando difere do endereço', function (): void {
    $same = ContentEntry::factory()->create(['url' => 'https://dev.to/he4rt/artigo']);
    $same->contentable->update(['canonical_url' => 'https://dev.to/he4rt/artigo']);

    livewire(ViewContentEntry::class, ['record' => $same->getKey()])
        ->assertSchemaComponentHidden('contentable.canonical_url');

    $different = ContentEntry::factory()->create(['url' => 'https://dev.to/he4rt/outro']);
    $different->contentable->update(['canonical_url' => 'https://blog.he4rt.dev/outro']);

    livewire(ViewContentEntry::class, ['record' => $different->getKey()])
        ->assertSchemaComponentVisible('contentable.canonical_url');
});

test('o autor vinculado só aparece quando existe', function (): void {
    $unlinked = ContentEntry::factory()->create(['author_id' => null]);

    livewire(ViewContentEntry::class, ['record' => $unlinked->getKey()])
        ->assertSchemaComponentHidden('author.username');

    $linked = ContentEntry::factory()->authoredBy($this->admin)->create();

    livewire(ViewContentEntry::class, ['record' => $linked->getKey()])
        ->assertSchemaComponentVisible('author.username');
});

test('o widget de stats conta o acervo e a fila de curadoria', function (): void {
    ContentEntry::factory()->count(2)->create(['author_id' => null]);
    ContentEntry::factory()->authoredBy($this->admin)->create();

    livewire(ContentEntryStatsWidget::class)
        ->assertOk()
        ->assertSee('Artigos no acervo')
        ->assertSee('Sem autor vinculado')
        ->assertSee('Aguardando vínculo manual');
});

test('o widget avisa quando as métricas estão paradas', function (): void {
    ContentEntry::factory()->create(['metrics_synced_at' => now()->subMonth()]);

    livewire(ContentEntryStatsWidget::class)
        ->assertSee('Métricas paradas há mais de uma semana');

    ContentEntry::factory()->create(['metrics_synced_at' => now()]);

    livewire(ContentEntryStatsWidget::class)
        ->assertSee('Métricas em dia');
});

test('o widget não quebra com o acervo vazio', function (): void {
    livewire(ContentEntryStatsWidget::class)
        ->assertOk()
        ->assertSee('Todo artigo tem dono')
        ->assertSee('Nunca');
});

test('a listagem exibe o widget de stats no topo', function (): void {
    ContentEntry::factory()->create();

    $widgets = livewire(ListContentEntries::class)
        ->loadTable()
        ->instance()
        ->getVisibleHeaderWidgets();

    expect($widgets)->not->toBeEmpty();
});
