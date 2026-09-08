<section class="slide" data-label="Destaques">
    <div class="slide-inner">
        <span class="sec-tag" data-anim>Gente, não número</span>
        <h2 class="sec" data-anim>Quem segurou a He4rt</h2>
        <p class="sec-sub" data-anim>
            O recorte inteiro que veio antes é o que essas pessoas construíram — uma mensagem, uma call e um PR por vez.
        </p>

        {{-- Rola por dentro: a lista não tem teto por decisão editorial, e é
             preferível que o operador precise rolar a que alguém fique de fora. --}}
        <div class="promo-grid" data-anim>
            @foreach ($cards as $card)
                <x-portal::retro.promotion-card :card="$card" />
            @endforeach
        </div>
    </div>
</section>
