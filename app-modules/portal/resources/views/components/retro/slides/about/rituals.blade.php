{{--
    O que a comunidade faz toda semana, e como assistir sem precisar de coragem.
    Segundo slide da apresentação: depois de dizer quem a He4rt é, dizer o que dá
    para fazer nela hoje.

    O diagrama é o dado, não enfeite: o RAIO da órbita é a raridade do encontro e
    a velocidade da volta é a frequência. Semanal gira por dentro e rápido; o que
    só acontece quando surge o tema orbita longe e devagar. A cor do ponto é a
    mesma do marcador do item, que é o que liga a figura à lista.
--}}
@php
    $initiatives = [
        [
            'title' => 'Reunião Semanal',
            'when' => 'Segunda · 21h',
            'text' => 'Novidades, projetos e atualizações da comunidade — e, claro, as fofocas. Avisos no Discord e no WhatsApp.',
            'color' => '#f4f2f8',
            'orbit' => 46,
            'duration' => 13,
        ],
        [
            'title' => 'Aulões',
            'when' => 'Geralmente quarta',
            'text' => 'Aulas técnicas gratuitas, sobre os mais diversos assuntos, ministradas pelos próprios membros.',
            'color' => '#c44bff',
            'orbit' => 71,
            'duration' => 26,
        ],
        [
            'title' => 'Spaces',
            'when' => 'Quando surge o tema',
            'text' => 'Conversas sobre carreira e orientação, com convidados de dentro e de fora da comunidade.',
            'color' => '#b69bff',
            'orbit' => 96,
            'duration' => 44,
        ],
    ];
@endphp
<section class="slide" data-label="Iniciativas">
    <div class="slide-inner">
        <span class="sec-tag" data-anim>Nossas iniciativas</span>
        <h2 class="sec" data-anim>Comece participando:<br />só entrar, ouvir e acompanhar</h2>
        <p class="sec-sub" data-anim>
            Em todas: sem precisar ligar câmera ou microfone. Dá para só ouvir e interagir pelo chat.
        </p>

        <div class="rit-grid">
            <div class="orb" data-anim aria-hidden="true">
                @foreach ($initiatives as $index => $initiative)
                    <div class="orb-ring" style="width: {{ $initiative['orbit'] }}%; height: {{ $initiative['orbit'] }}%">
                        {{-- Atraso negativo: cada ponto entra em cena num lugar
                             diferente da volta, em vez de os três largarem
                             alinhados no topo. --}}
                        <div
                            class="orb-arm"
                            style="--orb-dur: {{ $initiative['duration'] }}s; animation-delay: -{{ $index * 5 + 2 }}s"
                        >
                            <span class="orb-dot" style="--orb-color: {{ $initiative['color'] }}"></span>
                        </div>
                    </div>
                @endforeach

                <span class="orb-label">frequência de encontro</span>
            </div>

            <div class="rit" data-anim>
                @foreach ($initiatives as $initiative)
                    <div class="rit-item">
                        <span class="rit-bullet" style="--rit-color: {{ $initiative['color'] }}"></span>

                        <div>
                            <div class="rit-head">
                                <span class="rit-title">{{ $initiative['title'] }}</span>
                                <span class="rit-when">{{ $initiative['when'] }}</span>
                            </div>
                            <p class="rit-desc">{{ $initiative['text'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>
