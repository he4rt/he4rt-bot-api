{{--
    Onde encontrar a comunidade e como entrar. Fecha a apresentação para quem
    chegou de fora — daqui em diante o deck só fala em números, e eles já vão
    fazer sentido.

    Constelação: os canais são estrelas na MESMA órbita, equidistantes do
    coração da He4rt. Nenhum canal manda — a igualdade não é dita, é geométrica,
    e "a He4rt não cabe num lugar só" vira literal: toda porta dá no mesmo
    centro. Por isso não há CTA — o destino é o núcleo, as portas são todas.

    Os canais saem do config `he4rt.social_media`, a mesma fonte da página
    /redes: um link novo entra na órbita e as estrelas se redistribuem sozinhas,
    sem ninguém lembrar de vir editar o deck.
--}}
@php
    $links = \He4rt\Portal\SocialLinks\SocialLinksPage::links();
    $count = max(count($links), 1);

    /*
     | Raio em % da caixa da constelação, não em px: a órbita escala com o slide
     | e sobra borda para o rótulo de cada estrela não vazar do quadrado.
     */
    $radius = 41;
@endphp
<section class="slide" data-label="Onde entrar">
    <div class="slide-inner">
        <div class="join-grid">
            <div>
                <span class="sec-tag" data-anim>Onde encontrar</span>
                <h2 class="sec" data-anim>Todos os caminhos dão no mesmo lugar</h2>
                <p class="sec-sub" data-anim>
                    Não existe porta principal. Cada canal é uma entrada com a mesma distância
                    do centro — <b style="color: var(--text)">sem processo seletivo, nível mínimo
                    nem linguagem certa</b>. Escolhe a tua e chega.
                </p>
                <p class="hint" data-anim>↗ cada estrela é clicável</p>
            </div>

            {{--
                `--n` dimensiona o ciclo de energia: a cada tique de 2,5s a
                estrela seguinte acende e o raio dela alimenta o núcleo, dando a
                volta completa em `$count` tiques. Estrela e raio compartilham o
                mesmo `--i`, então os dois pulsam juntos.
            --}}
            <div class="const" data-anim style="--n: {{ $count }}">
                <div class="const-ring" aria-hidden="true"></div>

                @foreach ($links as $position => $link)
                    <span
                        class="const-spoke"
                        aria-hidden="true"
                        style="
                            transform: rotate({{ round(-90 + ($position * 360 / $count), 2) }}deg);
                            --i: {{ $position }};
                            --ac: {{ $link->accentDark ?? $link->accent }};
                        "
                    ></span>
                @endforeach

                <span class="const-core" aria-hidden="true">&lt;4</span>

                @foreach ($links as $position => $link)
                    @php
                        $angle = deg2rad(-90 + ($position * 360 / $count));
                    @endphp
                    <a
                        class="const-star"
                        href="{{ $link->url }}"
                        target="_blank"
                        rel="noopener"
                        style="
                            --sx: {{ round(50 + $radius * cos($angle), 2) }}%;
                            --sy: {{ round(50 + $radius * sin($angle), 2) }}%;
                            --ac: {{ $link->accentDark ?? $link->accent }};
                            --i: {{ $position }};
                        "
                    >
                        <span class="const-star-ic">
                            <x-filament::icon :icon="$link->icon" />
                        </span>

                        <span class="const-star-label">
                            <span class="const-star-name">{{ $link->label }}</span>
                            <span class="const-star-host">
                                {{ \Illuminate\Support\Str::of($link->url)->after('//')->before('/') }}
                            </span>
                        </span>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</section>
