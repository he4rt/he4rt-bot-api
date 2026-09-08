@props(['card', 'size' => 56])
{{--
    O cartão de uma pessoa no ritual da tag. As métricas vêm empilhadas POR FONTE
    e nunca somadas: um PR e uma mensagem não são "+1" equivalente (ADR-0001), e
    o que garante isso no desenho é cada faixa carregar o nome de quem a produziu.

    Quem só tem uma plataforma ligada renderiza uma faixa só — sem espaço vazio
    reservado para a que não existe.
--}}
<article class="promo-card">
    <div class="promo-head">
        <img
            class="promo-avatar"
            src="{{ $card->avatar }}"
            width="{{ $size }}"
            height="{{ $size }}"
            alt="{{ $card->name }}"
            style="width: {{ $size }}px; height: {{ $size }}px"
        />

        <div style="flex: 1; min-width: 0">
            <div class="promo-name">{{ $card->name }}</div>
            <div class="promo-handle">{{ '@' . $card->username }}</div>
        </div>
    </div>

    @foreach ($card->groups as $group)
        <div class="promo-source">
            <span class="promo-source-name">{{ $group->sourceLabel }}</span>
            <span class="promo-metrics">
                @foreach ($group->metrics as $metric)
                    <span class="promo-metric">
                        <b>{{ number_format($metric->value, 0, ',', '.') }}</b>
                        {{ $metric->label }}
                    </span>
                @endforeach
            </span>
        </div>
    @endforeach

    @if (filled($card->reason))
        <p class="promo-reason">{{ $card->reason }}</p>
    @endif
</article>
