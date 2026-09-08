<section class="slide" data-label="Reações">
    <div class="slide-inner">
        <span class="sec-tag" data-anim>O feedback</span>
        <h2 class="sec" data-anim>As reações do período</h2>
        <p class="sec-sub" data-anim>
            <b style="color: var(--text)">{{ number_format($total, 0, ',', '.') }} reações</b> distribuídas nas mensagens.
        </p>
        @if (count($emojis))
            <div data-anim style="display: flex; flex-wrap: wrap; gap: 10px; margin-top: 22px">
                @foreach ($emojis as $emoji)
                    <span class="bdg neu" style="font-size: 1rem; padding: 8px 14px">
                        {{ $emoji['custom'] ? ':'.$emoji['name'].':' : $emoji['name'] }}
                        <b style="color: var(--text); margin-left: 6px">{{ number_format($emoji['count'], 0, ',', '.') }}</b>
                    </span>
                @endforeach
            </div>
        @endif
    </div>
</section>
