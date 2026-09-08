@php ($stateColor = fn($state) => [ 'merged' => 'var(--st-merged)', 'open' => 'var(--st-open)', 'closed' => 'var(--st-closed)' ][$state ?? ''] ?? 'var(--st-open)')
<section class="slide" data-label="Destaques">
    <div class="slide-inner">
        <span class="sec-tag" data-anim>Os maiores</span>
        <h2 class="sec" data-anim>Destaques do período</h2>
        <p class="sec-sub" data-anim>Os PRs de maior impacto no código — onde mais linhas mudaram. Metadados direto da GitHub API.</p>
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 14px; margin-top: 22px">
            @foreach ($highlights as $pr)
                <a
                    class="card"
                    @if ($pr['url']) href="{{ $pr['url'] }}" target="_blank" rel="noopener" @endif
                    data-anim
                    style="text-decoration: none; display: block"
                >
                    <div style="display: flex; align-items: center; gap: 9px">
                        <span class="display" style="font-size: 1.8rem; color: var(--brand-soft); line-height: 1"
                            >#{{ $pr['num'] }}</span
                        >
                        <span class="stdot" style="margin: 0; background: {{ $stateColor($pr['state']) }}"></span>
                        <span
                            class="mono"
                            style="
                                font-size: 0.66rem;
                                text-transform: uppercase;
                                letter-spacing: 0.12em;
                                color: var(--faint);
                                margin-left: auto;
                            "
                            >{{ str($pr['repo'])->afterLast('/') }}</span
                        >
                    </div>
                    <div style="color: var(--text); font-size: 1rem; line-height: 1.4; margin: 12px 0 10px">
                        {{
                            $pr['title'] !== ''
                                ? $pr['title']
                                : 'PR #' . $pr['num']
                        }}
                    </div>
                    <div style="display: flex; flex-wrap: wrap; gap: 8px; align-items: center">
                        <span
                            class="by mono"
                            style="color: var(--faint); font-size: 0.8rem"
                            >{{ '@' . $pr['author_login'] }}</span
                        >
                        <x-portal::retro.badges
                            :additions="$pr['additions']"
                            :deletions="$pr['deletions']"
                            :files="$pr['changed_files']"
                        />
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</section>
