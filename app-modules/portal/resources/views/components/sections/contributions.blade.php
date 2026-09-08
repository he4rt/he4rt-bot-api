@props(['panorama'])

@unless ($panorama->isEmpty())
    <section class="hp-section" id="contribuicoes">
        <div class="hp-page hp-container">
            <x-he4rt::headline align="center" size="md" :keywords="['aberto']">
                <x-slot:badge>
                    <x-he4rt::badge>
                        <x-filament::icon icon="heroicon-o-code-bracket" class="h-5 w-5" />
                        Construído em aberto
                    </x-he4rt::badge>
                </x-slot>

                <x-slot:title>Nosso trabalho é aberto</x-slot>

                <x-slot:description>
                    Tudo que a comunidade constrói fica registrado: código, revisão, discussão e
                    artigo. Estes são os números de quem já conectou suas contas.
                </x-slot>
            </x-he4rt::headline>

            <dl class="cp-stats">
                <div class="cp-stat">
                    <dt>contribuições registradas</dt>
                    <dd>{{ number_format($panorama->total, 0, ',', '.') }}</dd>
                </div>
                <div class="cp-stat">
                    <dt>pessoas construindo</dt>
                    <dd>{{ $panorama->people }}</dd>
                </div>
                <div class="cp-stat">
                    <dt>contando desde</dt>
                    <dd class="cp-stat-soft">{{ $panorama->sinceLabel() }}</dd>
                </div>
            </dl>

            <figure class="cp-chart">
                <figcaption>Os últimos doze meses</figcaption>

                <div class="cp-bars" role="img" aria-label="Contribuições por mês nos últimos doze meses">
                    @foreach ($panorama->timeline as $month)
                        <div class="cp-bar" title="{{ $month->count }} em {{ $month->fullLabel }}">
                            <span class="cp-bar-fill" style="height: {{ $month->height }}%"></span>
                            <span class="cp-bar-label">{{ $month->label }}</span>
                        </div>
                    @endforeach
                </div>
            </figure>

            <figure class="cp-chart">
                <figcaption>De que é feito esse trabalho</figcaption>

                <div class="cp-composition" role="img" aria-label="Composição das contribuições por tipo">
                    @foreach ($panorama->composition as $slice)
                        <span
                            class="cp-segment"
                            style="width: {{ $slice->share }}%; background: {{ $slice->color }}"
                            title="{{ $slice->count }} · {{ $slice->label }}"
                        ></span>
                    @endforeach
                </div>

                <ul class="cp-legend">
                    @foreach ($panorama->composition as $slice)
                        <li>
                            <span class="cp-dot" style="background: {{ $slice->color }}"></span>
                            <span class="cp-legend-label">{{ $slice->label }}</span>
                            <span class="cp-legend-count">{{ number_format($slice->count, 0, ',', '.') }}</span>
                        </li>
                    @endforeach
                </ul>
            </figure>
        </div>
    </section>
@endunless
