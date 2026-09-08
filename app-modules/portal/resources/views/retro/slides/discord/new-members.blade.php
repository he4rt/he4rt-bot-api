<section class="slide" data-label="Novos">
    <div class="slide-inner" style="text-align: center">
        <span class="sec-tag" data-anim>Chegando</span>
        <h2 class="sec" data-anim>Gente nova na He4rt</h2>
        <p class="sec-sub" data-anim style="margin: 0 auto">
            Cada pessoa que entrou é uma nova história começando na comunidade.
        </p>
        <div class="stats" data-anim style="margin-top: 26px; justify-content: center">
            <div class="stat">
                <div class="v accent">{{ number_format($joins, 0, ',', '.') }}</div>
                <div class="l">Novos membros</div>
            </div>
            @if ($boosts > 0)
                <div class="stat">
                    <div class="v">{{ number_format($boosts, 0, ',', '.') }}</div>
                    <div class="l">Boosts</div>
                </div>
            @endif
        </div>
    </div>
</section>
