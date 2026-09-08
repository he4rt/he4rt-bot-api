<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Pages;

use App\Geo\Support\GeoLocation;
use App\Support\UploadLimit;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ViewField;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\JsContent;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use He4rt\Gamification\Character\Models\Character;
use He4rt\Identity\User\Enums\ProfileImage;
use He4rt\Identity\User\Models\User;
use He4rt\PanelApp\Rules\UnconvertedImageSize;
use He4rt\Profile\Actions\SyncProfileSkills;
use He4rt\Profile\Actions\ToggleAvailability;
use He4rt\Profile\Actions\UpsertProfile;
use He4rt\Profile\DTOs\ProfileSkillDTO;
use He4rt\Profile\DTOs\UpsertProfileDTO;
use He4rt\Profile\Enums\EmploymentType;
use He4rt\Profile\Enums\SeniorityLevel;
use He4rt\Profile\Enums\SkillProficiency;
use He4rt\Profile\Enums\SocialPlatform;
use He4rt\Profile\Enums\StartAvailability;
use He4rt\Profile\Models\Profile;
use He4rt\Profile\Models\Skill;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * @property-read Schema $form
 * @property-read Schema $birthdateForm
 * @property-read string|null $avatarPreviewUrl
 * @property-read string|null $coverPreviewUrl
 * @property-read int $avatarFocalY
 * @property-read int $coverFocalY
 */
class ProfilePage extends Page
{
    use WithFileUploads;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    /** @var array<string, mixed>|null */
    public ?array $birthdateData = [];

    protected static string|null|BackedEnum $navigationIcon = 'heroicon-o-user-circle';

    protected static ?string $title = 'Profile';

    protected static ?string $slug = 'profile';

    protected static ?int $navigationSort = 2;

    protected string $view = 'panel-app::pages.profile';

    protected Width|string|null $maxContentWidth = Width::Full;

    public function mount(): void
    {
        $profile = $this->getRecord();

        $this->form->fill([
            'nickname' => $profile->nickname,
            'headline' => $profile->headline,
            'seniority_level' => $profile->seniority_level,
            'years_experience' => $profile->years_experience,
            'about' => $profile->about,
            'social_links' => $this->socialLinksToRepeater($profile->social_links),
            'skills' => $this->skillsToRepeater($profile),
            'available_for_proposals' => $profile->available_for_proposals,
            'start_availability' => $profile->start_availability,
            'expected_salary_min' => $profile->expected_salary_min,
            'expected_salary_max' => $profile->expected_salary_max,
            'has_disability' => $profile->preferences->hasDisability,
            'willing_to_relocate' => $profile->preferences->willingToRelocate,
            'is_open_to_remote' => $profile->preferences->isOpenToRemote,
            'employment_types' => array_map(
                static fn (EmploymentType $type): string => $type->value,
                $profile->preferences->employmentTypes,
            ),
        ]);

        $this->birthdateForm->fill([
            'birthdate' => $profile->birthdate?->format('Y-m-d'),
        ]);
    }

    public function birthdateForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('birthdate')
                    ->label(__('panel-app::profile.fields.birthdate'))
                    ->native(condition: false)
                    ->displayFormat('d/m/Y')
                    ->format('Y-m-d')
                    ->minDate(now()->subYears(120))
                    ->maxDate(now()),
            ])
            // Own state path: fill() replaces the whole path, so sharing `data` with
            // the main form wiped headline/seniority/etc. on mount.
            ->statePath('birthdateData');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([
                    Section::make(__('panel-app::profile.sections.professional'))
                        ->schema([
                            Grid::make(3)->schema([
                                TextInput::make('headline')
                                    ->label(__('panel-app::profile.fields.headline'))
                                    ->placeholder(__('panel-app::profile.placeholders.headline'))
                                    ->maxLength(100)
                                    ->live(onBlur: true)
                                    ->columnSpan(1),

                                Select::make('seniority_level')
                                    ->label(__('panel-app::profile.fields.seniority_level'))
                                    ->options(SeniorityLevel::class)
                                    ->live()
                                    ->columnSpan(1),

                                TextInput::make('years_experience')
                                    ->label(__('panel-app::profile.fields.years_experience'))
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(50)
                                    ->live(onBlur: true)
                                    ->columnSpan(1),
                                Textarea::make('about')
                                    ->label(__('panel-app::profile.fields.about'))
                                    ->placeholder(__('panel-app::profile.placeholders.about'))
                                    ->hint(JsContent::make('`${Array.from($state ?? "").length}/500`'))
                                    ->maxLength(500)
                                    ->rows(4)
                                    ->live(onBlur: true)
                                    ->columnSpanFull(),
                            ]),
                        ]),

                    Section::make(__('panel-app::profile.sections.skills'))
                        ->description(__('panel-app::profile.hints.skills'))
                        ->schema([
                            Repeater::make('skills')
                                ->label('')
                                ->schema([
                                    Grid::make(3)->schema([
                                        Select::make('skill_id')
                                            ->label(__('panel-app::profile.fields.skill'))
                                            ->searchable()
                                            ->getSearchResultsUsing(fn (string $search, Select $component): array => Skill::search(
                                                $search,
                                                exclude: $this->skillIdsInSiblingRows($component),
                                            ))
                                            ->getOptionLabelUsing(fn (?string $value): ?string => $value === null ? null : (Skill::labelsById()[$value] ?? null))
                                            ->optionsLimit(50)
                                            ->distinct()
                                            ->required(fn (Get $get): bool => filled($get('proficiency')))
                                            ->columnSpan(1),

                                        Select::make('proficiency')
                                            ->label(__('panel-app::profile.fields.proficiency'))
                                            ->options(SkillProficiency::class)
                                            ->required(fn (Get $get): bool => filled($get('skill_id')))
                                            ->live()
                                            ->afterStateUpdated(function (Get $get, Select $component): void {
                                                if (blank($get('skill_id')) || blank($get('proficiency'))) {
                                                    return;
                                                }

                                                $this->appendEmptyRepeaterItemIfLastRow($component);
                                            })
                                            ->columnSpan(1),

                                        TextInput::make('years_experience')
                                            ->label(__('panel-app::profile.fields.skill_years_experience'))
                                            ->numeric()
                                            ->minValue(0)
                                            ->maxValue(50)
                                            ->columnSpan(1),
                                    ]),
                                ])
                                ->addActionLabel(__('panel-app::profile.actions.add_skill'))
                                ->defaultItems(0)
                                ->reorderable(condition: false)
                                ->columnSpanFull(),
                        ]),

                    Section::make(__('panel-app::profile.sections.address'))
                        ->relationship('address')
                        ->schema([
                            Grid::make(3)->schema([
                                Select::make('country')
                                    ->label(__('panel-app::profile.fields.country'))
                                    ->options(static fn (): array => GeoLocation::countries())
                                    ->default('BRA')
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(static function (Set $set): void {
                                        $set('state', null);
                                        $set('city', null);
                                    })
                                    ->columnSpan(1),

                                Select::make('state')
                                    ->label(__('panel-app::profile.fields.state'))
                                    ->options(static fn (Get $get): array => GeoLocation::statesFor($get('country')))
                                    ->searchable()
                                    ->live()
                                    ->visible(static fn (Get $get): bool => filled($get('country')))
                                    ->afterStateUpdated(static function (Set $set): void {
                                        $set('city', null);
                                    })
                                    ->columnSpan(1),

                                Select::make('city')
                                    ->label(__('panel-app::profile.fields.city'))
                                    ->options(static fn (Get $get): array => GeoLocation::citiesFor($get('country'), $get('state')))
                                    ->getSearchResultsUsing(static fn (string $search, Get $get): array => GeoLocation::citiesFor($get('country'), $get('state'), $search))
                                    ->getOptionLabelUsing(static fn (?string $value): ?string => $value)
                                    ->searchable()
                                    ->preload()
                                    ->searchPrompt(__('panel-app::profile.placeholders.city_search'))
                                    ->hintIcon('heroicon-o-information-circle', __('panel-app::profile.hints.city'))
                                    ->visible(static fn (Get $get): bool => filled($get('state')))
                                    ->columnSpan(1),
                            ]),
                        ]),

                    Section::make(__('panel-app::profile.sections.social_links'))
                        ->schema([
                            Repeater::make('social_links')
                                ->label('')
                                ->schema([
                                    Grid::make(2)->schema([
                                        Select::make('platform')
                                            ->label(__('panel-app::profile.fields.platform'))
                                            ->options(fn (): array => collect(SocialPlatform::cases())
                                                ->mapWithKeys(fn (SocialPlatform $platform): array => [
                                                    $platform->value => sprintf(
                                                        '<span class="flex items-center gap-2">%s %s</span>',
                                                        svg($platform->getBrandIcon(), 'h-4 w-4')->toHtml(),
                                                        e($platform->getLabel()),
                                                    ),
                                                ])
                                                ->all())
                                            ->allowHtml()
                                            ->required(fn (Get $get): bool => filled($get('handle')))
                                            ->live()
                                            ->afterStateUpdated(static function (Get $get, Set $set): void {
                                                if (blank($get('platform'))) {
                                                    $set('handle', null);
                                                }
                                            })
                                            ->columnSpan(1),

                                        TextInput::make('handle')
                                            ->label(__('panel-app::profile.fields.handle'))
                                            ->placeholder(__('panel-app::profile.placeholders.handle'))
                                            ->disabled(fn (Get $get): bool => blank($get('platform')))
                                            ->required(fn (Get $get): bool => filled($get('platform')))
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function (Get $get, TextInput $component): void {
                                                if (blank($get('platform')) || blank($get('handle'))) {
                                                    return;
                                                }

                                                $this->appendEmptyRepeaterItemIfLastRow($component);
                                            })
                                            ->columnSpan(1),
                                    ]),
                                ])
                                ->addActionLabel(__('panel-app::profile.actions.add_social_link'))
                                ->defaultItems(0)
                                ->reorderable(condition: false)
                                ->columnSpanFull(),
                        ]),

                    Section::make(__('panel-app::profile.sections.availability'))
                        ->schema([
                            Toggle::make('available_for_proposals')
                                ->label(__('panel-app::profile.fields.available_for_proposals'))
                                ->hint(__('panel-app::profile.hints.available_for_proposals'))
                                ->live(),

                            Select::make('start_availability')
                                ->label(__('panel-app::profile.fields.start_availability'))
                                ->options(StartAvailability::class)
                                ->live()
                                ->visible(fn (Get $get): bool => (bool) $get('available_for_proposals')),

                            Grid::make(2)
                                ->visible(fn (Get $get): bool => (bool) $get('available_for_proposals'))
                                ->schema([
                                    TextInput::make('expected_salary_min')
                                        ->label(__('panel-app::profile.fields.expected_salary_min'))
                                        ->hint(__('panel-app::profile.hints.expected_salary'))
                                        ->numeric()
                                        ->minValue(0)
                                        ->prefix('R$')
                                        ->live(onBlur: true),

                                    TextInput::make('expected_salary_max')
                                        ->label(__('panel-app::profile.fields.expected_salary_max'))
                                        ->numeric()
                                        ->minValue(0)
                                        ->prefix('R$')
                                        ->live(onBlur: true),
                                ]),
                        ]),

                    Section::make(__('panel-app::profile.sections.preferences'))
                        ->schema([
                            Toggle::make('is_open_to_remote')
                                ->label(__('panel-app::profile.fields.is_open_to_remote'))
                                ->live(),

                            Toggle::make('willing_to_relocate')
                                ->label(__('panel-app::profile.fields.willing_to_relocate'))
                                ->live(),

                            Toggle::make('has_disability')
                                ->label(__('panel-app::profile.fields.has_disability'))
                                ->hint(__('panel-app::profile.hints.has_disability'))
                                ->live(),

                            Select::make('employment_types')
                                ->label(__('panel-app::profile.fields.employment_types'))
                                ->options(EmploymentType::class)
                                ->multiple()
                                ->live(),
                        ]),

                    Section::make(__('panel-app::profile.sections.work_experiences'))
                        ->schema([
                            Repeater::make('workExperiences')
                                ->relationship('workExperiences')
                                ->model(fn (): Profile => $this->getRecord()) // record do schema e o User; a relacao vive na Profile
                                ->schema([
                                    Grid::make(2)->schema([
                                        TextInput::make('company_name')
                                            ->label(__('panel-app::profile.fields.company_name'))
                                            ->required(fn (Get $get): bool => filled($get('position')) || filled($get('description')) || filled($get('start_date')))
                                            ->maxLength(255)
                                            ->columnSpan(1),
                                        TextInput::make('position')
                                            ->label(__('panel-app::profile.fields.position'))
                                            ->required(fn (Get $get): bool => filled($get('company_name')) || filled($get('description')) || filled($get('start_date')))
                                            ->maxLength(255)
                                            ->columnSpan(1),
                                    ]),
                                    Textarea::make('description')
                                        ->label(__('panel-app::profile.fields.experience_description'))
                                        ->required(fn (Get $get): bool => filled($get('company_name')) || filled($get('position')) || filled($get('start_date')))
                                        ->rows(3)
                                        ->maxLength(2_000)
                                        ->columnSpanFull(),
                                    Grid::make(2)->schema([
                                        DatePicker::make('start_date')
                                            ->label(__('panel-app::profile.fields.start_date'))
                                            ->native(condition: false)
                                            ->displayFormat('M Y')
                                            ->format('Y-m-d')
                                            ->maxDate(now())
                                            ->required(fn (Get $get): bool => filled($get('company_name')) || filled($get('position')) || filled($get('description')))
                                            ->columnSpan(1),
                                        DatePicker::make('end_date')
                                            ->label(__('panel-app::profile.fields.end_date'))
                                            ->native(condition: false)
                                            ->displayFormat('M Y')
                                            ->format('Y-m-d')
                                            ->maxDate(now())
                                            ->afterOrEqual('start_date')
                                            ->required(fn (Get $get): bool => !$get('is_currently_working_here') && (filled($get('company_name')) || filled($get('position')) || filled($get('description')) || filled($get('start_date'))))
                                            ->hidden(fn (Get $get): bool => (bool) $get('is_currently_working_here'))
                                            ->columnSpan(1),
                                    ]),
                                    Toggle::make('is_currently_working_here')
                                        ->label(__('panel-app::profile.fields.is_currently_working_here'))
                                        ->live()
                                        ->afterStateUpdated(fn (Set $set, bool $state) => $state ? $set('end_date', null) : null),
                                ])
                                ->itemLabel(static function (array $state): ?string {
                                    $companyName = $state['company_name'] ?? null;

                                    return is_string($companyName) ? $companyName : null;
                                })
                                ->addActionLabel(__('panel-app::profile.actions.add_work_experience'))
                                ->collapsible()
                                ->defaultItems(0)
                                ->columnSpanFull()
                                ->mutateRelationshipDataBeforeCreateUsing($this->normalizeWorkExperienceData(...))
                                ->mutateRelationshipDataBeforeSaveUsing($this->normalizeWorkExperienceData(...)),
                        ]),
                ])
                    ->livewireSubmitHandler('save')
                    ->footer([
                        Actions::make([
                            Action::make('save')
                                ->label(__('panel-app::profile.actions.save'))
                                ->submit('save'),
                        ]),
                    ]),
            ])
            ->record(auth()->user())
            ->statePath('data');
    }

    public function save(): void
    {
        $birthdateData = $this->birthdateForm->getState();
        $formData = $this->form->getState();
        $profile = $this->getRecord();

        $socialLinks = $this->repeaterToSocialLinks($formData['social_links'] ?? []);

        $dto = UpsertProfileDTO::fromArray([
            'nickname' => $this->data['nickname'] ?? null,
            'birthdate' => $birthdateData['birthdate'] ?? null,
            'about' => $formData['about'] ?? null,
            'headline' => $formData['headline'] ?? null,
            'seniority_level' => $formData['seniority_level'] ?? null,
            'years_experience' => $formData['years_experience'] ?? null,
            'social_links' => $socialLinks !== [] ? $socialLinks : null,
            'expected_salary_min' => $formData['expected_salary_min'] ?? null,
            'expected_salary_max' => $formData['expected_salary_max'] ?? null,
            'preferences' => [
                'has_disability' => $formData['has_disability'] ?? false,
                'willing_to_relocate' => $formData['willing_to_relocate'] ?? false,
                'is_open_to_remote' => $formData['is_open_to_remote'] ?? false,
                'employment_types' => $formData['employment_types'] ?? [],
            ],
        ]);

        resolve(UpsertProfile::class)->handle($profile, $dto);

        $available = (bool) ($formData['available_for_proposals'] ?? false);
        $rawStartAvailability = $formData['start_availability'] ?? null;
        $startAvailability = match (true) {
            $rawStartAvailability instanceof StartAvailability => $rawStartAvailability,
            is_string($rawStartAvailability) => StartAvailability::from($rawStartAvailability),
            $available => StartAvailability::Negotiable,
            default => null,
        };

        resolve(ToggleAvailability::class)->handle($profile, $available, $startAvailability);

        resolve(SyncProfileSkills::class)->handle($profile, $this->repeaterToSkills($formData['skills'] ?? []));

        $this->dispatch('scroll-to-top');

        $this->form->saveRelationships();

        Notification::make()
            ->success()
            ->title(__('panel-app::profile.notifications.saved'))
            ->send();
    }

    public function editAvatarAction(): Action
    {
        return $this->imageUploadAction('editAvatar', ProfileImage::Avatar);
    }

    public function editCoverAction(): Action
    {
        return $this->imageUploadAction('editCover', ProfileImage::Cover);
    }

    public function adjustAvatarAction(): Action
    {
        return $this->imageFramingAction('adjustAvatar', ProfileImage::Avatar);
    }

    public function adjustCoverAction(): Action
    {
        return $this->imageFramingAction('adjustCover', ProfileImage::Cover);
    }

    public function getRecord(): Profile
    {
        return Profile::query()
            ->firstOrCreate(
                [
                    'user_id' => auth()->id(),
                ],
            );
    }

    #[Computed]
    public function character(): ?Character
    {
        return Character::query()
            ->with('badges')
            ->where('user_id', auth()->id())
            ->first();
    }

    #[Computed]
    public function initials(): string
    {
        return Str::of(auth()->user()->name)
            ->explode(' ')
            ->map(fn (string $part): string => Str::upper(Str::substr($part, 0, 1)))
            ->take(2)
            ->implode('');
    }

    #[Computed]
    public function coverAspectRatio(): string
    {
        return ProfileImage::Cover->cssAspectRatio();
    }

    #[Computed]
    public function coverFocalY(): int
    {
        /** @var User $user */
        $user = auth()->user()->fresh();

        return $user->imageFocalY(ProfileImage::Cover);
    }

    #[Computed]
    public function avatarFocalY(): int
    {
        /** @var User $user */
        $user = auth()->user()->fresh();

        return $user->imageFocalY(ProfileImage::Avatar);
    }

    #[Computed]
    public function avatarPreviewUrl(): ?string
    {
        /** @var User $user */
        $user = auth()->user()->fresh();

        return $user->imageUrl(ProfileImage::Avatar);
    }

    #[Computed]
    public function coverPreviewUrl(): ?string
    {
        /** @var User $user */
        $user = auth()->user()->fresh();

        return $user->imageUrl(ProfileImage::Cover);
    }

    public function removeAvatar(): void
    {
        $this->removeImage(ProfileImage::Avatar);
    }

    public function removeCover(): void
    {
        $this->removeImage(ProfileImage::Cover);
    }

    /**
     * Escolhe qual faixa vertical da imagem aparece no recorte da tela.
     *
     * Existe para o que nao passa pelo editor: recortar um GIF achataria a
     * animacao, entao ele e servido inteiro e o enquadramento vira CSS.
     */
    private function imageFramingAction(string $name, ProfileImage $image): Action
    {
        return Action::make($name)
            ->label(__('panel-app::profile.actions.adjust_'.$image->value))
            ->modalHeading(__('panel-app::profile.actions.adjust_'.$image->value))
            ->modalDescription(__('panel-app::profile.hints.adjust_framing'))
            ->modalSubmitActionLabel(__('panel-app::profile.actions.save_framing'))
            ->modalSubmitAction(fn (Action $action) => $action->color('primary'))
            ->modalWidth(Width::TwoExtraLarge)
            ->visible(fn (): bool => filled($this->imagePreviewUrl($image)))
            ->schema([
                ViewField::make('focal_y')
                    ->hiddenLabel()
                    ->view('panel-app::components.image-focal-picker')
                    ->viewData([
                        'imageUrl' => $this->imagePreviewUrl($image),
                        'aspectRatio' => $image->cssAspectRatio(),
                        'isCircle' => $image === ProfileImage::Avatar,
                    ])
                    ->default(function () use ($image): int {
                        /** @var User $user */
                        $user = auth()->user();

                        return $user->imageFocalY($image);
                    }),
            ])
            ->action(function (array $data) use ($image): void {
                $focalY = $data['focal_y'] ?? null;

                /** @var User $user */
                $user = auth()->user();
                $user->setImageFocalY($image, is_numeric($focalY) ? (int) $focalY : ProfileImage::DEFAULT_FOCAL_Y);

                $this->refreshImageComputeds();

                Notification::make()
                    ->success()
                    ->title(__('panel-app::profile.notifications.framing_updated'))
                    ->send();
            });
    }

    /**
     * Upload com recorte na proporcao da imagem. O editor do Filament roda no
     * browser antes do upload, entao a validacao de dimensao abaixo mede o
     * resultado do recorte, e nao o arquivo bruto.
     */
    private function imageUploadAction(string $name, ProfileImage $image): Action
    {
        $limits = $this->imageLimits($image);

        return Action::make($name)
            ->label(__('panel-app::profile.actions.change_'.$image->value))
            ->modalHeading(__('panel-app::profile.actions.change_'.$image->value))
            ->modalSubmitActionLabel(__('panel-app::profile.actions.save_'.$image->value))
            ->modalSubmitAction(fn (Action $action) => $action->color('primary'))
            // O autofocus do modal cai no input do FilePond, que responde ao
            // foco abrindo o seletor de arquivos; com o focus trap reaplicando
            // o foco, o dialogo do sistema reabre em loop.
            ->modalAutofocus(condition: false)
            ->schema([
                $this->imageUploadField($image, $limits),
            ])
            ->action(function (array $data) use ($image): void {
                $file = $data[$image->value] ?? null;

                if (is_string($file)) {
                    $file = TemporaryUploadedFile::createFromLivewire($file);
                }

                if (!$file instanceof UploadedFile) {
                    return;
                }

                /** @var User $user */
                $user = auth()->user();
                $user->putImage($image, $file);

                $this->refreshImageComputeds();

                Notification::make()
                    ->success()
                    ->title(__('panel-app::profile.notifications.'.$image->value.'_updated'))
                    ->send();

                // Sem conversao a imagem vai inteira para a tela, e ai o
                // enquadramento e que decide o que aparece. Emenda o ajuste no
                // mesmo fluxo em vez de exigir que o usuario ache o botao.
                if ($user->imageNeedsFraming($image)) {
                    $this->replaceMountedAction($this->framingActionName($image));
                }
            });
    }

    /**
     * Limites de upload da imagem, em KB para as regras e em MB para o texto.
     *
     * O teto anunciado nunca passa do que o php.ini aceita, senao o PHP recusa
     * o arquivo antes da validacao e o que sobra na tela e "failed to upload".
     *
     * @return array{width: int, height: int, min_width: int, min_height: int, max_kb: int, unconverted_max_kb: int, max_mb: float, gif_mb: float, formats: string}
     */
    private function imageLimits(ProfileImage $image): array
    {
        $maxKilobytes = UploadLimit::kilobytes($image->maxKilobytes());
        $unconvertedMaxKilobytes = min($maxKilobytes, ProfileImage::unconvertedMaxKilobytes());

        return [
            'width' => $image->width(),
            'height' => $image->height(),
            'min_width' => $image->minWidth(),
            'min_height' => $image->minHeight(),
            'max_kb' => $maxKilobytes,
            'unconverted_max_kb' => $unconvertedMaxKilobytes,
            'max_mb' => round($maxKilobytes / 1_024, 1),
            'gif_mb' => round($unconvertedMaxKilobytes / 1_024, 1),
            'formats' => ProfileImage::formatLabels(),
        ];
    }

    /**
     * @param  array{width: int, height: int, min_width: int, min_height: int, max_kb: int, unconverted_max_kb: int, max_mb: float, gif_mb: float, formats: string}  $limits
     */
    private function imageUploadField(ProfileImage $image, array $limits): FileUpload
    {
        $field = match ($image) {
            ProfileImage::Avatar => FileUpload::make($image->value)->avatar(),
            ProfileImage::Cover => FileUpload::make($image->value)
                ->image()
                ->panelAspectRatio($image->aspectRatio()),
        };

        return $field
            ->label(__('panel-app::profile.fields.'.$image->value))
            // O teto do GIF só entra no texto quando ele é de fato menor: no
            // ambiente onde o limite geral já é o mesmo, repetir confunde.
            ->helperText(__(
                $limits['unconverted_max_kb'] < $limits['max_kb']
                    ? 'panel-app::profile.hints.image_upload_with_gif_limit'
                    : 'panel-app::profile.hints.image_upload',
                $limits,
            ))
            // imageAspectRatio() fica desligado de proposito. Ele instala uma
            // regra Rule::dimensions()->ratio() que exige a proporcao exata,
            // com tolerancia menor que um pixel. Como o editor recorta no
            // canvas e arredonda, o arquivo chega fora da razao por 1px e a
            // validacao reprova com "invalid image dimensions", que e o erro
            // generico da issue #458. Quem enquadra e a conversion.
            ->imageAspectRatio(ratio: null)
            ->automaticallyCropImagesToAspectRatio(condition: false)
            ->imageEditor()
            // Sem imageAspectRatio, e o viewport que trava o recorte na
            // proporcao certa dentro do editor.
            ->imageEditorViewportWidth($image->width())
            ->imageEditorViewportHeight($image->height())
            ->imageEditorAspectRatioOptions([$image->aspectRatio()])
            // Sem alvo de redimensionamento: o editor usa esses valores no
            // getCroppedCanvas(), entao com eles o recorte sairia sempre no
            // tamanho alvo, esticado no canvas do browser, e a validacao de
            // dimensao minima nunca falharia. Quem estica e a conversion.
            ->automaticallyResizeImagesToWidth(width: null)
            ->automaticallyResizeImagesToHeight(height: null)
            ->acceptedFileTypes(ProfileImage::mimeTypes())
            ->maxSize($limits['max_kb'])
            ->rules([
                sprintf(
                    'dimensions:min_width=%d,min_height=%d',
                    $image->minWidth(),
                    $image->minHeight(),
                ),
                new UnconvertedImageSize($limits['unconverted_max_kb']),
            ])
            ->validationMessages([
                'dimensions' => __('panel-app::profile.validation.image_dimensions', $limits),
                // O GIF chega ate aqui quando o usuario burla o filtro do
                // seletor de arquivos. Sem esta mensagem, o erro que aparece
                // e o generico do Laravel.
                'mimetypes' => __('panel-app::profile.validation.image_mimetypes', $limits),
            ])
            ->storeFiles(condition: false)
            ->required();
    }

    private function removeImage(ProfileImage $image): void
    {
        /** @var User $user */
        $user = auth()->user();
        $user->removeImage($image);

        $this->refreshImageComputeds();
    }

    private function framingActionName(ProfileImage $image): string
    {
        return match ($image) {
            ProfileImage::Avatar => 'adjustAvatar',
            ProfileImage::Cover => 'adjustCover',
        };
    }

    private function imagePreviewUrl(ProfileImage $image): ?string
    {
        return match ($image) {
            ProfileImage::Avatar => $this->avatarPreviewUrl,
            ProfileImage::Cover => $this->coverPreviewUrl,
        };
    }

    private function refreshImageComputeds(): void
    {
        unset(
            $this->avatarPreviewUrl,
            $this->coverPreviewUrl,
            $this->avatarFocalY,
            $this->coverFocalY,
        );
    }

    /**
     * @param  array<string, string>|null  $socialLinks
     * @return list<array{platform: string, handle: string}>
     */
    private function socialLinksToRepeater(?array $socialLinks): array
    {
        if ($socialLinks === null) {
            return [];
        }

        $result = [];

        foreach ($socialLinks as $platform => $handle) {
            $result[] = ['platform' => $platform, 'handle' => $handle];
        }

        return $result;
    }

    /**
     * @param  array<int|string, array<string, mixed>>  $repeaterData
     * @return array<string, string>
     */
    private function repeaterToSocialLinks(array $repeaterData): array
    {
        $links = [];

        foreach ($repeaterData as $item) {
            $platform = $item['platform'] ?? null;
            $handle = $item['handle'] ?? null;

            if (filled($platform) && filled($handle)) {
                $key = $platform instanceof SocialPlatform ? $platform->value : (string) $platform;
                $links[$key] = (string) $handle;
            }
        }

        return $links;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>|null null tells Filament to skip creating/saving this row
     */
    private function normalizeWorkExperienceData(array $data): ?array
    {
        $keyFields = ['company_name', 'position', 'description', 'start_date'];

        $hasAnyData = collect($keyFields)->contains(fn (string $field): bool => filled($data[$field] ?? null));

        if (!$hasAnyData) {
            return null;
        }

        if ($data['is_currently_working_here'] ?? false) {
            $data['end_date'] = null;
        }

        return $data;
    }

    /**
     * @return list<array{skill_id: string, proficiency: SkillProficiency, years_experience: int|null}>
     */
    private function skillsToRepeater(Profile $profile): array
    {
        $skills = [];

        foreach ($profile->profileSkills()->get() as $profileSkill) {
            $skills[] = [
                'skill_id' => $profileSkill->skill_id,
                'proficiency' => $profileSkill->proficiency,
                'years_experience' => $profileSkill->years_experience,
            ];
        }

        return $skills;
    }

    private function appendEmptyRepeaterItemIfLastRow(Select|TextInput $component): void
    {
        $repeater = $component->getParentRepeater();

        if (!$repeater instanceof Repeater) {
            return;
        }

        $items = $repeater->getRawState();

        if (!is_array($items) || blank($items)) {
            return;
        }

        $repeaterPath = $repeater->getStatePath();
        $componentPath = $component->getStatePath();

        if ($repeaterPath === null || $componentPath === null) {
            return;
        }

        $currentKey = explode('.', mb_substr($componentPath, mb_strlen($repeaterPath) + 1))[0];

        if ($currentKey !== array_key_last($items)) {
            return;
        }

        $newUuid = $repeater->generateUuid();

        if ($newUuid) {
            $items[$newUuid] = [];
        } else {
            $items[] = [];
        }

        $repeater->rawState($items);

        $childSchema = $repeater->getChildSchema($newUuid ?? array_key_last($items));

        if ($childSchema instanceof Schema) {
            $childSchema->fill();
        }

        $repeater->collapsed(condition: false, shouldMakeComponentCollapsible: false);
        $repeater->callAfterStateUpdated();
    }

    /**
     * Skill ids already chosen in the other rows of the skills repeater, so the
     * search can omit them and each skill is only pickable once.
     *
     * @return list<string>
     */
    private function skillIdsInSiblingRows(Select $component): array
    {
        $repeater = $component->getParentRepeater();

        if (!$repeater instanceof Repeater) {
            return [];
        }

        $rows = $repeater->getRawState();

        if (!is_array($rows)) {
            return [];
        }

        return array_values(
            collect($rows)
                ->pluck('skill_id')
                ->reject(fn (mixed $id): bool => blank($id) || $id === $component->getState())
                ->map(fn (mixed $id): string => (string) $id)
                ->all()
        );
    }

    /**
     * @param  array<int|string, array<string, mixed>>  $repeaterData
     * @return list<ProfileSkillDTO>
     */
    private function repeaterToSkills(array $repeaterData): array
    {
        $skills = [];

        foreach ($repeaterData as $item) {
            $skillId = $item['skill_id'] ?? null;
            $proficiency = $item['proficiency'] ?? null;

            if (blank($skillId)) {
                continue;
            }

            $proficiency = $proficiency instanceof SkillProficiency
                ? $proficiency
                : SkillProficiency::tryFrom((string) $proficiency);

            // Guarda payload adulterado/inválido: sem isso, o from() no DTO lançaria
            // ValueError antes da validação graciosa do SyncProfileSkills.
            if (!$proficiency instanceof SkillProficiency) {
                continue;
            }

            $skills[] = ProfileSkillDTO::fromArray([
                'skill_id' => $skillId,
                'proficiency' => $proficiency,
                'years_experience' => $item['years_experience'] ?? null,
            ]);
        }

        return $skills;
    }
}
