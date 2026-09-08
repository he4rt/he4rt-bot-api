{{--
    A capa do onboarding mensal: o deck é o material do evento, então abre
    recebendo quem chegou, não fazendo balanço. A edição é derivada e os
    apresentadores vêm da curadoria; só o texto é editorial, como na outra capa.
--}}
@props(['since', 'until', 'edition', 'hosts' => [], 'coverTitle' => null, 'coverIntro' => null])
@php
    $month = $since->timezone(config('app.display_timezone'))->translatedFormat('F Y');
@endphp
<section class="slide" data-label="Boas-vindas">
    <div class="slide-inner" style="text-align: center; max-width: 900px">
        <div data-anim>
            <img class="cover-heart" src="{{ asset('images/retro-heart.png') }}" width="86" height="86" alt="He4rt" />
        </div>
        <div class="kicker" data-anim style="margin-top: 20px">
            ONBOARDING · {{ $month }} · {{ $edition }}ª EDIÇÃO
        </div>
        <h1 class="hero" data-anim>
            @if (filled($coverTitle))
                {{ $coverTitle }}
            @else
                Bem-vindo à He4rt, <em>dev</em>
            @endif
        </h1>
        <svg class="ecg" viewBox="0 0 560 60" preserveAspectRatio="none" data-anim aria-hidden="true">
            <path d="M0 34 H210 l10 0 l8 -7 l11 14 l13 -34 l15 50 l11 -22 l9 0 H340 l9 0 l8 -6 l9 12 l12 -28 l13 40 l10 -16 l8 0 H560" />
        </svg>
        <p class="lead" data-anim style="margin: 8px auto 0">
            @if (filled($coverIntro))
                {{ $coverIntro }}
            @else
                Nos próximos minutos você vai ver <b>quem somos</b>, <b>o que a gente faz</b> e <b>onde entrar</b>.
            @endif
        </p>
        @if (count($hosts))
            <div class="cover-hosts" data-anim>
                <span class="cover-host-label">{{ count($hosts) === 1 ? 'Quem apresenta' : 'Quem apresenta hoje' }}</span>
                <div class="cover-host-list">
                    @foreach ($hosts as $host)
                        <div class="cover-host">
                            <img
                                class="mini"
                                src="{{ $host->avatar }}"
                                onerror="this.onerror=null;this.src='https://github.com/{{ $host->username }}.png'"
                                width="52"
                                height="52"
                                alt="{{ $host->username }}"
                            />
                            <div class="cover-host-text">
                                <span class="cover-host-name">{{ $host->name }}</span>
                                <span class="cover-host-handle">{{ '@' . $host->username }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
        <div class="hint" data-anim>navegue com <kbd>←</kbd> <kbd>→</kbd></div>
    </div>
</section>
