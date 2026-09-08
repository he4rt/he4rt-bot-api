@php
    /*
     | Props vêm do MessagesSlide, mas um snapshot congelado antes deste painel
     | existir só traz total/with_reactions/pinned/chatters — daí os defaults.
     | O deck publicado continua abrindo; republicar preenche o resto.
     */
    $people ??= 0;
    $peak ??= null;
    $days ??= [];
    $hours ??= [];

    $fmt = fn (int $value): string => number_format($value, 0, ',', '.');

    $weekdays = [1 => 'Segunda', 2 => 'Terça', 3 => 'Quarta', 4 => 'Quinta', 5 => 'Sexta', 6 => 'Sábado', 7 => 'Domingo'];

    // Barra proporcional ao dia mais falante; o resto se mede contra ele.
    $busiestDay = max(1, ...array_map(static fn (array $d): int => $d['messages'], $days ?: [['messages' => 0]]));
    $busiestHour = max(1, ...array_map(static fn (array $h): int => $h['messages'], $hours ?: [['messages' => 0]]));
    $peakHour = collect($hours)->sortByDesc('messages')->first();
@endphp

<section class="slide" data-label="Conversas">
    {{-- O painel é denso como o de voz: a variante compacta encolhe o padding
         do slide e o tamanho do título para o conteúdo caber sem rolagem. --}}
    <div class="slide-inner is-dense">
        <span class="sec-tag" data-anim>O papo</span>
        <h2 class="sec" data-anim>O que rolou no chat</h2>

        {{-- Sem linha de resumo aqui: ela repetiria o primeiro número. --}}
        <div class="vb-totals" data-anim>
            <div class="vb-total">
                <b>{{ $fmt($total) }}</b>
                <span>Mensagens</span>
            </div>
            @if ($people > 0)
                <div class="vb-total">
                    <b>{{ $fmt($people) }}</b>
                    <span>Pessoas no papo</span>
                </div>
            @endif
            <div class="vb-total">
                <b>{{ $fmt($with_reactions) }}</b>
                <span>Com reação</span>
            </div>
            <div class="vb-total">
                <b>{{ $fmt($pinned) }}</b>
                <span>Fixadas</span>
            </div>
            @if ($peak)
                <div class="vb-total">
                    <b>{{ $fmt($peak['messages']) }}</b>
                    <span>Pico · {{ $peak['date'] }}</span>
                </div>
            @endif
        </div>

        <div class="vb-cols" data-anim>
            @if (count($days))
                <div class="vb-panel">
                    <span class="vb-title">O ritmo da semana</span>

                    <ul class="vb-arenas">
                        @foreach ($days as $day)
                            <li class="vb-arena">
                                <div class="vb-arena-head">
                                    <span class="vb-arena-name">{{ $weekdays[$day['weekday']] }}</span>

                                    <span class="vb-arena-joins"><b>{{ $fmt($day['messages']) }}</b> msgs</span>
                                </div>

                                <div class="vb-track">
                                    <span class="vb-fill" style="width: {{ max(1.5, ($day['messages'] / $busiestDay) * 100) }}%"></span>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (count($chatters))
                <div class="vb-panel">
                    <span class="vb-title">Quem segurou o papo</span>

                    <div class="vb-rank-legend">
                        <span></span><span></span><span>Msgs</span><span>Reações</span><span>XP</span>
                    </div>

                    <ol class="vb-rank">
                        @foreach ($chatters as $index => $chatter)
                            <li class="vb-rank-row">
                                <span class="vb-rank-n">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                                <span class="vb-rank-name">{{ $chatter['name'] }}</span>
                                <span class="vb-rank-xp">{{ $fmt($chatter['messages']) }}</span>
                                <span class="vb-rank-meta">{{ $fmt($chatter['reactions'] ?? 0) }}</span>
                                <span class="vb-rank-meta">{{ $fmt($chatter['xp'] ?? 0) }}</span>
                            </li>
                        @endforeach
                    </ol>
                </div>
            @endif
        </div>

        @if (count($hours))
            <div class="vb-panel vb-hours" data-anim>
                <div class="vb-hours-head">
                    <span class="vb-title">Mensagens por hora</span>
                    @if ($peakHour)
                        <span class="vb-hours-peak">
                            pico {{ str_pad((string) $peakHour['hour'], 2, '0', STR_PAD_LEFT) }}h · {{ $fmt($peakHour['messages']) }}
                        </span>
                    @endif
                </div>

                <div class="vb-bars">
                    @foreach ($hours as $slot)
                        <span
                            @class(['vb-bar', 'is-peak' => $peakHour && $slot['hour'] === $peakHour['hour']])
                            style="height: {{ max(4, ($slot['messages'] / $busiestHour) * 100) }}%"
                            title="{{ str_pad((string) $slot['hour'], 2, '0', STR_PAD_LEFT) }}h · {{ $fmt($slot['messages']) }} msgs"
                        ></span>
                    @endforeach
                </div>

                <div class="vb-hours-axis">
                    <span>0h</span>
                    <span>23h</span>
                </div>
            </div>
        @endif
    </div>
</section>
