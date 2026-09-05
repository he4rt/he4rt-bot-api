{{--
    O slide da entrega da tag. `data-steps` é o contrato que o deck lê: enquanto
    houver passo pendente, a seta direita revela em vez de trocar de slide.

    A conta é 2 + 3 por pessoa — o passo 0 abre com a pergunta e o palco vazio,
    cada pessoa gasta três (quem é, o que fez, por que a tag é dela), e o passo
    final recua a câmera para a foto oficial, com todo mundo lado a lado.

    A encenação é um dolly: o palco tem profundidade (perspective no CSS) e cada
    pessoa ocupa um plano — avançar não troca de card, viaja até ele. Quem já foi
    apresentado sai por trás da câmera até o finale trazer todos de volta.
    `--shift` é a posição de cada card nessa foto final, calculada aqui porque o
    CSS não sabe quantos cards existem.
--}}
<section class="slide promo-tag" data-label="A tag He4rt" data-steps="{{ 2 + count($cards) * 3 }}">
    <div class="slide-inner" style="text-align: center">
        <span class="sec-tag" data-reveal="0" style="margin-inline: auto">O ritual</span>
        <h2 class="sec" data-reveal="0">A tag He4rt vai para…</h2>

        <div class="promo-stage">
            @foreach ($cards as $position => $card)
                @php ($step = $position * 3 + 1)
                <article
                    class="promo-hero"
                    data-reveal="{{ $step }}"
                    style="--shift: {{ $position - (count($cards) - 1) / 2 }}"
                >
                    <span class="promo-avatar-ring">
                        <img
                            class="promo-avatar big"
                            src="{{ $card->avatar }}"
                            width="112"
                            height="112"
                            alt="{{ $card->name }}"
                        />
                    </span>

                    <div class="promo-name big">{{ $card->name }}</div>
                    <div class="promo-handle">{{ '@' . $card->username }}</div>

                    @if ($card->memberSince !== null)
                        @php ($since = $card->memberSince->timezone(config('app.display_timezone')))
                        @php ($years = (int) $since->diffInYears())
                        <div class="promo-since">
                            na comunidade desde <b>{{ $since->translatedFormat('F \d\e Y') }}</b>
                            @if ($years >= 1)
                                · {{ $years }} {{ $years === 1 ? 'ano' : 'anos' }} de He4rt
                            @endif
                        </div>
                    @endif

                    <div class="promo-hero-metrics" data-reveal="{{ $step + 1 }}">
                        @foreach ($card->groups as $group)
                            <div class="promo-source" style="--promo-i: {{ $loop->index }}">
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
                    </div>

                    @if (filled($card->reason))
                        <p class="promo-reason big" data-reveal="{{ $step + 2 }}">{{ $card->reason }}</p>
                    @endif
                </article>
            @endforeach

            {{-- Marcador do finale: não desenha nada — quando o deck o marca
                 `.shown`, o CSS recua a câmera e alinha todos os cards. --}}
            <span class="promo-finale" data-reveal="{{ 1 + count($cards) * 3 }}" aria-hidden="true"></span>
        </div>
    </div>
</section>
