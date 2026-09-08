@props (['person'])
{{--
    A anatomia do card é fixa de propósito: chips só para PRs (a parte com
    título, que merece leitura) e UMA régua de contadores para todo o resto.
    Uma linha rotulada por tipo fazia o card do #1 ter o dobro da altura do #3
    e quebrava a comparação lado a lado no trilho.

    Só blocos PHP pareados neste arquivo (e nada de diretiva PHP inline, nem
    citada em comentário): o compilador do Blade casa o primeiro abre com o
    primeiro fecha ANTES de remover comentários, e qualquer mistura engole o
    miolo do template como PHP cru.
--}}
@php
    $stateColor = fn ($state) => ['merged' => 'var(--st-merged)', 'open' => 'var(--st-open)', 'closed' => 'var(--st-closed)'][$state ?? ''] ?? 'var(--st-open)';
@endphp
<div class="acts">
    @if (count($person['pr_refs']))
        @php
            $prShown = array_slice($person['pr_refs'], 0, 3);
            $prMore = count($person['pr_refs']) - count($prShown);
        @endphp
        <div class="act act-pr" style="--c: var(--t-pr)">
            <span class="act-h">Abriu PR</span>
            <span class="act-items">
                @foreach ($prShown as $ref)
                    <a
                        class="ref"
                        @if ($ref['url']) href="{{ $ref['url'] }}" target="_blank" rel="noopener" @endif
                        title="#{{ $ref['num'] }} · {{ $ref['title'] }}"
                    >
                        <span class="stdot" style="margin: 0; background: {{ $stateColor($ref['state']) }}"></span
                        ><span class="rn">#{{ $ref['num'] }}</span><span class="rt">{{ $ref['title'] }}</span></a
                    >
                @endforeach
                @if ($prMore > 0)
                    <span class="ref more">mais {{ $prMore }}…</span>
                @endif
            </span>
        </div>
    @endif
    @php
        $stats = array_values(
            array_filter(
                [
                    ['cls' => 'act-review', 'c' => '--t-review', 'n' => $person['reviews'], 'label' => 'reviews'],
                    ['cls' => 'act-issue', 'c' => '--t-issue', 'n' => count($person['issue_refs']), 'label' => 'issues'],
                    ['cls' => 'act-comment', 'c' => '--t-comment', 'n' => $person['comments'], 'label' => 'coment.'],
                    ['cls' => 'act-review-comment', 'c' => '--t-review-comment', 'n' => $person['review_comments'], 'label' => 'em review'],
                    ['cls' => 'act-commit', 'c' => '--t-commit', 'n' => $person['commits'], 'label' => 'commits'],
                ],
                fn (array $stat): bool => $stat['n'] > 0,
            ),
        );
    @endphp
    @if (count($stats))
        <div class="cstats strip">
            @foreach ($stats as $stat)
                <span
                    class="cstat {{ $stat['cls'] }}"
                    style="--c: var({{ $stat['c'] }})"
                    title="{{ $stat['n'] }} {{ $stat['label'] }}"
                >
                    <span class="cstat-n">{{ $stat['n'] }}</span><span class="cstat-l">{{ $stat['label'] }}</span>
                </span>
            @endforeach
        </div>
    @endif
</div>
