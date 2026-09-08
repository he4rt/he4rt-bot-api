@php
    /*
     | Props vêm do VoiceBoardSlide, mas um snapshot congelado antes deste painel
     | existir só traz participants/xp/channels — daí os defaults. O deck publicado
     | continua abrindo; republicar preenche o resto.
     */
    $joins ??= 0;
    $earners ??= 0;
    $peak ??= null;
    $people ??= [];
    $hours ??= [];

    $fmt = fn (int $value): string => number_format($value, 0, ',', '.');

    // Barra proporcional ao canal mais movimentado; o resto se mede contra ele.
    $busiest = max(1, ...array_map(static fn (array $c): int => $c['joins'] ?? $c['events'] ?? 0, $channels ?: [[]]));
    $busiestHour = max(1, ...array_map(static fn (array $h): int => $h['joins'], $hours ?: [['joins' => 0]]));
    $peakHour = collect($hours)->sortByDesc('joins')->first();
@endphp

<section class="slide" data-label="Voz">
    {{-- O painel é denso: a variante compacta encolhe o padding do slide e o
         tamanho do título para o conteúdo caber sem rolagem. --}}
    <div class="slide-inner is-dense">
        <span class="sec-tag" data-anim>As calls</span>
        <h2 class="sec" data-anim>Quem viveu no voice</h2>

        {{-- Sem linha de resumo aqui: ela repetiria os dois primeiros números. --}}
        <div class="vb-totals" data-anim>
            <div class="vb-total">
                <b>{{ $fmt($participants) }}</b>
                <span>Pessoas em call</span>
            </div>
            <div class="vb-total">
                <b>{{ $fmt($joins) }}</b>
                <span>Entradas em call</span>
            </div>
            <div class="vb-total">
                <b>{{ $fmt($xp) }}</b>
                <span>XP em voz</span>
            </div>
            <div class="vb-total">
                <b>{{ $fmt($earners) }}</b>
                <span>Pessoas com XP</span>
            </div>
            @if ($peak)
                <div class="vb-total">
                    <b>{{ $fmt($peak['joins']) }}</b>
                    <span>Pico · {{ $peak['date'] }}</span>
                </div>
            @endif
        </div>

        <div class="vb-cols" data-anim>
            @if (count($channels))
                <div class="vb-panel">
                    <span class="vb-title">Arenas · canais por entrada</span>

                    <ul class="vb-arenas">
                        @foreach ($channels as $channel)
                            @php
                                $chJoins = $channel['joins'] ?? $channel['events'] ?? 0;
                                $rooms = $channel['rooms'] ?? 1;
                            @endphp
                            <li class="vb-arena">
                                <div class="vb-arena-head">
                                    {{-- Nome como está no Discord, emoji incluso: é assim que a
                                         pessoa reconhece o canal. Sem uppercase, que empurraria o
                                         emoji para fora de escala. --}}
                                    <span class="vb-arena-name">{{ $channel['name'] }}</span>

                                    @if ($rooms > 1)
                                        <span class="vb-chip" title="Salas temporárias de mesmo nome, agrupadas">×{{ $rooms }}</span>
                                    @endif

                                    <span class="vb-arena-meta">
                                        {{ $fmt($channel['people'] ?? 0) }} pessoas · {{ $fmt($channel['xp'] ?? 0) }} XP
                                    </span>

                                    <span class="vb-arena-joins"><b>{{ $fmt($chJoins) }}</b> entradas</span>
                                </div>

                                <div class="vb-track">
                                    <span class="vb-fill" style="width: {{ max(1.5, ($chJoins / $busiest) * 100) }}%"></span>
                                </div>
                            </li>
                        @endforeach
                    </ul>

                    <p class="vb-note">Salas de mesmo nome contam juntas (×N).</p>
                </div>
            @endif

            @if (count($people))
                <div class="vb-panel">
                    <span class="vb-title">Quem mais viveu no voice</span>

                    <div class="vb-rank-legend">
                        <span></span><span></span><span>XP</span><span>Entradas</span><span>Canais</span>
                    </div>

                    <ol class="vb-rank">
                        @foreach ($people as $index => $person)
                            <li class="vb-rank-row">
                                <span class="vb-rank-n">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                                <span class="vb-rank-name">{{ $person['name'] }}</span>
                                <span class="vb-rank-xp">{{ $fmt($person['xp']) }}</span>
                                <span class="vb-rank-meta">{{ $fmt($person['joins']) }}</span>
                                <span class="vb-rank-meta">{{ $fmt($person['channels']) }}</span>
                            </li>
                        @endforeach
                    </ol>
                </div>
            @endif
        </div>

        @if (count($hours))
            <div class="vb-panel vb-hours" data-anim>
                <div class="vb-hours-head">
                    <span class="vb-title">Entradas por hora</span>
                    @if ($peakHour)
                        <span class="vb-hours-peak">
                            pico {{ str_pad((string) $peakHour['hour'], 2, '0', STR_PAD_LEFT) }}h · {{ $fmt($peakHour['joins']) }}
                        </span>
                    @endif
                </div>

                <div class="vb-bars">
                    @foreach ($hours as $slot)
                        <span
                            @class(['vb-bar', 'is-peak' => $peakHour && $slot['hour'] === $peakHour['hour']])
                            style="height: {{ max(4, ($slot['joins'] / $busiestHour) * 100) }}%"
                            title="{{ str_pad((string) $slot['hour'], 2, '0', STR_PAD_LEFT) }}h · {{ $fmt($slot['joins']) }} entradas"
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
