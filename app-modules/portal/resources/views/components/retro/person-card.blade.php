@props (['person', 'rank' => null, 'size' => 44, 'compact' => false, 'maxTotal' => null])
@php ($ns = $size >= 80 ? '1.7rem' : '1.18rem')
<div class="card">
    <div class="phead">
        <a class="pavatar" href="{{ $person['url'] }}" target="_blank" rel="noopener">
            <img
                class="mini"
                src="{{ $person['avatar'] }}"
                onerror="this.onerror=null;this.src='https://github.com/{{ $person['login'] }}.png'"
                width="{{ $size }}"
                height="{{ $size }}"
                alt="{{ $person['login'] }}"
                style="width: {{ $size }}px; height: {{ $size }}px; box-shadow: 0 0 0 2px var(--surface), 0 0 0 4px rgba(120, 43, 241, 0.45)"
            />
        </a>
        <div style="flex: 1; min-width: 0">
            <a
                class="name"
                href="{{ $person['url'] }}"
                target="_blank"
                rel="noopener"
                style="font-size: {{ $ns }}; display: block"
                >{{ '@' . $person['login'] }}</a
            >
            <div class="handle">{{ $person['total'] }} {{ $person['total'] === 1 ? 'interação' : 'interações' }}</div>
        </div>
        @if ($rank)
            <span class="total-pill">#{{ $rank }}</span>
        @endif
    </div>
    <x-portal::retro.composition-bar :person="$person" :max-total="$maxTotal" />
    @if ($compact)
        <x-portal::retro.activity-icons :person="$person" />
    @else
        <x-portal::retro.activity-chips :person="$person" />
    @endif
</div>
