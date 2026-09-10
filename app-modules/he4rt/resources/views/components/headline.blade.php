@props([
    "as" => "div",
    "align" => "left",
    "size" => "lg",
    "animation" => "fade-up",
    "keywords" => [],
    "titleTag" => "h1",
])

@php
    $tag = $as;
    $animate = $animation !== "none";

    $classes = collect([
        "hp-headline",
        "hp-headline-align-" . $align,
        "hp-headline-size-" . $size,
    ])
        ->filter()
        ->implode(" ");

    $animations = [
        "fade-up" => [
            "enter" => "transition-all duration-700 ease-in",
            "start" => "translate-y-6 opacity-0",
            "end" => "translate-y-0 opacity-100",
        ],
        "scale" => [
            "enter" => "transition-all duration-700 ease-in",
            "start" => "scale-95 opacity-0",
            "end" => "scale-100 opacity-100",
        ],
        "blur" => [
            "enter" => "transition-all duration-700 ease-out",
            "start" => "opacity-0 blur-sm",
            "end" => "blur-0 opacity-100",
        ],
    ];

    $anim = $animations[$animation] ?? [];
@endphp

<div {{ $attributes->class($classes) }}>
    <{{ $tag }}
        class="hp-headline-container"
        @if ($animate)
            x-data="{ shown: false }"
            x-intersect.threshold.20.once="shown = true"
        @endif
    >
        @isset($badge)
            <div
                class="hp-headline-badge"
                @if ($animate)
                    x-show="shown"
                    x-transition:enter="{{ $anim["enter"] }}"
                    x-transition:enter-start="{{ $anim["start"] }}"
                    x-transition:enter-end="{{ $anim["end"] }}"
                    x-cloak
                @endif
            >
                {{ $badge }}
            </div>
        @endisset

        <div class="hp-headline-content space-y-4">
            @isset($title)
                <{{ $titleTag }}
                    {{ $title->attributes->class(["hp-headline-title", "delay-100" => $animate]) }}
                    @if ($animate)
                        x-show="shown"
                        x-transition:enter="{{ $anim["enter"] }}"
                        x-transition:enter-start="{{ $anim["start"] }}"
                        x-transition:enter-end="{{ $anim["end"] }}"
                        x-cloak
                    @endif
                >
                    @foreach (str($title)->explode(" ") as $word)
                        @if (in_array(trim($word), $keywords))
                            <span class="hp-headline-highlight">{{ $word }}</span>
                        @else
                            {{ $word }}
                        @endif

                        @unless ($loop->last)
                            {{ " " }}
                        @endunless
                    @endforeach
                </{{ $titleTag }}>
            @endisset

            @isset($subtitle)
                <div
                    {{ $subtitle->attributes->class(["hp-headline-subtitle", "delay-200" => $animate]) }}
                    @if ($animate)
                        x-show="shown"
                        x-transition:enter="{{ $anim["enter"] }}"
                        x-transition:enter-start="{{ $anim["start"] }}"
                        x-transition:enter-end="{{ $anim["end"] }}"
                        x-cloak
                    @endif
                >
                    {{ $subtitle }}
                </div>
            @endisset

            @isset($description)
                <p
                    {{ $description->attributes->class(["hp-headline-description", "delay-300" => $animate]) }}
                    @if ($animate)
                        x-show="shown"
                        x-transition:enter="{{ $anim["enter"] }}"
                        x-transition:enter-start="{{ $anim["start"] }}"
                        x-transition:enter-end="{{ $anim["end"] }}"
                        x-cloak
                    @endif
                >
                    {{ $description }}
                </p>
            @endisset
        </div>

        @isset($actions)
            <div
                class="hp-headline-actions {{ $animate ? "delay-400" : "" }}"
                @if ($animate)
                    x-show="shown"
                    x-transition:enter="{{ $anim["enter"] }}"
                    x-transition:enter-start="{{ $anim["start"] }}"
                    x-transition:enter-end="{{ $anim["end"] }}"
                    x-cloak
                @endif
            >
                {{ $actions }}
            </div>
        @endisset
    </{{ $tag }}>
</div>
