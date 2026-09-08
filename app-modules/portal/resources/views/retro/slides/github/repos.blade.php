<section class="slide" data-label="{{ $repo['name'] }}">
    <div class="slide-inner">
        {{-- Trilho de identidade à esquerda, PRs em duas colunas à direita: a
             lista inteira de PRs numa coluna só deixava metade da tela vazia. --}}
        <div class="repo-layout">
            <aside class="repo-rail" data-anim>
                <span
                    class="mono"
                    style="font-size: 0.74rem; letter-spacing: 0.2em; text-transform: uppercase; color: var(--brand-soft)"
                    >Repositório {{ $index }}</span
                >
                <div style="display: flex; gap: 16px; align-items: center; margin-top: 14px">
                    <div class="repo-ic">
                        <svg viewBox="0 0 24 24" fill="none" stroke="var(--brand-soft)" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" />
                            <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="repo-name">{{ $repo['name'] }}</h2>
                        <div class="handle">{{ $repo['full_name'] }}</div>
                    </div>
                </div>
                <div style="display: flex; flex-wrap: wrap; gap: 9px; margin-top: 18px">
                    <span class="bdg neu">{{ $repo['metrics']['prs'] }} PRs</span>
                    <span class="bdg neu">{{ number_format($repo['metrics']['changed_files'], 0, ',', '.') }} arquivos</span>
                    @if ($repo['metrics']['additions'] > 0)
                        <span class="bdg add">+{{ number_format($repo['metrics']['additions'], 0, ',', '.') }}</span>
                    @endif
                    @if ($repo['metrics']['deletions'] > 0)
                        <span class="bdg del">−{{ number_format($repo['metrics']['deletions'], 0, ',', '.') }}</span>
                    @endif
                </div>
                @php
                    // Snapshot antigo não traz métricas por pessoa: todo mundo cai
                    // no grupo de presença e o trilho degrada para a pilha de
                    // avatares que existia antes.
                    $authors = array_values(array_filter($repo['people'], fn (array $p): bool => ($p['prs'] ?? 0) > 0));
                    $present = array_values(array_filter($repo['people'], fn (array $p): bool => ($p['prs'] ?? 0) === 0));
                    $maxChurn = max([1, ...array_map(fn (array $p): int => $p['additions'] + $p['deletions'], $authors)]);
                @endphp

                @if ($authors !== [])
                    <div class="rp-list">
                        <span class="rp-title">Quem mexeu</span>
                        @foreach (array_slice($authors, 0, 5) as $p)
                            @php
                                $churn = max(1, $p['additions'] + $p['deletions']);
                            @endphp
                            <div class="rp-row">
                                <div class="rp-id">
                                    <img
                                        class="mini"
                                        src="{{ $p['avatar'] }}"
                                        onerror="this.onerror=null;this.src='https://github.com/{{ $p['login'] }}.png'"
                                        width="24"
                                        height="24"
                                        alt="{{ $p['login'] }}"
                                        style="width: 24px; height: 24px"
                                    />
                                    <span class="rp-login">{{ '@' . $p['login'] }}</span>
                                    <span class="rp-prs">{{ $p['prs'] }} @choice('PR|PRs', $p['prs'])</span>
                                </div>
                                <div class="rp-meter">
                                    <span class="rp-bar" style="width: {{ round(($churn / $maxChurn) * 100, 1) }}%">
                                        <span class="a" style="width: {{ round(($p['additions'] / $churn) * 100, 1) }}%"></span>
                                        <span class="d" style="width: {{ round(($p['deletions'] / $churn) * 100, 1) }}%"></span>
                                    </span>
                                    <span class="rp-churn">+{{ number_format($p['additions'], 0, ',', '.') }}</span>
                                    @if ($p['deletions'] > 0)
                                        <span class="rp-churn is-del">−{{ number_format($p['deletions'], 0, ',', '.') }}</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                @if ($present !== [])
                    <div class="rp-present">
                        <span class="avstack">
                            @foreach (array_slice($present, 0, 6) as $p)
                                <img
                                    class="mini"
                                    src="{{ $p['avatar'] }}"
                                    onerror="this.onerror=null;this.src='https://github.com/{{ $p['login'] }}.png'"
                                    width="28"
                                    height="28"
                                    alt="{{ $p['login'] }}"
                                    style="width: 28px; height: 28px"
                                />
                            @endforeach
                        </span>
                        @if ($authors !== [])
                            <span class="rp-present-label">na revisão e por perto</span>
                        @endif
                    </div>
                @endif
            </aside>
            @php
                // Densidade adaptativa: o grid de duas colunas existe para caber
                // 10+ PRs; com poucos ele deixa a segunda coluna manca. Repo
                // pequeno troca quantidade por detalhe — coluna única, card rico.
                $isHero = count($repo['prs']) <= 4;
            @endphp
            <div class="repo-prs {{ $isHero ? 'is-hero' : '' }}">
                @foreach ($repo['prs'] as $pr)
                    <div data-anim><x-portal::retro.pr-row :pr="$pr" :hero="$isHero" /></div>
                @endforeach
            </div>
        </div>
    </div>
</section>
