<?php

declare(strict_types=1);

use Filament\Actions\DeleteAction;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use He4rt\Identity\User\Models\User;
use He4rt\PanelAdmin\Filament\Resources\Skills\Pages\CreateSkill;
use He4rt\PanelAdmin\Filament\Resources\Skills\Pages\ListSkills;
use He4rt\Profile\Enums\SkillCategory;
use He4rt\Profile\Models\Profile;
use He4rt\Profile\Models\ProfileSkill;
use He4rt\Profile\Models\Skill;
use Illuminate\Support\Str;

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
    livewire(ListSkills::class)->loadTable()->assertOk();
});

test('preencher o nome gera o slug', function (): void {
    livewire(CreateSkill::class)
        ->fillForm(['name' => 'Rust Lang'])
        ->assertSchemaStateSet(['slug' => 'rust-lang']);
});

test('cria uma skill', function (): void {
    // O catálogo já vem semeado pela migration de skills: usar um slug sintético.
    livewire(CreateSkill::class)
        ->fillForm([
            'name' => 'Linguagem Fictícia',
            'slug' => 'linguagem-ficticia',
            'category' => SkillCategory::Language->value,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Skill::query()->where('slug', 'linguagem-ficticia')->exists())->toBeTrue();
});

test('o filtro de categoria filtra as skills', function (): void {
    // O catálogo semeado pela migration passa de 100 linhas e a tabela pagina:
    // a busca restringe o conjunto para a asserção ser determinística.
    $language = Skill::factory()->create(['name' => 'Zoltrix Lang', 'category' => SkillCategory::Language]);
    $tool = Skill::factory()->create(['name' => 'Zoltrix Tool', 'category' => SkillCategory::Tool]);

    livewire(ListSkills::class)
        ->loadTable()
        ->searchTable('Zoltrix')
        ->filterTable('category', [SkillCategory::Language->value])
        ->assertCanSeeTableRecords([$language])
        ->assertCanNotSeeTableRecords([$tool]);
});

test('o filtro de skills sem perfil mostra só as não usadas', function (): void {
    $unused = Skill::factory()->create(['name' => 'Zoltrix Livre']);
    $used = Skill::factory()->create(['name' => 'Zoltrix Usada']);

    $profile = Profile::query()->where('user_id', $this->admin->getKey())->sole();
    ProfileSkill::factory()->create([
        'profile_id' => $profile->getKey(),
        'skill_id' => $used->getKey(),
    ]);

    livewire(ListSkills::class)
        ->loadTable()
        ->searchTable('Zoltrix')
        ->filterTable('unused')
        ->assertCanSeeTableRecords([$unused])
        ->assertCanNotSeeTableRecords([$used]);
});

test('a exclusão fica escondida para skill em uso', function (): void {
    $used = Skill::factory()->create(['name' => 'Zoltrix Usada']);

    $profile = Profile::query()->where('user_id', $this->admin->getKey())->sole();
    ProfileSkill::factory()->create([
        'profile_id' => $profile->getKey(),
        'skill_id' => $used->getKey(),
    ]);

    livewire(ListSkills::class)
        ->loadTable()
        ->searchTable('Zoltrix')
        ->assertActionHidden(TestAction::make(DeleteAction::class)->table($used));
});

test('a exclusão fica visível para skill sem vínculo', function (): void {
    $unused = Skill::factory()->create(['name' => 'Zoltrix Livre']);

    livewire(ListSkills::class)
        ->loadTable()
        ->searchTable('Zoltrix')
        ->assertActionVisible(TestAction::make(DeleteAction::class)->table($unused));
});

test('valida os dados do form', function (array $data, array $errors): void {
    livewire(CreateSkill::class)
        ->fillForm([
            'name' => 'Base Sintética',
            'slug' => 'base-sintetica',
            'category' => SkillCategory::Language->value,
            ...$data,
        ])
        ->call('create')
        ->assertHasFormErrors($errors);
})->with([
    '`name` é obrigatório' => [['name' => null], ['name' => 'required']],
    '`name` tem no máximo 255' => [['name' => Str::random(256)], ['name' => 'max']],
    '`slug` é obrigatório' => [['slug' => null], ['slug' => 'required']],
    '`category` é obrigatória' => [['category' => null], ['category' => 'required']],
]);
