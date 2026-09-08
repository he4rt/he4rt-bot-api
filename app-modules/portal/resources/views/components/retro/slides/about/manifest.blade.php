{{--
    O que a He4rt é, contada pela própria história. Primeiro slide depois da capa:
    os números das fontes só significam alguma coisa depois de se saber de quem
    eles são.

    Os marcos são copy editorial e vivem aqui. Os únicos números vêm do PERÍODO
    da edição e da idade da comunidade — nada aqui soma fontes (ADR-0001).
--}}
@use(He4rt\Portal\Retrospective\AboutSection)
@php
    $age = AboutSection::ageAt($until);
    $days = (int) $since->diffInDays($until, absolute: true);

    /*
     | A linha do tempo para nesta edição: um deck de 2021 não pode anunciar o que
     | ainda não tinha acontecido. Recorte antigo mostra uma He4rt mais nova, que é
     | exatamente a que ele retrata.
     */
    $milestones = collect([
        ['year' => 2018, 'text' => 'A He4rt nasce numa live do Daniel Reis, com o servidor oficial da comunidade.'],
        ['year' => 2019, 'text' => 'Lançamos o 4noobs: ensino gratuito feito pela própria comunidade, e nosso maior case.'],
        ['year' => 2020, 'text' => 'Primeira comunidade do Brasil — e provavelmente do mundo — a sediar um evento completo da Microsoft.'],
        ['year' => 2021, 'text' => 'He4rt Conf: a primeira conferência da comunidade, com +42 mil espectadores únicos.'],
        ['year' => 2022, 'text' => 'Primeiro meetup presencial, em parceria com a 44SP, a Microsoft e outros apoiadores.'],
        ['year' => 2026, 'text' => '25 mil membros no Discord, plataforma própria e o LaravelDaySP com a 3Pontos, na casa dos 77 mil na Twitch.'],
    ])->filter(fn (array $milestone): bool => $milestone['year'] <= $until->year)->values();

    /*
     | A onda é gerada, não desenhada à mão: um pico por marco, centrado na coluna
     | dele. Assim a linha do tempo continua batendo certo quando um marco entra,
     | sai pelo recorte, ou quando alguém acrescenta o próximo ano.
     |
     | `preserveAspectRatio="none"` deixa a onda acompanhar a largura; por isso o
     | ponto de cada marco é HTML por cima, e não um <circle> que viraria elipse.
     */
    $width = 1000;
    $baseline = 72;
    $step = $milestones->isEmpty() ? $width : $width / $milestones->count();
    $spike = [[-34, 72], [-24, 72], [-17, 60], [-9, 88], [0, 18], [7, 96], [15, 64], [24, 72], [34, 72]];

    $wave = 'M0 '.$baseline;

    foreach ($milestones as $position => $milestone) {
        $center = ($position + 0.5) * $step;

        foreach ($spike as [$offset, $y]) {
            $wave .= ' L'.round($center + $offset, 1).' '.$y;
        }
    }

    $wave .= ' L'.$width.' '.$baseline;
@endphp
<section class="slide" data-label="A He4rt">
    <div class="slide-inner">
        <span class="sec-tag" data-anim>Quem somos</span>
        <h2 class="sec" data-anim>A He4rt bate desde {{ $milestones->first()['year'] ?? 2018 }}</h2>
        <p class="sec-sub" data-anim style="max-width: 68ch">
            Uma comunidade brasileira de gente que programa junto, onde
            <b style="color: var(--text)">quem está começando</b> divide o mesmo espaço com
            <b style="color: var(--text)">quem já vive disso</b>. Começou numa live e nunca mais parou.
        </p>

        <div data-anim style="display: flex; flex-wrap: wrap; gap: 9px; margin-top: 16px">
            <span class="bdg neu">{{ $age }} @choice('ano de comunidade|anos de comunidade', $age)</span>
            <span class="bdg neu">{{ number_format($days, 0, ',', '.') }} @choice('dia neste recorte|dias neste recorte', $days)</span>
            <span class="bdg neu">{{ count($sources) }} @choice('frente medida|frentes medidas', count($sources))</span>
        </div>

        <div class="tl" data-anim>
            <div class="tl-line" aria-hidden="true">
                <svg class="tl-ecg" viewBox="0 0 {{ $width }} 104" preserveAspectRatio="none">
                    <path d="{{ $wave }}" />
                </svg>

                @foreach ($milestones as $position => $milestone)
                    <span
                        @class(['tl-dot', 'is-now' => $position === $milestones->count() - 1])
                        style="left: {{ round((($position + 0.5) / $milestones->count()) * 100, 3) }}%; --tl-i: {{ $position }}"
                    ></span>
                @endforeach
            </div>

            <div class="tl-cols">
                @foreach ($milestones as $milestone)
                    <div class="tl-col">
                        <span class="tl-year">{{ $milestone['year'] }}</span>
                        <p class="tl-text">{{ $milestone['text'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>
