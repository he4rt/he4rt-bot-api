@props (['items', 'size' => 48, 'startRank' => null])
@php
    // O maior total do trilho vira a régua de todas as barras de composição.
    $maxTotal = max([1, ...array_column($items, 'total')]);
@endphp
<x-portal::retro.carousel>
    @foreach ($items as $i => $person)
        <div class="pslide" data-anim>
            <x-portal::retro.person-card
                :person="$person"
                :rank="$startRank !== null ? $startRank + $i : null"
                :size="$size"
                :max-total="$maxTotal"
            />
        </div>
    @endforeach
</x-portal::retro.carousel>
