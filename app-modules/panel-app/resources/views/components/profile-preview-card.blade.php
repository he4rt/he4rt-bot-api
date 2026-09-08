@props ([
    'data' => [],
    'user' => null,
    'character' => null,
    'initials' => '',
    'avatarPreviewUrl' => null,
    'coverPreviewUrl' => null,
    'coverAspectRatio',
    'coverFocalY' => 50,
    'avatarFocalY' => 50
])

@php
    $name = $user?->name ?? '';
    $username = $user?->username ?? '';
    $nickname = $data['nickname'] ?? null;
    $headline = $data['headline'] ?? null;
    $about = $data['about'] ?? null;
    $yearsExperience = $data['years_experience'] ?? null;
    $available = (bool) ($data['available_for_proposals'] ?? false);

    $seniorityRaw = $data['seniority_level'] ?? null;
    $seniority =
        $seniorityRaw instanceof \He4rt\Profile\Enums\SeniorityLevel
            ? $seniorityRaw
            : (is_string($seniorityRaw)
                ? \He4rt\Profile\Enums\SeniorityLevel::tryFrom($seniorityRaw)
                : null);

    $startRaw = $data['start_availability'] ?? null;
    $startAvailability =
        $startRaw instanceof \He4rt\Profile\Enums\StartAvailability
            ? $startRaw
            : (is_string($startRaw)
                ? \He4rt\Profile\Enums\StartAvailability::tryFrom($startRaw)
                : null);

    $socialLinks = collect($data['social_links'] ?? [])->filter(
        fn($item) => !empty($item['platform']) && !empty($item['handle']),
    );

    $address = $data['address'] ?? [];
    $location = \App\Geo\Support\GeoLocation::formatLocation(
        $address['city'] ?? null,
        $address['state'] ?? null,
        $address['country'] ?? null,
    );

    $level = $character?->level ?? 1;
    $experience = $character?->experience ?? 0;
    $nextThreshold = \He4rt\Gamification\Character\Models\Character::LEVEL_THRESHOLDS[$level + 1] ?? $experience;
    $currentThreshold = \He4rt\Gamification\Character\Models\Character::LEVEL_THRESHOLDS[$level] ?? 0;
    $xpPercent =
        $nextThreshold > $currentThreshold
            ? round((($experience - $currentThreshold) / ($nextThreshold - $currentThreshold)) * 100)
            : 100;
    $badges = $character?->badges ?? collect();

    $skillLabels = \He4rt\Profile\Models\Skill::labelsById();
    $skills = collect($data['skills'] ?? [])
        ->map(function ($item) use ($skillLabels) {
            $skillId = $item['skill_id'] ?? null;

            if (empty($skillId) || !isset($skillLabels[$skillId])) {
                return null;
            }

            $proficiencyRaw = $item['proficiency'] ?? null;
            $proficiency =
                $proficiencyRaw instanceof \He4rt\Profile\Enums\SkillProficiency
                    ? $proficiencyRaw
                    : (is_string($proficiencyRaw)
                        ? \He4rt\Profile\Enums\SkillProficiency::tryFrom($proficiencyRaw)
                        : null);

            return [
                'name' => $skillLabels[$skillId],
                'proficiency' => $proficiency,
            ];
        })
        ->filter();
@endphp

<div
    class="w-full overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900"
>
    {{-- Header gradient / cover --}}
    <div
        class="relative bg-gradient-to-r from-purple-600 to-purple-500"
        style="aspect-ratio: {{ $coverAspectRatio }}"
    >
        @if ($coverPreviewUrl)
            <img
                src="{{ $coverPreviewUrl }}"
                alt=""
                class="absolute inset-0 h-full w-full object-cover"
                style="object-position: center {{ $coverFocalY }}%"
            />
        @endif

        {{-- Level badge --}}
        <div
            class="absolute top-3 right-3 rounded-md bg-white/20 px-2 py-0.5 text-xs font-bold text-white backdrop-blur-sm"
        >
            LVL {{ $level }}
        </div>

        {{-- Avatar --}}
        <div class="absolute -bottom-8 left-6">
            @if ($avatarPreviewUrl)
                <img
                    src="{{ $avatarPreviewUrl }}"
                    alt="{{ $name }}"
                    style="object-position: center {{ $avatarFocalY }}%"
                    class="h-16 w-16 rounded-full border-4 border-white object-cover shadow-md dark:border-gray-800"
                />
            @else
                <div
                    class="flex h-16 w-16 items-center justify-center rounded-full border-4 border-white bg-purple-600 text-lg font-bold text-white shadow-md ring-1 ring-black/5 dark:border-gray-800 dark:ring-white/15"
                >
                    {{ $initials }}
                </div>
            @endif
        </div>
    </div>

    {{-- Content --}}
    <div class="space-y-4 px-6 pt-12 pb-6">
        {{-- Name + available badge --}}
        <div>
            <div class="flex items-center gap-2">
                <h3 class="text-base font-bold text-gray-900 dark:text-white">{{ $name }}</h3>
                @if ($available)
                    <span
                        class="inline-flex items-center gap-1 rounded-full bg-green-50 px-2 py-0.5 text-xs font-medium text-green-700 dark:bg-green-500/10 dark:text-green-400"
                    >
                        <span class="h-1.5 w-1.5 rounded-full bg-green-500"></span>
                        {{ __('panel-app::profile.preview.available') }}
                    </span>
                @endif
            </div>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ '@' }}{{ $username }}</p>
            @if ($location)
                <p class="mt-0.5 flex items-center gap-1 text-xs text-gray-400 dark:text-gray-500">
                    <x-heroicon-m-map-pin class="h-3 w-3" />
                    {{ $location }}
                </p>
            @endif
        </div>

        {{-- Headline --}}
        @if ($headline)
            <p class="text-sm text-gray-700 dark:text-gray-300">{{ $headline }}</p>
        @endif

        {{-- Seniority + years --}}
        @if ($seniority || $yearsExperience)
            <div class="flex flex-wrap items-center gap-2">
                @if ($seniority)
                    <span
                        @class ([
                            'inline-block rounded-md px-2 py-0.5 text-xs font-semibold',
                            'bg-green-50 text-green-700 dark:bg-green-500/10 dark:text-green-400' =>
                                $seniority === \He4rt\Profile\Enums\SeniorityLevel::Junior,
                            'bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400' =>
                                $seniority === \He4rt\Profile\Enums\SeniorityLevel::Mid,
                            'bg-purple-50 text-purple-700 dark:bg-purple-500/10 dark:text-purple-400' =>
                                $seniority === \He4rt\Profile\Enums\SeniorityLevel::Senior,
                            'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400' =>
                                $seniority === \He4rt\Profile\Enums\SeniorityLevel::Specialist,
                            'bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-400' =>
                                $seniority === \He4rt\Profile\Enums\SeniorityLevel::Lead
                        ])
                    >
                        {{ $seniority->getLabel() }}
                    </span>
                @endif
                @if ($yearsExperience)
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                        {{ trans_choice('panel-app::profile.preview.years_experience', $yearsExperience, ['count' => $yearsExperience]) }}
                    </span>
                @endif
            </div>
        @endif

        {{-- Bio --}}
        @if ($about)
            <p class="text-sm leading-relaxed text-gray-600 dark:text-gray-400">{{ Str::limit($about, 200) }}</p>
        @endif

        {{-- Skills --}}
        @if ($skills->isNotEmpty())
            <div>
                <span class="mb-1.5 block text-xs font-semibold tracking-wider text-gray-500 uppercase dark:text-gray-400">
                    {{ __('panel-app::profile.preview.skills') }}
                </span>
                <div class="flex flex-wrap gap-1.5">
                    @foreach ($skills as $skill)
                        <span
                            class="inline-flex items-center gap-1 rounded-md bg-purple-50 px-2 py-0.5 text-xs font-medium text-purple-700 dark:bg-purple-500/10 dark:text-purple-300"
                        >
                            {{ $skill['name'] }}
                            @if ($skill['proficiency'])
                                <span class="text-purple-300 dark:text-purple-500">·</span>
                                {{ $skill['proficiency']->getLabel() }}
                            @endif
                        </span>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- XP bar --}}
        @if ($character)
            <div>
                <div class="mb-1 flex items-center justify-between">
                    <span class="text-xs font-semibold tracking-wider text-gray-500 uppercase dark:text-gray-400"
                        >{{ __('panel-app::profile.preview.experience') }}</span
                    >
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                        <span
                            class="font-semibold text-purple-600 dark:text-purple-400"
                            >{{ number_format($experience) }}</span
                        >
                        / {{ number_format($nextThreshold) }} XP
                    </span>
                </div>
                <div class="h-1.5 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                    <div
                        class="h-full rounded-full bg-purple-600 transition-all duration-500 dark:bg-purple-500"
                        style="width: {{ $xpPercent }}%"
                    ></div>
                </div>
            </div>
        @endif

        {{-- Badges --}}
        @if ($badges->isNotEmpty())
            <div class="flex flex-wrap gap-1.5">
                @foreach ($badges->take(5) as $badge)
                    <span
                        class="inline-block rounded-full border border-gray-200 bg-gray-50 px-2.5 py-0.5 text-xs font-medium text-gray-700 dark:border-white/10 dark:bg-gray-800 dark:text-gray-300"
                    >
                        {{ $badge->name }}
                    </span>
                @endforeach
            </div>
        @endif

        {{-- Social links --}}
        @if ($socialLinks->isNotEmpty())
            <div class="flex flex-wrap gap-2">
                @foreach ($socialLinks as $link)
                    @php
                        $platform =
                            $link['platform'] instanceof \He4rt\Profile\Enums\SocialPlatform
                                ? $link['platform']
                                : \He4rt\Profile\Enums\SocialPlatform::tryFrom($link['platform']);
                    @endphp
                    @if ($platform)
                        <a
                            href="{{ $platform->getUrl($link['handle']) }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center gap-1 text-xs font-medium text-purple-600 hover:text-purple-800 dark:text-purple-400 dark:hover:text-purple-200 transition-colors"
                        >
                            <x-filament::icon :icon="$platform->getBrandIcon()" class="h-3.5 w-3.5 shrink-0" />
                            {{ $platform->getLabel() }}
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 12 12" class="h-3 w-3 shrink-0" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.5 9.5 9.5 2.5M5 2.5h4.5V7" />
                            </svg>
                        </a>
                    @endif
                @endforeach
            </div>
        @endif

        {{-- Start availability --}}
        @if ($available && $startAvailability)
            <p class="text-xs font-medium text-green-600 dark:text-green-400">
                {{ __('panel-app::profile.preview.can_start') }} {{ $startAvailability->getLabel() }}
            </p>
        @endif
    </div>

    {{-- Footer --}}
    <div class="border-t border-gray-100 px-6 py-3 dark:border-white/5">
        <p class="text-center text-xs text-gray-400 dark:text-gray-500">{{ __('panel-app::profile.preview.footer') }}</p>
    </div>
</div>
