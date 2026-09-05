{{-- O preview roda no Alpine, sem ida ao servidor: arrastar precisa responder
     na hora. O valor entra no state do formulário e só é salvo no submit. --}}
<div
    x-data="{ focalY: $wire.$entangle('{{ $getStatePath() }}') }"
    class="space-y-3"
>
    <div
        @class([
            'overflow-hidden ring-1 ring-gray-950/5 dark:ring-white/10',
            'mx-auto w-40 rounded-full' => $isCircle ?? false,
            'rounded-lg' => !($isCircle ?? false),
        ])
        style="aspect-ratio: {{ $aspectRatio }}"
    >
        <img
            src="{{ $imageUrl }}"
            alt=""
            class="h-full w-full object-cover"
            :style="`object-position: center ${focalY}%`"
        />
    </div>

    <input
        type="range"
        min="0"
        max="100"
        step="1"
        x-model.number="focalY"
        class="w-full accent-primary-600"
        aria-label="{{ __('panel-app::profile.hints.adjust_framing') }}"
    />
</div>
