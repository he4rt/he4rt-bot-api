<div class="flex items-start gap-3 rounded-xl border border-amber-500/20 bg-amber-500/5 p-4 text-xs dark:border-amber-500/20 dark:bg-amber-500/[0.04]" style="padding: 1rem;">
    <div class="mt-0.5 flex size-6 shrink-0 items-center justify-center rounded-lg bg-amber-500/15 text-amber-600 dark:bg-amber-400/15 dark:text-amber-400">
        <x-filament::icon icon="heroicon-m-shield-exclamation" class="size-4" :size="\Filament\Support\Enums\IconSize::Small" />
    </div>
    <div class="flex-1 space-y-1 leading-relaxed">
        <p class="font-semibold text-zinc-900 dark:text-zinc-100">
            {{ __('panel-app::profile.hints.admin_warning_title') }}
        </p>
        <p class="text-zinc-600 dark:text-zinc-300">
            {{ __('panel-app::profile.hints.admin_warning_body') }}
        </p>
    </div>
</div>
