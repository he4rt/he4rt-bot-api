@props(['sources', 'since', 'until', 'closingText' => null])
@php
    $fmt = fn ($d): string => $d instanceof \Carbon\CarbonInterface
        ? $d->timezone(config('app.display_timezone'))->format('d/m/Y')
        : (string) $d;
    $people = collect($sources)
        ->flatMap(fn ($source) => $source->slides)
        ->first(fn ($slide): bool => $slide->kind() === 'github.core')
        ?->toArray()['people'] ?? [];
@endphp
<section class="slide" data-label="Obrigado">
    <div class="slide-inner" style="text-align: center; max-width: 940px">
        <h2 class="sec" data-anim>Obrigado a quem fez<br />o coração bater 💜</h2>
        <p class="sec-sub" data-anim style="margin: 0 auto">
            @if (filled($closingText))
                {{ $closingText }}
            @else
                Cada PR, cada mensagem, cada call e cada reação manteve a He4rt viva.
            @endif
        </p>
        @if (count($people))
            <div class="closing-wall" data-anim>
                @foreach ($people as $person)
                    <img
                        class="mini"
                        src="{{ $person['avatar'] }}"
                        onerror="this.onerror=null;this.src='https://github.com/{{ $person['login'] }}.png'"
                        loading="lazy"
                        width="46"
                        height="46"
                        alt="{{ $person['login'] }}"
                        title="{{ $person['login'] }}"
                    />
                @endforeach
            </div>
        @endif
        <p class="hint" data-anim style="margin-top: 30px">{{ $fmt($since) }} — {{ $fmt($until) }}</p>
    </div>
</section>
