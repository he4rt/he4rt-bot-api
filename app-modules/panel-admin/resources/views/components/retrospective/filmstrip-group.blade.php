{{--
    Um bloco da tira: o cabeçalho da fonte (nome, contagem, on/off, ordem) e as
    miniaturas que ela emitiu.

    O grupo aparece mesmo desligado e mesmo sem slide nenhum — é aqui que mora o
    interruptor que o religa. Capa e fecho passam sem `group`: são slides do deck,
    não fontes, e não têm o que ligar ou reordenar.
--}}
@use(He4rt\PanelAdmin\Filament\Resources\Retrospectives\Support\InspectorMode)

@props([
    'label',
    'icon' => null,
    'group' => null,
    'first' => false,
    'last' => false,
])

<div
    @class([
        'shrink-0 rounded-lg border px-2 py-1.5 transition',
        'border-gray-200 dark:border-white/10' => $group === null || $group->visible,
        'border-dashed border-gray-300 bg-gray-50/60 dark:border-white/10 dark:bg-white/[0.02]' => $group !== null && ! $group->visible,
    ])
>
    <div class="mb-1.5 flex h-6 items-center gap-1.5">
        @if ($icon)
            <x-filament::icon :icon="$icon" class="h-4 w-4 shrink-0 text-gray-400" />
        @endif

        @if ($group === null)
            <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">{{ $label }}</span>
        @else
            <button
                type="button"
                x-on:click="$dispatch('filmstrip-call', { method: 'select', args: ['{{ InspectorMode::Source->value }}:{{ $group->key }}'] })"
                class="truncate rounded-md text-xs font-semibold text-gray-700 hover:text-primary-600 dark:text-gray-300 dark:hover:text-primary-400"
            >{{ $label }}</button>

            <span class="shrink-0 text-[0.7rem] text-gray-400 dark:text-gray-500">
                {{ $group->slideCount() }} {{ \Illuminate\Support\Str::plural('slide', $group->slideCount()) }}
            </span>

            {{-- Interruptor da fonte inteira. Curadoria de apresentação: re-deriva
                 do snapshot na composição, sem republicar. --}}
            <button
                type="button"
                role="switch"
                aria-checked="{{ $group->visible ? 'true' : 'false' }}"
                aria-label="{{ $group->visible ? 'Ocultar' : 'Exibir' }} {{ $label }} no deck"
                x-on:click="$dispatch('filmstrip-call', { method: 'toggleSource', args: ['{{ $group->key }}'] })"
                @class([
                    'relative ms-auto h-4 w-7 shrink-0 rounded-full transition',
                    'bg-primary-600' => $group->visible,
                    'bg-gray-200 dark:bg-white/10' => ! $group->visible,
                ])
            >
                <span
                    @class([
                        'absolute top-0.5 h-3 w-3 rounded-full bg-white shadow transition-all',
                        'start-[0.875rem]' => $group->visible,
                        'start-0.5' => ! $group->visible,
                    ])
                ></span>
            </button>

            <x-filament::icon-button
                icon="heroicon-m-chevron-left"
                size="xs"
                color="gray"
                label="Adiantar {{ $label }} no deck"
                :disabled="$first"
                x-on:click="$dispatch('filmstrip-call', { method: 'moveSource', args: ['{{ $group->key }}', -1] })"
            />

            <x-filament::icon-button
                icon="heroicon-m-chevron-right"
                size="xs"
                color="gray"
                label="Atrasar {{ $label }} no deck"
                :disabled="$last"
                x-on:click="$dispatch('filmstrip-call', { method: 'moveSource', args: ['{{ $group->key }}', 1] })"
            />
        @endif
    </div>

    <div class="flex items-start gap-1.5">
        {{ $slot }}
    </div>
</div>
