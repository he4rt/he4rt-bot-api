{{-- Linha do tempo de atividade: régua de calendário, três trilhas de calor,
     streamgraph do GitHub por tipo e as duas séries do Discord.

     O SVG é montado imperativamente pelo Alpine, então o nó vive sob `wire:ignore`
     — sem isso o Livewire reescreve a árvore e apaga o desenho a cada roundtrip. --}}

<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            {{ __('panel-admin::contributions.timeline.heading', ['days' => $meta['days'], 'timezone' => $timezoneLabel]) }}
        </x-slot>

        <x-slot name="description">
            {{ __('panel-admin::contributions.timeline.hint') }}
        </x-slot>

        <style>
            .he4rt-timeline {
                --tl-bg0: #ffffff;
                --tl-bg1: #ffffff;
                --tl-line: #e5e7eb;
                --tl-line2: #d1d5db;
                --tl-ink2: #6b7280;
                --tl-ink3: #9ca3af;
                --tl-acc: #782bf1;
                --tl-acc2: #7c3aed;
                --tl-acc3: #6d28d9;
                --tl-l0: #f3f4f6;
                --tl-l1: #ddd0fd;
                --tl-l2: #b99cf8;
                --tl-l3: #8f5cf0;
                --tl-l4: #5b21b6;
                --tl-t0: #4c1d95;
                --tl-t1: #6d28d9;
                --tl-t2: #8b5cf6;
                --tl-t3: #a78bfa;
                --tl-t4: #c4b5fd;
                --tl-t5: #ddd6fe;
                --tl-on-dark: #f5f3ff;
                --tl-on-light: #1f1235;
            }

            .dark .he4rt-timeline {
                --tl-bg0: #0b0a12;
                --tl-bg1: #12111c;
                --tl-line: #242134;
                --tl-line2: #322d47;
                --tl-ink2: #a9a4c2;
                --tl-ink3: #7d7898;
                --tl-acc: #782bf1;
                --tl-acc2: #a985ff;
                --tl-acc3: #c9b6ff;
                --tl-l0: #18162a;
                --tl-l1: #5333ae;
                --tl-l2: #7147df;
                --tl-l3: #9470fa;
                --tl-l4: #c2aaff;
                --tl-t0: #5b3ac0;
                --tl-t1: #7048e0;
                --tl-t2: #8a66f6;
                --tl-t3: #a688ff;
                --tl-t4: #c3adff;
                --tl-t5: #e2d8ff;
                --tl-on-dark: #f3efff;
                --tl-on-light: #0b0a12;
            }

            .he4rt-timeline .tl-canvas {
                overflow-x: auto;
                overflow-y: hidden;
                -webkit-overflow-scrolling: touch;
            }

            .he4rt-timeline .tl-canvas svg {
                display: block;
                width: 100%;
                min-width: 760px;
                height: auto;
                user-select: none;
                touch-action: pan-y;
                font-family: inherit;
            }

            .he4rt-timeline .tl-mono {
                font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            }

            .he4rt-timeline .tl-day {
                cursor: crosshair;
            }

            .he4rt-timeline .tl-day:focus-visible {
                stroke: var(--tl-acc2);
                stroke-width: 1.5;
                outline: none;
            }

            .he4rt-timeline .tl-layer {
                cursor: pointer;
                transition: opacity 0.15s;
            }

            .he4rt-timeline .tl-layer-label {
                font-size: 9px;
                font-weight: 600;
                letter-spacing: 0.06em;
                text-transform: uppercase;
                pointer-events: none;
            }

            .he4rt-timeline .tl-swatch {
                width: 11px;
                height: 11px;
                border-radius: 2px;
                display: inline-block;
            }

            .he4rt-tooltip {
                position: fixed;
                z-index: 60;
                pointer-events: none;
                min-width: 210px;
                border-radius: 9px;
                padding: 8px 10px;
                font-size: 11.5px;
                line-height: 1.45;
                background: var(--tl-bg1);
                color: var(--tl-ink2);
                border: 1px solid var(--tl-line2);
                box-shadow: 0 12px 34px -12px rgb(0 0 0 / 0.45);
            }

            .he4rt-tooltip .tl-tip-h {
                display: flex;
                justify-content: space-between;
                gap: 10px;
                align-items: baseline;
                margin-bottom: 5px;
                font-weight: 600;
                color: var(--tl-acc3);
            }

            .he4rt-tooltip .tl-tip-h small {
                font-weight: 400;
                color: var(--tl-ink3);
                font-size: 10px;
            }

            .he4rt-tooltip .tl-tip-r {
                display: flex;
                justify-content: space-between;
                gap: 12px;
                padding: 1px 0;
            }

            .he4rt-tooltip .tl-tip-r.is-on {
                color: var(--tl-acc3);
            }

            .he4rt-tooltip .tl-tip-r i {
                display: inline-block;
                width: 8px;
                height: 8px;
                border-radius: 2px;
                margin-right: 6px;
            }

            .he4rt-tooltip .tl-tip-r b {
                font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
                font-variant-numeric: tabular-nums;
            }

            .he4rt-tooltip .tl-tip-sep {
                height: 1px;
                background: var(--tl-line);
                margin: 5px 0;
            }

            .he4rt-tooltip .tl-tip-n {
                color: var(--tl-ink3);
                font-size: 10.5px;
            }
        </style>

        <div
            wire:ignore
            class="he4rt-timeline"
            x-load
            x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('activity-timeline', 'he4rt/panel-admin') }}"
            x-data="activityTimeline(@js($payload))"
            x-on:keydown.escape="clear()"
        >
            <div class="mb-3 flex flex-wrap items-center gap-x-4 gap-y-2">
                <div class="flex items-center gap-1.5 text-xs" style="color: var(--tl-ink3)">
                    <span>{{ __('panel-admin::contributions.timeline.legend.less') }}</span>
                    @foreach (['--tl-l0', '--tl-l1', '--tl-l2', '--tl-l3', '--tl-l4'] as $level)
                        <i class="tl-swatch" style="background: var({{ $level }})"></i>
                    @endforeach
                    <span>{{ __('panel-admin::contributions.timeline.legend.more') }}</span>

                    <span class="ms-3 inline-flex items-center gap-1.5">
                        <i
                            class="tl-swatch"
                            style="border: 1px solid var(--tl-line2); background: repeating-linear-gradient(45deg, transparent 0 2px, var(--tl-line2) 2px 3px)"
                        ></i>
                        {{ __('panel-admin::contributions.timeline.legend.no_data') }}
                    </span>
                </div>

                <div class="ms-auto flex items-center gap-2">
                    @foreach (['github' => 'GitHub', 'discord' => 'Discord'] as $layer => $label)
                        <button
                            type="button"
                            role="switch"
                            x-bind:aria-checked="layers.{{ $layer }} ? 'true' : 'false'"
                            x-on:click="toggleLayer('{{ $layer }}')"
                            x-bind:class="layers.{{ $layer }}
                                ? 'border-primary-500 text-primary-600 dark:text-primary-400'
                                : 'border-gray-200 text-gray-400 dark:border-white/10 dark:text-gray-500'"
                            class="rounded-md border px-2 py-1 text-xs font-medium transition"
                        >{{ $label }}</button>
                    @endforeach

                    <button
                        type="button"
                        x-on:click="clear()"
                        x-bind:disabled="! sel"
                        class="rounded-md border border-gray-200 px-2 py-1 text-xs font-medium text-gray-500 transition disabled:opacity-40 dark:border-white/10 dark:text-gray-400"
                    >{{ __('panel-admin::contributions.timeline.clear') }}</button>
                </div>
            </div>

            <div class="tl-canvas" x-ref="canvas"></div>

            <p class="mt-2 text-xs" style="color: var(--tl-ink3)" x-text="selectionLabel"></p>

            <div class="he4rt-tooltip" role="tooltip" x-ref="tip" x-show="tip.open" x-html="tip.html" x-cloak></div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
