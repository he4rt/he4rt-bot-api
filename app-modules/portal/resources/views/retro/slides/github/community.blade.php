@php ($tail = array_slice($people, 5))
@php ($maxTotal = max([1, ...array_column($tail, 'total')]))
<section class="slide" data-label="A comunidade">
    <div class="slide-inner">
        <span class="sec-tag" data-anim>A cauda que sustenta</span>
        <h2 class="sec" data-anim>E toda a comunidade</h2>
        <p class="sec-sub" data-anim>Mais {{ count($tail) }} {{ count($tail) === 1 ? 'pessoa entrou' : 'pessoas entraram' }} com reviews, issues, comentários e commits. Cada toque conta.</p>
        <div class="pgrid" style="margin-top: 22px">
            @foreach ($tail as $person)
                <div data-anim><x-portal::retro.person-card :person="$person" :size="44" :compact="true" :max-total="$maxTotal" /></div>
            @endforeach
        </div>
    </div>
</section>
