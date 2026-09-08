@props (['pr', 'hero' => false])
@php ($stateColor = ['merged' => 'var(--st-merged)', 'open' => 'var(--st-open)', 'closed' => 'var(--st-closed)'][ $pr['state'] ?? '' ] ?? 'var(--st-open)')
@php ($stateLabel = ['merged' => 'merged', 'open' => 'aberto', 'closed' => 'fechado'][ $pr['state'] ?? '' ] ?? 'aberto')
<a class="tpr {{ $hero ? 'is-hero' : '' }}" @if ($pr['url']) href="{{ $pr['url'] }}" target="_blank" rel="noopener" @endif>
    <span class="stdot" style="background: {{ $stateColor }}"></span>
    <span class="rn">#{{ $pr['num'] }}</span>
    <span style="flex: 1; min-width: 0">
        <span class="d">{{
            $pr['title'] !== ''
                ? $pr['title']
                : 'PR #' . $pr['num']
        }}</span>
        <span style="display: flex; flex-wrap: wrap; gap: 8px; align-items: center; margin-top: 7px">
            <span class="by">{{ '@' . $pr['author_login'] }}</span>
            @if ($hero)
                <span class="st-label" style="color: {{ $stateColor }}">{{ $stateLabel }}</span>
                <x-portal::retro.badges :additions="$pr['additions']" :deletions="$pr['deletions']" :files="$pr['changed_files'] > 0 ? $pr['changed_files'] : null" />
            @else
                <x-portal::retro.badges :additions="$pr['additions']" :deletions="$pr['deletions']" />
            @endif
        </span>
    </span>
</a>
