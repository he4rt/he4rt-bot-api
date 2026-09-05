@php
    $fmt = fn(int $n): string => number_format($n, 0, ',', '.');
    $total = max(1, (int) $meta['total']);
    $days = (int) ($meta['days'] ?? 0);
    $types = collect([
        ['label' => 'reviews', 'count' => (int) $meta['reviews'], 'color' => 'var(--t-review)'],
        ['label' => 'commits', 'count' => (int) $meta['commits'], 'color' => 'var(--t-commit)'],
        ['label' => 'comentários', 'count' => (int) $meta['comments'], 'color' => 'var(--t-comment)'],
        ['label' => 'comentários de review', 'count' => (int) $meta['review_comments'], 'color' => 'var(--t-review-comment)'],
        ['label' => 'PRs', 'count' => (int) $meta['prs'], 'color' => 'var(--t-pr)'],
        ['label' => 'issues', 'count' => (int) $meta['issues'], 'color' => 'var(--t-issue)'],
    ])
        ->filter(fn(array $type): bool => $type['count'] > 0)
        ->sortByDesc('count')
        ->values();
    $busiest = max(1, (int) ($types->max('count') ?? 1));
    $prsOpen = max(0, (int) $meta['prs'] - (int) $meta['prs_merged'] - (int) $meta['prs_unmerged']);
    $top = $types->first();
@endphp

<section class="slide" data-label="Panorama">
    <div class="slide-inner">
        <span class="sec-tag" data-anim>O panorama</span>

        <div data-anim>
            <div class="pan-hero">{{ $fmt((int) $meta['total']) }}</div>
            <p class="pan-lead">
                contribuições humanas
                @if ($days > 0)
                    em <b>{{ $days }} @choice('dia|dias', $days)</b>
                @endif
                · <b>{{ $meta['people'] }} @choice('pessoa|pessoas', $meta['people'])</b>
                · <b>{{ $meta['repos'] }} @choice('repositório|repositórios', $meta['repos'])</b>
                · <span class="faint">bots ficaram de fora</span>
            </p>
        </div>

        <div class="pan-grid">
            @if ($types->isNotEmpty())
                <div data-anim>
                    <span class="pan-sec">Por tipo</span>
                    <div class="pan-rows">
                        @foreach ($types as $type)
                            <div class="pan-row">
                                <span class="lbl">{{ $type['label'] }}</span>
                                <span class="num">{{ $fmt($type['count']) }}</span>
                                <div class="pan-bar">
                                    {{-- O maior tipo ocupa o vão todo menos o espaço reservado ao rótulo de %. --}}
                                    <span
                                        class="pan-fill"
                                        style="width: calc((100% - 3.4rem) * {{ max(0.015, round($type['count'] / $busiest, 4)) }}); background: {{ $type['color'] }}"
                                    ></span>
                                    <span class="pct">{{ round(($type['count'] / $total) * 100) }}%</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($meta['prs'] > 0)
                <div data-anim>
                    <span class="pan-sec">Pull Requests</span>
                    <p class="pan-prs-total"><b>{{ $meta['prs'] }} PRs</b> no recorte</p>
                    @php
                        $states = array_values(array_filter([
                            ['count' => (int) $meta['prs_merged'], 'color' => 'var(--st-merged)'],
                            ['count' => $prsOpen, 'color' => 'var(--st-open)'],
                            ['count' => (int) $meta['prs_unmerged'], 'color' => 'var(--st-closed)'],
                        ], fn(array $state): bool => $state['count'] > 0));
                    @endphp
                    <div class="pan-stack">
                        @foreach ($states as $state)
                            <span style="width: {{ round(($state['count'] / $meta['prs']) * 100, 2) }}%; background: {{ $state['color'] }}"></span>
                        @endforeach
                    </div>
                    <p class="pan-states">
                        <span class="key"><i style="background: var(--st-merged)"></i><b>{{ $meta['prs_merged'] }}</b> merged</span>
                        <span class="key"><i style="background: var(--st-open)"></i><b>{{ $prsOpen }}</b> @choice('aberto|abertos', $prsOpen)</span>
                        <span class="key"><i style="background: var(--st-closed)"></i><b>{{ $meta['prs_unmerged'] }}</b> @choice('fechado|fechados', $meta['prs_unmerged']) sem merge</span>
                    </p>
                    <div class="pan-diff">
                        <span class="bdg add">+{{ $fmt((int) $meta['additions']) }}</span>
                        <span class="bdg del">−{{ $fmt((int) $meta['deletions']) }}</span>
                        <span class="bdg neu">{{ $fmt((int) $meta['changed_files']) }} arquivos</span>
                        <span class="faint">só metadata dos PRs</span>
                    </div>
                </div>
            @endif
        </div>

        @if ($top !== null)
            <p class="pan-insight" data-anim>
                → {{ $top['label'] }} @choice('é|são', $top['count'])
                <b>{{ round(($top['count'] / $total) * 100) }}%</b> de tudo
                ({{ $fmt($top['count']) }} de {{ $fmt((int) $meta['total']) }}
                @choice('contribuição|contribuições', $meta['total'])).
                @if ($days > 0)
                    Média de <b>{{ number_format($meta['total'] / $days, 1, ',', '.') }}</b> contribuições por dia.
                @endif
            </p>
        @endif
    </div>
</section>
