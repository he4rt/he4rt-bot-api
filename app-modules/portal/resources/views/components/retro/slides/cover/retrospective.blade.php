@props(['since', 'until', 'coverTitle' => null, 'coverIntro' => null])
@php
    $fmt = fn ($d): string => $d instanceof \Carbon\CarbonInterface
        ? $d->timezone(config('app.display_timezone'))->format('d/m/Y')
        : (string) $d;
@endphp
<section class="slide" data-label="Abertura">
    <div class="slide-inner" style="text-align: center; max-width: 900px">
        <div data-anim>
            <img class="cover-heart" src="{{ asset('images/retro-heart.png') }}" width="86" height="86" alt="He4rt" />
        </div>
        <div class="kicker" data-anim style="margin-top: 20px">
            RETROSPECTIVA · {{ $fmt($since) }} — {{ $fmt($until) }}
        </div>
        <h1 class="hero" data-anim>
            @if (filled($coverTitle))
                {{ $coverTitle }}
            @else
                Quem fez a He4rt <em>bater</em>
            @endif
        </h1>
        <svg class="ecg" viewBox="0 0 560 60" preserveAspectRatio="none" data-anim aria-hidden="true">
            <path d="M0 34 H210 l10 0 l8 -7 l11 14 l13 -34 l15 50 l11 -22 l9 0 H340 l9 0 l8 -6 l9 12 l12 -28 l13 40 l10 -16 l8 0 H560" />
        </svg>
        <p class="lead" data-anim style="margin: 8px auto 0">
            @if (filled($coverIntro))
                {{ $coverIntro }}
            @else
                Participação da comunidade <b>He4rt</b> em cada frente — <b>gente, código, conversa e presença</b>.
            @endif
        </p>
        <div class="hint" data-anim>navegue com <kbd>←</kbd> <kbd>→</kbd></div>
    </div>
</section>
