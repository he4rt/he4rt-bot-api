<div class="rounded-xl border border-zinc-200/80 bg-zinc-50/70 p-4 text-xs dark:border-white/10 dark:bg-white/[0.03]" style="padding: 1rem;">
    <div class="mb-2.5 flex items-center gap-2 font-semibold text-zinc-900 dark:text-zinc-100">
        <x-filament::icon icon="heroicon-m-information-circle" class="size-4 text-purple-600 dark:text-purple-400" :size="\Filament\Support\Enums\IconSize::Small" />
        <span>{{ __('panel-app::profile.hints.username_rules_title') }}</span>
    </div>
    <ul class="space-y-2 text-zinc-600 dark:text-zinc-400">
        <li class="flex items-start gap-2">
            <x-filament::icon icon="heroicon-m-check" class="mt-0.5 size-4 shrink-0 text-purple-600 dark:text-purple-400" :size="\Filament\Support\Enums\IconSize::Small" />
            <span>{{ __('panel-app::profile.hints.username_rule_length') }}</span>
        </li>
        <li class="flex items-start gap-2">
            <x-filament::icon icon="heroicon-m-check" class="mt-0.5 size-4 shrink-0 text-purple-600 dark:text-purple-400" :size="\Filament\Support\Enums\IconSize::Small" />
            <span>{{ __('panel-app::profile.hints.username_rule_characters') }}</span>
        </li>
        <li class="flex items-start gap-2">
            <x-filament::icon icon="heroicon-m-check" class="mt-0.5 size-4 shrink-0 text-purple-600 dark:text-purple-400" :size="\Filament\Support\Enums\IconSize::Small" />
            <span>{{ __('panel-app::profile.hints.username_rule_format') }}</span>
        </li>
        <li class="flex items-start gap-2">
            <x-filament::icon icon="heroicon-m-check" class="mt-0.5 size-4 shrink-0 text-purple-600 dark:text-purple-400" :size="\Filament\Support\Enums\IconSize::Small" />
            <span>{{ __('panel-app::profile.hints.username_rule_consecutive') }}</span>
        </li>
        <li class="flex items-start gap-2">
            <x-filament::icon icon="heroicon-m-clock" class="mt-0.5 size-4 shrink-0 text-purple-600 dark:text-purple-400" :size="\Filament\Support\Enums\IconSize::Small" />
            <span>{{ __('panel-app::profile.hints.username_rule_cooldown') }}</span>
        </li>
    </ul>
</div>
