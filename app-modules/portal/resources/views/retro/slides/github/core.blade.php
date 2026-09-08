@php ($top = array_slice($people, 0, 5))
<section class="slide" data-label="O núcleo">
    <div class="slide-inner">
        <span class="sec-tag" data-anim>O núcleo</span>
        <h2 class="sec" data-anim>Quem puxou a frente</h2>
        <p class="sec-sub" data-anim>As pessoas que concentraram o grosso da entrega — abrindo PRs, revisando código umas das outras e mantendo as discussões vivas.</p>
        @if (count($top))
            <div style="margin-top: 20px">
                <x-portal::retro.people-carousel :items="$top" :start-rank="1" :size="48" />
            </div>
        @endif
    </div>
</section>
