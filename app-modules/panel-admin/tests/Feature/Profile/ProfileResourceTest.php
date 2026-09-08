<?php

declare(strict_types=1);

use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use He4rt\Identity\User\Models\User;
use He4rt\PanelAdmin\Filament\Resources\Profiles\Pages\EditProfile;
use He4rt\PanelAdmin\Filament\Resources\Profiles\Pages\ListProfiles;
use He4rt\PanelAdmin\Filament\Resources\Profiles\ProfileResource;
use He4rt\PanelAdmin\Filament\Resources\Profiles\RelationManagers\ProfileSkillsRelationManager;
use He4rt\PanelAdmin\Filament\Resources\Profiles\RelationManagers\WorkExperiencesRelationManager;
use He4rt\Profile\Enums\SeniorityLevel;
use He4rt\Profile\Enums\SkillProficiency;
use He4rt\Profile\Models\Profile;
use He4rt\Profile\Models\Skill;
use He4rt\Profile\Models\WorkExperience;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    config([
        'app.display_timezone' => 'America/Sao_Paulo',
    ]);

    $this->admin = User::factory()->superAdmin()->create();

    $this->actingAs($this->admin);

    Filament::setCurrentPanel(Filament::getPanel('admin'));

    // O UserObserver já cria o perfil junto do usuário.
    $this->profile = Profile::query()->where('user_id', $this->admin->getKey())->sole();
});

test('a listagem carrega para admin', function (): void {
    livewire(ListProfiles::class)->loadTable()->assertOk();
});

test('o perfil não pode ser criado nem apagado pelo painel', function (): void {
    expect(ProfileResource::canCreate())->toBeFalse()
        ->and(ProfileResource::canDelete($this->profile))->toBeFalse()
        ->and(ProfileResource::canDeleteAny())->toBeFalse()
        ->and(ProfileResource::getPages())->not->toHaveKey('create');
});

test('o form não expõe a remuneração pretendida', function (): void {
    livewire(EditProfile::class, ['record' => $this->profile->getKey()])
        ->assertSchemaComponentDoesNotExist('expected_salary_min')
        ->assertSchemaComponentDoesNotExist('expected_salary_max');
});

test('a disponibilidade de início só aparece para quem está aberto a propostas', function (): void {
    livewire(EditProfile::class, ['record' => $this->profile->getKey()])
        ->fillForm(['available_for_proposals' => false])
        ->assertSchemaComponentHidden('start_availability')
        ->fillForm(['available_for_proposals' => true])
        ->assertSchemaComponentVisible('start_availability');
});

test('o filtro de senioridade filtra os perfis', function (): void {
    $senior = Profile::query()->where('user_id', User::factory()->create()->getKey())->sole();
    $senior->update(['seniority_level' => SeniorityLevel::Senior]);

    $junior = Profile::query()->where('user_id', User::factory()->create()->getKey())->sole();
    $junior->update(['seniority_level' => SeniorityLevel::Junior]);

    livewire(ListProfiles::class)
        ->loadTable()
        ->filterTable('seniority_level', [SeniorityLevel::Senior->value])
        ->assertCanSeeTableRecords([$senior])
        ->assertCanNotSeeTableRecords([$junior]);
});

test('o filtro de perfil incompleto mostra quem não tem headline ou senioridade', function (): void {
    $complete = Profile::query()->where('user_id', User::factory()->create()->getKey())->sole();
    $complete->update([
        'headline' => 'Backend',
        'seniority_level' => SeniorityLevel::Senior,
    ]);

    livewire(ListProfiles::class)
        ->loadTable()
        ->filterTable('incomplete')
        ->assertCanSeeTableRecords([$this->profile])
        ->assertCanNotSeeTableRecords([$complete]);
});

test('valida os dados do form', function (array $data, array $errors): void {
    livewire(EditProfile::class, ['record' => $this->profile->getKey()])
        ->fillForm($data)
        ->call('save')
        ->assertHasFormErrors($errors);
})->with([
    '`years_experience` não aceita negativo' => [['years_experience' => -1], ['years_experience' => 'min']],
    '`years_experience` tem teto de 70' => [['years_experience' => 71], ['years_experience' => 'max']],
    '`birthdate` não pode ser no futuro' => [['birthdate' => now()->addYear()->format('Y-m-d')], ['birthdate' => 'before_or_equal']],
]);

test('o relation manager de skills registra a proficiência', function (): void {
    $skill = Skill::factory()->create(['name' => 'Rust']);

    livewire(ProfileSkillsRelationManager::class, [
        'ownerRecord' => $this->profile,
        'pageClass' => EditProfile::class,
    ])
        ->loadTable()
        ->callAction(TestAction::make('create')->table(), data: [
            'skill_id' => $skill->getKey(),
            'proficiency' => SkillProficiency::Advanced->value,
            'years_experience' => 3,
        ])
        ->assertHasNoActionErrors();

    expect($this->profile->profileSkills()->where('skill_id', $skill->getKey())->exists())->toBeTrue();
});

test('o relation manager de experiências lista os registros do perfil', function (): void {
    $experience = WorkExperience::factory()->create(['profile_id' => $this->profile->getKey()]);

    livewire(WorkExperiencesRelationManager::class, [
        'ownerRecord' => $this->profile,
        'pageClass' => EditProfile::class,
    ])
        ->loadTable()
        ->assertOk()
        ->assertCanSeeTableRecords([$experience]);
});
