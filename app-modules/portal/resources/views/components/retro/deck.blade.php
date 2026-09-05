@props (['stateKey' => '', 'bare' => false])
<div class="retro">
    <div class="atmo"></div>
    <svg class="grain">
        <filter id="retro-noise">
            <feTurbulence type="fractalNoise" baseFrequency="0.9" numOctaves="3" stitchTiles="stitch" />
        </filter>
        <rect width="100%" height="100%" filter="url(#retro-noise)" />
    </svg>

    <div class="logo-bg" aria-hidden="true">
        <svg viewBox="-24 -16 632 548" fill="none" preserveAspectRatio="xMidYMid meet">
            <path
                class="trace"
                d="M321.871 46.0393L142.46 225.416L107.15 190.106L107.144 190.099L107.137 190.093C102.363 185.487 98.5537 179.976 95.9328 173.881C93.3118 167.787 91.9312 161.232 91.8717 154.598C91.8122 147.964 93.0749 141.385 95.5862 135.244C98.0974 129.104 101.807 123.525 106.498 118.834C111.189 114.143 116.767 110.434 122.908 107.923C129.048 105.411 135.628 104.149 142.261 104.208C148.895 104.268 155.451 105.648 161.545 108.269C167.64 110.89 173.151 114.699 177.757 119.473L178.464 120.206L179.183 119.486L251.253 47.4526L251.969 46.7366L251.244 46.0295C222.277 17.7749 183.344 2.07155 142.88 2.3217C102.416 2.57185 63.6803 18.7554 35.065 47.3659C6.44979 75.9765 -9.74008 114.71 -9.9969 155.174C-10.2537 195.638 5.44322 234.573 33.693 263.545L33.7019 263.554L141.753 371.604L142.46 372.311L143.167 371.604L497.208 17.5631L498.208 16.5632L496.932 15.9537C476.231 6.06282 453.572 0.952379 430.63 1.00033C410.428 0.965204 390.418 4.92639 371.753 12.6559C353.087 20.3856 336.134 31.7312 321.871 46.0393Z"
            />
            <path
                class="trace"
                d="M569.434 88.4943L568.824 87.2175L567.824 88.218L430.627 225.45L395.317 190.106L394.61 189.398L393.903 190.105L321.869 262.139L321.162 262.846L321.869 263.553L357.179 298.863L213.818 442.224L213.112 442.93L213.818 443.638L285.852 515.707L286.559 516.414L287.266 515.707L539.384 263.589L538.677 262.882L539.385 263.589C561.877 241.089 576.843 212.171 582.224 180.815C587.605 149.459 583.137 117.206 569.434 88.4943Z"
            />
            <path
                class="led"
                pathLength="100"
                d="M321.871 46.0393L142.46 225.416L107.15 190.106L107.144 190.099L107.137 190.093C102.363 185.487 98.5537 179.976 95.9328 173.881C93.3118 167.787 91.9312 161.232 91.8717 154.598C91.8122 147.964 93.0749 141.385 95.5862 135.244C98.0974 129.104 101.807 123.525 106.498 118.834C111.189 114.143 116.767 110.434 122.908 107.923C129.048 105.411 135.628 104.149 142.261 104.208C148.895 104.268 155.451 105.648 161.545 108.269C167.64 110.89 173.151 114.699 177.757 119.473L178.464 120.206L179.183 119.486L251.253 47.4526L251.969 46.7366L251.244 46.0295C222.277 17.7749 183.344 2.07155 142.88 2.3217C102.416 2.57185 63.6803 18.7554 35.065 47.3659C6.44979 75.9765 -9.74008 114.71 -9.9969 155.174C-10.2537 195.638 5.44322 234.573 33.693 263.545L33.7019 263.554L141.753 371.604L142.46 372.311L143.167 371.604L497.208 17.5631L498.208 16.5632L496.932 15.9537C476.231 6.06282 453.572 0.952379 430.63 1.00033C410.428 0.965204 390.418 4.92639 371.753 12.6559C353.087 20.3856 336.134 31.7312 321.871 46.0393Z"
            />
            <path
                class="led b"
                pathLength="100"
                d="M569.434 88.4943L568.824 87.2175L567.824 88.218L430.627 225.45L395.317 190.106L394.61 189.398L393.903 190.105L321.869 262.139L321.162 262.846L321.869 263.553L357.179 298.863L213.818 442.224L213.112 442.93L213.818 443.638L285.852 515.707L286.559 516.414L287.266 515.707L539.384 263.589L538.677 262.882L539.385 263.589C561.877 241.089 576.843 212.171 582.224 180.815C587.605 149.459 583.137 117.206 569.434 88.4943Z"
            />
        </svg>
    </div>

    {{ $filters ?? '' }}

    <div
        class="deck-shell"
        wire:key="deck-{{ $stateKey }}"
        x-data="{
            active: 0,
            total: 0,
            label: '',
            slides: [],
            init() {
                this.slides = Array.from($el.querySelectorAll('.slide'));
                this.total = this.slides.length;
                this.slides.forEach((sl) =>
                    sl
                        .querySelectorAll('[data-anim]')
                        .forEach((el, k) => (el.style.animationDelay = 110 + Math.min(k, 14) * 60 + 'ms')),
                );
                this.go(0, { silent: true });
            },
            stepsOf(slide) {
                return slide ? Number(slide.dataset.steps || 0) : 0;
            },
            stepOf(slide) {
                return slide ? Number(slide.dataset.step || 0) : 0;
            },
            // Contrato genérico, não um caso especial de um slide: qualquer slide
            // pode declarar `data-steps` e marcar seus elementos com `data-reveal`.
            // O deck só decide QUANDO cada faixa entra; o que aparece é problema
            // da partial.
            setStep(slide, step) {
                if (!slide) return;

                slide.dataset.step = String(step);
                slide.querySelectorAll('[data-reveal]').forEach((el) => {
                    el.classList.toggle('shown', Number(el.dataset.reveal) <= step);
                });
            },
            // A seta só troca de slide depois que o slide atual gastou os passos
            // dele. Sem isso, a revelação da tag precisaria de uma tecla própria e
            // quem apresenta pularia o momento inteiro com um toque distraído.
            forward() {
                const slide = this.slides[this.active];
                const steps = this.stepsOf(slide);

                if (steps && this.stepOf(slide) < steps - 1) {
                    this.setStep(slide, this.stepOf(slide) + 1);
                    return;
                }

                this.go(this.active + 1);
            },
            back() {
                const slide = this.slides[this.active];

                if (this.stepsOf(slide) && this.stepOf(slide) > 0) {
                    this.setStep(slide, this.stepOf(slide) - 1);
                    return;
                }

                this.go(this.active - 1);
            },
            // `silent` marca o movimento que veio de FORA (o builder mandando o deck
            // para um slide). Sem isso o anúncio de volta reabriria o mesmo caminho
            // e as duas pontas ficariam se empurrando.
            go(i, { silent = false } = {}) {
                const previous = this.active;

                this.active = Math.max(0, Math.min(i, this.total - 1));
                this.slides.forEach((s, k) => {
                    s.classList.toggle('active', k === this.active);
                    s.classList.toggle('past', k < this.active);
                    if (k === this.active) s.scrollTop = 0;
                });
                this.label = this.slides[this.active] ? this.slides[this.active].dataset.label : '';

                // Um slide com passos começa fechado quando se entra por ele
                // avançando, e ABERTO quando se chega de volta ou de fora (o
                // builder mandando o preview para cá): quem está editando a lista
                // quer ver as pessoas, não a tela de suspense.
                const current = this.slides[this.active];
                const steps = this.stepsOf(current);

                if (steps) {
                    this.setStep(current, silent || this.active < previous ? steps - 1 : 0);
                }

                if (!silent && this.active !== previous) {
                    // Borbulha até o host: no portal ninguém escuta, no builder é o
                    // que mantém estrutura e inspector acompanhando o deck.
                    $dispatch('retro-moved', { index: this.active });
                }
            },
            // Método, não expressão inline: o Alpine compila atributo como
            // `__self.result = <expressão>`, e um bloco multi-linha ali é
            // SyntaxError silencioso — o teclado inteiro morre.
            keyboard($event) {
                // Embutido no builder, o deck divide o teclado com o inspector:
                // setas dentro de um campo movem o cursor, não o deck.
                if ($event.target.closest('input, textarea, select, [contenteditable]')) return;

                if ($event.key === 'ArrowRight') {
                    this.forward();
                    $event.preventDefault();
                } else if ($event.key === 'ArrowLeft') {
                    this.back();
                    $event.preventDefault();
                }
            },
        }"
        {{-- Navegação por fora do deck: o Deck Builder aponta o preview para o
             slide que o operador acabou de selecionar na coluna de estrutura. --}}
        x-on:retro-goto.window="go($event.detail.index, { silent: true })"
        @keydown.window="keyboard($event)"
    >
        @unless ($bare)
            <div class="progress">
                <div class="bar" :style="'width: ' + (total ? ((active + 1) / total) * 100 : 0) + '%'"></div>
            </div>
            @if (filled($filters ?? null))
                <button type="button" class="filter-fab" @click="$dispatch('retro-open-filters')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3" /></svg>
                    Recorte
                </button>
            @endif
        @endunless

        <div class="deck">{{ $slot }}</div>

        @unless ($bare)
            <nav class="navbar">
                <div class="counter">
                    <b x-text="String(active + 1).padStart(2, '0')"></b> /
                    <span x-text="String(total).padStart(2, '0')"></span>
                    <span class="slabel">· <span x-text="label"></span></span>
                </div>
                <div class="dots">
                    <template x-for="i in total" :key="i">
                        <button
                            type="button"
                            class="dot"
                            :class="active === i - 1 ? 'on' : ''"
                            @click="go(i - 1)"
                        ></button>
                    </template>
                </div>
                <div class="navactions">
                    <button type="button" class="navbtn" @click="back()" :disabled="active === 0 && ! stepOf(slides[active])">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6" /></svg>
                    </button>
                    <button type="button" class="navbtn" @click="forward()" :disabled="active >= total - 1 && stepOf(slides[active]) >= stepsOf(slides[active]) - 1">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6" /></svg>
                    </button>
                </div>
            </nav>
        @endunless
    </div>
</div>
