{{--
    A He4rt fora da tela: os eventos presenciais de que a comunidade participou.
    Vem logo depois do manifesto para desmontar cedo a ideia de "comunidade só
    online" — antes de falar do que acontece toda semana no Discord.

    Slide fixo: a lista é editorial e as fotos moram em
    public/images/retro/events/<slug>.webp. Trocar um evento é trocar uma linha
    aqui e uma imagem lá; nada vem do snapshot.

    Só entra evento que já tinha acontecido no fim do recorte — a mesma regra dos
    marcos do manifesto: um deck de 2021 não pode mostrar uma foto de 2026.
--}}
@php
    $events = collect([
        [
            'title' => 'Meetup He4rt',
            'year' => 2022,
            'place' => '42 São Paulo',
            'text' => 'O primeiro encontro presencial da comunidade: a galera inteira, de máscara e crachá, atrás da placa.',
            'image' => 'meetup-2022-galera.webp',
        ],
        [
            'title' => 'Meetup He4rt',
            'year' => 2022,
            'place' => '42 São Paulo',
            'text' => 'Palestras no corredor lotado da 42, com a camiseta do drip nas costas.',
            'image' => 'meetup-2022-palestra.webp',
        ],
        [
            'title' => 'Meetup He4rt',
            'year' => 2022,
            'place' => '42 São Paulo',
            'text' => 'Presencial e ao vivo: o chat da Twitch discutindo se o PHP morreu enquanto a gente conversava no sofá.',
            'image' => 'meetup-2022-transmissao.webp',
        ],
        [
            'title' => 'After do Meetup',
            'year' => 2022,
            'place' => 'São Paulo',
            'text' => 'Depois das palestras, a calçada do bar virou extensão do Discord.',
            'image' => 'meetup-2022-after-galera.webp',
        ],
        [
            'title' => 'Campus Party',
            'year' => 2022,
            'place' => 'São Paulo',
            'text' => 'A He4rt acampada na Campus Party, de placa em punho pra achar a galera no meio da multidão.',
            'image' => 'campus-party-2022-galera.webp',
        ],
        [
            'title' => 'Campus Party',
            'year' => 2022,
            'place' => 'São Paulo',
            'text' => 'Coraçãozinho com a mão e a placa da He4rt: o jeito de se encontrar no meio de milhares de campuseiros.',
            'image' => 'campus-party-2022-coracao.webp',
        ],
        [
            'title' => 'He4rt Pub',
            'year' => 2026,
            'place' => 'São Paulo',
            'text' => 'Fevereiro de 2026: uma mesa de bar não bastou, e a comunidade tomou o pub inteiro.',
            'image' => 'he4rt-pub-2026.webp',
        ],
    ])->filter(fn (array $event): bool => $event['year'] <= $until->year)->values();
@endphp
<section class="slide" data-label="Eventos">
    <div class="slide-inner">
        <span class="sec-tag" data-anim>Fora da tela</span>
        <h2 class="sec" data-anim>Não somos só uma comunidade online</h2>
        <p class="sec-sub" data-anim>
            Meetups, conferências e encontros por onde a He4rt passou — com gente de verdade, sem precisar de call.
        </p>

        @if ($events->isEmpty())
            <p class="hint evt-empty" data-anim>
                Neste recorte a He4rt ainda era só online. Os encontros presenciais vieram depois.
            </p>
        @else
        <x-portal::retro.carousel class="evt-carousel">
            @foreach ($events as $event)
                <div class="pslide" data-anim>
                    <figure class="evt-card">
                        <img
                            class="evt-photo"
                            src="{{ asset('images/retro/events/'.$event['image']) }}"
                            alt="{{ $event['title'] }} — {{ $event['place'] }}, {{ $event['year'] }}"
                            loading="lazy"
                            draggable="false"
                        />
                        <figcaption class="evt-meta">
                            <div class="evt-head">
                                <span class="evt-title">{{ $event['title'] }}</span>
                                <span class="evt-when">{{ $event['year'] }} · {{ $event['place'] }}</span>
                            </div>
                            <p class="evt-desc">{{ $event['text'] }}</p>
                        </figcaption>
                    </figure>
                </div>
            @endforeach
        </x-portal::retro.carousel>
        @endif
    </div>
</section>
