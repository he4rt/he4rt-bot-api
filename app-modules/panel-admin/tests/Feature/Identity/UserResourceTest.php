<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use He4rt\Identity\Authorization\Enums\UserRole;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;
use He4rt\PanelAdmin\Filament\Resources\Users\Pages\EditUser;
use He4rt\PanelAdmin\Filament\Resources\Users\Pages\ListUsers;
use He4rt\PanelAdmin\Filament\Resources\Users\RelationManagers\ProvidersRelationManager;
use He4rt\PanelAdmin\Filament\Resources\Users\UserResource;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

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
    livewire(ListUsers::class)->loadTable()->assertOk();
});

test('a conta não pode ser criada pelo painel', function (): void {
    expect(UserResource::canCreate())->toBeFalse()
        ->and(UserResource::getPages())->not->toHaveKey('create');
});

test('o form de edição não expõe campos de punição', function (): void {
    livewire(EditUser::class, ['record' => $this->admin->getKey()])
        ->assertSchemaComponentDoesNotExist('banned_at')
        ->assertSchemaComponentDoesNotExist('suspended_until');
});

test('o form de edição expõe os campos de identificação', function (): void {
    livewire(EditUser::class, ['record' => $this->admin->getKey()])
        ->assertSchemaComponentExists('username', checkComponentUsing: fn (TextInput $field): bool => $field->isRequired())
        ->assertSchemaComponentExists('name')
        ->assertSchemaComponentExists('email');
});

test('a coluna de situação existe na tabela', function (): void {
    livewire(ListUsers::class)->loadTable()->assertTableColumnExists('situation');
});

test('o filtro de situação separa banidos de ativos', function (): void {
    $banned = User::factory()->create(['banned_at' => now()->subDay()]);
    $active = User::factory()->create();

    livewire(ListUsers::class)
        ->loadTable()
        ->filterTable('situation', 'banned')
        ->assertCanSeeTableRecords([$banned])
        ->assertCanNotSeeTableRecords([$active, $this->admin]);
});

test('o filtro de situação mostra só suspensão vigente', function (): void {
    $suspended = User::factory()->create(['suspended_until' => now()->addWeek()]);
    $expired = User::factory()->create(['suspended_until' => now()->subWeek()]);

    livewire(ListUsers::class)
        ->loadTable()
        ->filterTable('situation', 'suspended')
        ->assertCanSeeTableRecords([$suspended])
        ->assertCanNotSeeTableRecords([$expired]);
});

test('o filtro de situação exclui banidos e suspensos dos ativos', function (): void {
    $banned = User::factory()->create(['banned_at' => now()]);
    $suspended = User::factory()->create(['suspended_until' => now()->addWeek()]);

    livewire(ListUsers::class)
        ->loadTable()
        ->filterTable('situation', 'active')
        ->assertCanSeeTableRecords([$this->admin])
        ->assertCanNotSeeTableRecords([$banned, $suspended]);
});

test('o filtro de quem nunca logou mostra só first_login_at nulo', function (): void {
    $logged = User::factory()->create(['first_login_at' => now()]);

    livewire(ListUsers::class)
        ->loadTable()
        ->filterTable('never_logged_in')
        ->assertCanSeeTableRecords([$this->admin])
        ->assertCanNotSeeTableRecords([$logged]);
});

test('valida os dados do form', function (array $data, array $errors): void {
    livewire(EditUser::class, ['record' => $this->admin->getKey()])
        ->fillForm($data)
        ->call('save')
        ->assertHasFormErrors($errors);
})->with([
    '`username` é obrigatório' => [['username' => null], ['username' => 'required']],
    '`username` tem no máximo 255' => [['username' => Str::random(256)], ['username' => 'max']],
    '`name` é obrigatório' => [['name' => null], ['name' => 'required']],
    '`name` tem no máximo 255' => [['name' => Str::random(256)], ['name' => 'max']],
    '`email` precisa ser válido' => [['email' => 'nao-e-email'], ['email' => 'email']],
]);

test('o relation manager de identidades lista os provedores do usuário', function (): void {
    $identity = ExternalIdentity::factory()->create([
        'model_type' => $this->admin->getMorphClass(),
        'model_id' => $this->admin->getKey(),
    ]);

    livewire(ProvidersRelationManager::class, [
        'ownerRecord' => $this->admin,
        'pageClass' => EditUser::class,
    ])
        ->loadTable()
        ->assertOk()
        ->assertCanSeeTableRecords([$identity]);
});

test('o form expõe os papéis como lista de checkbox', function (): void {
    $other = User::factory()->create();

    livewire(EditUser::class, ['record' => $other->getKey()])
        ->assertSchemaComponentExists('roles', checkComponentUsing: fn (CheckboxList $field): bool => !$field->isDisabled());
});

test('salvar o form concede super admin a outro usuário', function (): void {
    $other = User::factory()->create();
    $role = Role::findByName(UserRole::SuperAdmin->value, UserRole::GUARD);

    livewire(EditUser::class, ['record' => $other->getKey()])
        ->fillForm(['roles' => [$role->getKey()]])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($other->fresh()?->hasRole(UserRole::SuperAdmin))->toBeTrue();
});

test('salvar o form sem papéis revoga super admin de outro usuário', function (): void {
    $other = User::factory()->superAdmin()->create();

    livewire(EditUser::class, ['record' => $other->getKey()])
        ->fillForm(['roles' => []])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($other->fresh()?->hasRole(UserRole::SuperAdmin))->toBeFalse();
});

test('o admin não altera os próprios papéis', function (): void {
    livewire(EditUser::class, ['record' => $this->admin->getKey()])
        ->assertSchemaComponentExists('roles', checkComponentUsing: fn (CheckboxList $field): bool => $field->isDisabled())
        ->fillForm(['roles' => []])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($this->admin->fresh()?->hasRole(UserRole::SuperAdmin))->toBeTrue();
});

test('a coluna de papéis existe na tabela', function (): void {
    livewire(ListUsers::class)->loadTable()->assertTableColumnExists('roles.name');
});

test('o filtro de papel separa super admin de usuário comum', function (): void {
    $regular = User::factory()->create();
    $role = Role::findByName(UserRole::SuperAdmin->value, UserRole::GUARD);

    livewire(ListUsers::class)
        ->loadTable()
        ->filterTable('roles', $role->getKey())
        ->assertCanSeeTableRecords([$this->admin])
        ->assertCanNotSeeTableRecords([$regular]);
});
