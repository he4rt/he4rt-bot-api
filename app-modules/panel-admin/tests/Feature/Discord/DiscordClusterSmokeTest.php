<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use He4rt\Identity\User\Models\User;
use He4rt\IntegrationDiscord\Models\DiscordChannel;
use He4rt\IntegrationDiscord\Models\DiscordEventLog;
use He4rt\IntegrationDiscord\Models\DiscordGuild;
use He4rt\IntegrationDiscord\Models\DiscordMember;
use He4rt\IntegrationDiscord\Models\DiscordRole;
use He4rt\PanelAdmin\Discord\Pages\DiscordDashboard;
use He4rt\PanelAdmin\Discord\Resources\DiscordChannels\Pages\ListDiscordChannels;
use He4rt\PanelAdmin\Discord\Resources\DiscordChannels\Pages\ViewDiscordChannel;
use He4rt\PanelAdmin\Discord\Resources\DiscordEventLogs\Pages\ListDiscordEventLogs;
use He4rt\PanelAdmin\Discord\Resources\DiscordEventLogs\Pages\ViewDiscordEventLog;
use He4rt\PanelAdmin\Discord\Resources\DiscordGuilds\Pages\ListDiscordGuilds;
use He4rt\PanelAdmin\Discord\Resources\DiscordGuilds\Pages\ViewDiscordGuild;
use He4rt\PanelAdmin\Discord\Resources\DiscordGuilds\RelationManagers\ChannelsRelationManager;
use He4rt\PanelAdmin\Discord\Resources\DiscordGuilds\RelationManagers\MembersRelationManager;
use He4rt\PanelAdmin\Discord\Resources\DiscordGuilds\RelationManagers\RolesRelationManager;
use He4rt\PanelAdmin\Discord\Resources\DiscordMembers\Pages\ListDiscordMembers;
use He4rt\PanelAdmin\Discord\Resources\DiscordMembers\Pages\ViewDiscordMember;
use He4rt\PanelAdmin\Discord\Resources\DiscordRoles\Pages\ListDiscordRoles;
use He4rt\PanelAdmin\Discord\Resources\DiscordRoles\Pages\ViewDiscordRole;
use He4rt\PanelAdmin\Discord\Widgets\DiscordStatsWidget;
use He4rt\PanelAdmin\Discord\Widgets\EventsPerDayChartWidget;
use He4rt\PanelAdmin\Discord\Widgets\MemberGrowthChartWidget;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $user = User::factory()->superAdmin()->create();

    $this->actingAs($user);

    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

test('lista as guilds', function (): void {
    $guild = DiscordGuild::factory()->create();

    livewire(ListDiscordGuilds::class)
        ->loadTable()
        ->assertCanSeeTableRecords([$guild]);
});

test('exibe a view da guild', function (): void {
    $guild = DiscordGuild::factory()->create();

    livewire(ViewDiscordGuild::class, ['record' => $guild->id])
        ->assertOk();
});

test('lista canais agrupados sem categorias', function (): void {
    $guild = DiscordGuild::factory()->create();

    $category = DiscordChannel::factory()
        ->category()
        ->recycle($guild)
        ->create();

    $channels = DiscordChannel::factory()
        ->count(2)
        ->recycle($guild)
        ->create(['parent_id' => $category->id]);

    livewire(ListDiscordChannels::class)
        ->loadTable()
        ->assertCanSeeTableRecords($channels)
        ->assertCanNotSeeTableRecords([$category]);
});

test('pagina canais agrupados com mais de uma página', function (): void {
    // Regressão: com PaginationMode::Cursor (padrão global do painel), renderizar
    // o link da próxima página de uma tabela agrupada por relacionamento estoura
    // em array_flip() — só reproduz com mais registros que o page size (10).
    $guild = DiscordGuild::factory()->create();

    $categories = DiscordChannel::factory()
        ->count(2)
        ->category()
        ->recycle($guild)
        ->create();

    foreach ($categories as $category) {
        DiscordChannel::factory()
            ->count(8)
            ->recycle($guild)
            ->create(['parent_id' => $category->id]);
    }

    livewire(ListDiscordChannels::class)
        ->loadTable()
        ->assertOk();
});

test('exibe a view do canal', function (): void {
    $guild = DiscordGuild::factory()->create();
    $channel = DiscordChannel::factory()->recycle($guild)->create();

    livewire(ViewDiscordChannel::class, ['record' => $channel->id])
        ->assertOk();
});

test('lista membros ativos por padrão', function (): void {
    $guild = DiscordGuild::factory()->create();

    $active = DiscordMember::factory()->recycle($guild)->create();
    $left = DiscordMember::factory()->recycle($guild)->left()->create();

    livewire(ListDiscordMembers::class)
        ->loadTable()
        ->assertCanSeeTableRecords([$active])
        ->assertCanNotSeeTableRecords([$left])
        ->filterTable('left_at', true)
        ->assertCanSeeTableRecords([$left])
        ->assertCanNotSeeTableRecords([$active]);
});

test('exibe a view do membro', function (): void {
    $guild = DiscordGuild::factory()->create();
    $member = DiscordMember::factory()->recycle($guild)->create();

    livewire(ViewDiscordMember::class, ['record' => $member->id])
        ->assertOk();
});

test('lista roles ordenadas por position desc', function (): void {
    $guild = DiscordGuild::factory()->create();

    $lower = DiscordRole::factory()->recycle($guild)->create(['position' => 1]);
    $higher = DiscordRole::factory()->recycle($guild)->create(['position' => 10]);

    livewire(ListDiscordRoles::class)
        ->loadTable()
        ->assertCanSeeTableRecords([$higher, $lower], inOrder: true);
});

test('exibe a view do role', function (): void {
    $guild = DiscordGuild::factory()->create();
    $role = DiscordRole::factory()->recycle($guild)->create(['color' => 0]);

    livewire(ViewDiscordRole::class, ['record' => $role->id])
        ->assertOk();
});

test('lista event logs', function (): void {
    $logs = DiscordEventLog::factory()->count(2)->create();

    livewire(ListDiscordEventLogs::class)
        ->loadTable()
        ->assertCanSeeTableRecords($logs);
});

test('filtra event logs por tipo', function (): void {
    $banEvent = DiscordEventLog::factory()->create(['event_type' => 'GUILD_BAN_ADD']);
    $messageEvent = DiscordEventLog::factory()->create(['event_type' => 'MESSAGE_CREATE']);

    livewire(ListDiscordEventLogs::class)
        ->loadTable()
        ->filterTable('event_type', ['GUILD_BAN_ADD'])
        ->assertCanSeeTableRecords([$banEvent])
        ->assertCanNotSeeTableRecords([$messageEvent]);
});

test('exibe a view do event log com payload', function (): void {
    $log = DiscordEventLog::factory()->create();

    livewire(ViewDiscordEventLog::class, ['record' => $log->id])
        ->assertOk();
});

test('dashboard renderiza', function (): void {
    livewire(DiscordDashboard::class)
        ->assertOk();

    livewire(DiscordStatsWidget::class)
        ->assertOk();

    livewire(EventsPerDayChartWidget::class)
        ->assertOk();

    livewire(MemberGrowthChartWidget::class)
        ->assertOk();
});

test('relation managers da guild renderizam', function (): void {
    $guild = DiscordGuild::factory()->create();

    DiscordChannel::factory()->recycle($guild)->create();
    DiscordRole::factory()->recycle($guild)->create();
    DiscordMember::factory()->recycle($guild)->create();

    livewire(ChannelsRelationManager::class, [
        'ownerRecord' => $guild,
        'pageClass' => ViewDiscordGuild::class,
    ])->assertOk();

    livewire(RolesRelationManager::class, [
        'ownerRecord' => $guild,
        'pageClass' => ViewDiscordGuild::class,
    ])->assertOk();

    livewire(MembersRelationManager::class, [
        'ownerRecord' => $guild,
        'pageClass' => ViewDiscordGuild::class,
    ])->assertOk();
});
