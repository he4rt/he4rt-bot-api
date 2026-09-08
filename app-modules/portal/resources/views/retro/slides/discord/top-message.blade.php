<section class="slide" data-label="Destaque">
    <div class="slide-inner">
        <span class="sec-tag" data-anim>O momento</span>
        <h2 class="sec" data-anim>As mensagens mais reagidas</h2>
        <div style="display: flex; flex-direction: column; gap: 14px; margin-top: 22px">
            @foreach ($messages as $message)
                <div class="card" data-anim>
                    <div style="color: var(--text); font-size: 1.05rem; line-height: 1.5">{{ $message['content'] }}</div>
                    <div style="display: flex; gap: 10px; align-items: center; margin-top: 12px">
                        <span class="by mono" style="color: var(--faint); font-size: 0.8rem">{{ '@'.$message['author'] }}</span>
                        <span class="bdg neu" style="margin-left: auto"
                            >{{ number_format($message['reactions'], 0, ',', '.') }} reações</span
                        >
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
