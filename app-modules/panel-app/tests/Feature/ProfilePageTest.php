<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\JsContent;
use He4rt\Identity\User\Models\User;
use He4rt\PanelApp\Pages\ProfilePage;
use He4rt\Profile\Enums\EmploymentType;
use He4rt\Profile\Enums\SeniorityLevel;
use He4rt\Profile\Enums\SkillProficiency;
use He4rt\Profile\Enums\StartAvailability;
use He4rt\Profile\Models\Profile;
use He4rt\Profile\Models\Skill;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    Cache::flush();
    Storage::fake(config()->string('filesystems.default'));

    Http::fake([
        'world.bmbc.cloud/api/countries*' => Http::response([
            'success' => true,
            'data' => [
                ['id' => 31, 'iso2' => 'BR', 'iso3' => 'BRA', 'name' => 'Brazil'],
                ['id' => 231, 'iso2' => 'US', 'iso3' => 'USA', 'name' => 'United States'],
            ],
        ]),
        'world.bmbc.cloud/api/states*' => Http::response([
            'success' => true,
            'data' => [
                ['id' => 486, 'name' => 'São Paulo', 'cities' => [
                    ['id' => 2, 'name' => 'São Paulo'],
                    ['id' => 3, 'name' => 'Campinas'],
                ]],
            ],
        ]),
    ]);

    $this->user = User::factory()->create();

    $this->actingAs($this->user);

    Filament::setCurrentPanel(Filament::getPanel('app'));

    $this->profile = Profile::query()
        ->where('user_id', $this->user->id)
        ->first();
});

test('profile page renders successfully', function (): void {
    $this->get(ProfilePage::getUrl())
        ->assertSuccessful();
});

test('profile page loads existing profile data', function (): void {
    $this->profile->update([
        'headline' => 'Backend Developer',
        'seniority_level' => SeniorityLevel::Mid,
    ]);

    livewire(ProfilePage::class)
        ->assertOk()
        ->assertSchemaStateSet([
            'headline' => 'Backend Developer',
            'seniority_level' => SeniorityLevel::Mid,
        ]);
});

test('profile page saves all fields', function (): void {
    livewire(ProfilePage::class)
        ->set('data.nickname', 'Dan')
        ->fillForm([
            'headline' => 'Backend Developer',
            'seniority_level' => 'mid',
            'years_experience' => 5,
            'about' => 'Dev PHP apaixonado por Laravel',
        ])
        ->call('save')
        ->assertNotified();

    $this->profile->refresh();

    expect($this->profile->nickname)->toBe('Dan')
        ->and($this->profile->headline)->toBe('Backend Developer')
        ->and($this->profile->seniority_level)->toBe(SeniorityLevel::Mid)
        ->and($this->profile->years_experience)->toBe(5)
        ->and($this->profile->about)->toBe('Dev PHP apaixonado por Laravel');
});

test('profile page saves birthdate from date picker', function (): void {
    livewire(ProfilePage::class)
        ->fillForm([
            'birthdate' => '1996-02-15',
        ], 'birthdateForm')
        ->call('save')
        ->assertHasNoFormErrors([], 'birthdateForm')
        ->assertNotified();

    $this->profile->refresh();

    expect($this->profile->birthdate?->format('Y-m-d'))->toBe('1996-02-15');
});

test('profile page saves social links from repeater', function (): void {
    livewire(ProfilePage::class)
        ->fillForm([
            'social_links' => [
                ['platform' => 'instagram', 'handle' => '@danielhe4rt'],
                ['platform' => 'website', 'handle' => 'https://danielheart.dev'],
            ],
        ])
        ->call('save')
        ->assertNotified();

    $this->profile->refresh();

    expect($this->profile->social_links)->toMatchArray([
        'instagram' => '@danielhe4rt',
        'website' => 'https://danielheart.dev',
    ]);
});

test('profile page saves skills from repeater', function (): void {
    $php = Skill::query()->where('slug', 'php')->sole();
    $laravel = Skill::query()->where('slug', 'laravel')->sole();

    livewire(ProfilePage::class)
        ->fillForm([
            'skills' => [
                ['skill_id' => $php->id, 'proficiency' => 'advanced', 'years_experience' => 5],
                ['skill_id' => $laravel->id, 'proficiency' => 'expert', 'years_experience' => 3],
            ],
        ])
        ->call('save')
        ->assertNotified();

    $this->profile->refresh();

    expect($this->profile->profileSkills()->count())->toBe(2);

    $pivot = $this->profile->profileSkills()->where('skill_id', $php->id)->first();

    expect($pivot->proficiency)->toBe(SkillProficiency::Advanced)
        ->and($pivot->years_experience)->toBe(5);
});

test('skills repeater hides skills already chosen in other rows', function (): void {
    $php = Skill::query()->where('slug', 'php')->sole();

    $component = livewire(ProfilePage::class)
        ->fillForm([
            'skills' => [
                ['skill_id' => $php->id, 'proficiency' => 'advanced', 'years_experience' => 5],
                ['skill_id' => null, 'proficiency' => null, 'years_experience' => null],
            ],
        ]);

    $instance = $component->instance();
    [$firstRow, $secondRow] = array_keys($instance->data['skills']);

    $searchIn = static function (int|string $rowKey) use ($instance, $php): array {
        /** @var Select $select */
        $select = $instance->form->getComponentByStatePath(
            sprintf('data.skills.%s.skill_id', $rowKey),
            withAbsoluteStatePath: true,
        );

        return array_keys($select->getSearchResults($php->slug));
    };

    expect($searchIn($secondRow))->not->toContain($php->id)
        ->and($searchIn($firstRow))->toContain($php->id);
});

test('profile page saves availability toggle', function (): void {
    livewire(ProfilePage::class)
        ->fillForm([
            'available_for_proposals' => true,
            'start_availability' => 'immediate',
        ])
        ->call('save')
        ->assertNotified();

    $this->profile->refresh();

    expect($this->profile->available_for_proposals)->toBeTrue()
        ->and($this->profile->start_availability)->toBe(StartAvailability::Immediate);
});

test('profile page validates about max length', function (): void {
    livewire(ProfilePage::class)
        ->fillForm([
            'about' => str_repeat('a', 501),
        ])
        ->call('save')
        ->assertHasFormErrors(['about']);
});

test('profile page shows about character counter in real time', function (): void {
    livewire(ProfilePage::class)
        ->fillForm([
            'about' => 'abc'."\u{1F600}".'def',
        ])
        ->assertFormFieldExists('about', function (Textarea $field): bool {
            expect($field->getHint())->toBeInstanceOf(JsContent::class)
                ->and($field->getHint()->toHtml())->toContain('Array.from($state')
                ->toContain('500')
                ->and($field->getStateBindingModifiers())->toBe(['live', 'blur']);

            return true;
        });
});

test('profile page validates headline max length', function (): void {
    livewire(ProfilePage::class)
        ->fillForm([
            'headline' => str_repeat('a', 101),
        ])
        ->call('save')
        ->assertHasFormErrors(['headline']);
});

test('profile page does not show account fields', function (): void {
    livewire(ProfilePage::class)
        ->assertFormFieldDoesNotExist('email')
        ->assertFormFieldDoesNotExist('password');
});

test('profile page saves expected salary range', function (): void {
    livewire(ProfilePage::class)
        ->fillForm([
            'available_for_proposals' => true,
            'expected_salary_min' => 5_000,
            'expected_salary_max' => 9_000,
        ])
        ->call('save')
        ->assertNotified();

    $this->profile->refresh();

    expect($this->profile->expected_salary_min)->toBe('5000.00')
        ->and($this->profile->expected_salary_max)->toBe('9000.00');
});

test('profile page saves work preferences', function (): void {
    livewire(ProfilePage::class)
        ->fillForm([
            'is_open_to_remote' => true,
            'willing_to_relocate' => true,
            'has_disability' => false,
            'employment_types' => ['pj', 'freelance'],
        ])
        ->call('save')
        ->assertNotified();

    $this->profile->refresh();

    expect($this->profile->preferences->isOpenToRemote)->toBeTrue()
        ->and($this->profile->preferences->willingToRelocate)->toBeTrue()
        ->and($this->profile->preferences->hasDisability)->toBeFalse()
        ->and($this->profile->preferences->employmentTypes)->toBe([EmploymentType::IndependentContractor, EmploymentType::Freelancer]);
});

test('profile page saves a work experience', function (): void {
    livewire(ProfilePage::class)
        ->fillForm([
            'workExperiences' => [
                [
                    'company_name' => 'He4rt',
                    'position' => 'Backend Developer',
                    'description' => 'Trampo com Laravel',
                    'start_date' => '2020-01-01',
                    'end_date' => '2022-01-01',
                    'is_currently_working_here' => false,
                ],
            ],
        ])
        ->call('save')
        ->assertNotified();

    $experience = $this->profile->workExperiences()->sole();

    expect($experience->company_name)->toBe('He4rt')
        ->and($experience->position)->toBe('Backend Developer')
        ->and($experience->description)->toBe('Trampo com Laravel')
        ->and($experience->start_date->toDateString())->toBe('2020-01-01')
        ->and($experience->end_date->toDateString())->toBe('2022-01-01')
        ->and($experience->is_currently_working_here)->toBeFalse();
});

test('profile page nulls end_date when currently working here', function (): void {
    livewire(ProfilePage::class)
        ->fillForm([
            'workExperiences' => [
                [
                    'company_name' => 'He4rt',
                    'position' => 'Tech Lead',
                    'description' => 'Trabalho atual',
                    'start_date' => '2023-01-01',
                    'is_currently_working_here' => true,
                ],
            ],
        ])
        ->call('save')
        ->assertNotified();

    $experience = $this->profile->workExperiences()->sole();

    expect($experience->is_currently_working_here)->toBeTrue()
        ->and($experience->end_date)->toBeNull();
});

test('profile page uploads avatar through action modal', function (): void {
    livewire(ProfilePage::class)
        ->callAction('editAvatar', [
            'avatar' => UploadedFile::fake()->image('avatar.jpg', 1_200, 1_200),
        ])
        ->assertHasNoActionErrors()
        ->assertNotified();

    $media = $this->user->fresh()->getFirstMedia('avatar');

    expect($media)->not->toBeNull()
        ->and($media?->collection_name)->toBe('avatar');
});

test('profile page uploads cover through action modal', function (): void {
    livewire(ProfilePage::class)
        ->callAction('editCover', [
            'cover' => UploadedFile::fake()->image('cover.jpg', 1_800, 600),
        ])
        ->assertHasNoActionErrors()
        ->assertNotified();

    $media = $this->user->fresh()->getFirstMedia('cover');

    expect($media)->not->toBeNull()
        ->and($media?->collection_name)->toBe('cover');
});

test('profile page allows updating username through editUsername action modal', function (): void {
    livewire(ProfilePage::class)
        ->callAction('editUsername', [
            'username' => 'new_cool_handle',
        ])
        ->assertHasNoActionErrors()
        ->assertNotified(__('panel-app::profile.notifications.username_updated'))
        ->assertSee('@new_cool_handle');

    expect($this->user->fresh()->username)->toBe('new_cool_handle');
});

test('profile page halts action and sends danger notification on username error', function (): void {
    livewire(ProfilePage::class)
        ->callAction('editUsername', [
            'username' => $this->user->username,
        ])
        ->assertActionHalted('editUsername')
        ->assertNotified()
        ->assertHasErrors(['mountedActionsData.0.username']);
});

test('profile page shows validation error when username has invalid format', function (string $invalidUsername): void {
    livewire(ProfilePage::class)
        ->callAction('editUsername', [
            'username' => $invalidUsername,
        ])
        ->assertHasActionErrors(['username']);
})->with([
    'contains special characters' => 'teste!',
    'starts with special character' => '_teste',
    'ends with special character' => 'teste-',
    'consecutive special characters' => 'teste..teste',
    'too short' => 'a',
    'reserved word' => 'admin',
]);
