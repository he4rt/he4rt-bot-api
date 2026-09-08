{{--
    Trilho horizontal com scroll-snap, setas e arrasto com inércia no mouse.

    Só a mecânica mora aqui: o que vai dentro de cada `.pslide` é do slot. O
    carrossel de pessoas e o de eventos compartilham o mesmo trilho porque a
    física do arrasto não muda com o conteúdo — só o card muda.
--}}
<div
    {{ $attributes->class('pcarousel') }}
    x-data="{
        scrollable: false,
        atStart: true,
        atEnd: true,
        dragging: false,
        startX: 0,
        startLeft: 0,
        moved: false,
        vx: 0,
        lastX: 0,
        lastT: 0,
        raf: null,
        update() {
            const t = $refs.track;
            this.scrollable = t.scrollWidth > t.clientWidth + 2;
            this.atStart = t.scrollLeft <= 2;
            this.atEnd = t.scrollLeft + t.clientWidth >= t.scrollWidth - 2;
        },
        nudge(dir) {
            const t = $refs.track;
            const card = t.querySelector('.pslide');
            const step = card ? card.offsetWidth + 16 : t.clientWidth * 0.8;
            t.scrollBy({ left: dir * step, behavior: 'smooth' });
        },
        down(e) {
            // só mouse: no touch o scroll nativo já arrasta (com inércia do SO)
            if (e.pointerType !== 'mouse') return;
            e.preventDefault(); // mata a seleção de texto e o drag-fantasma da imagem
            cancelAnimationFrame(this.raf);
            this.dragging = true;
            this.moved = false;
            this.startX = e.clientX;
            this.startLeft = $refs.track.scrollLeft;
            this.vx = 0;
            this.lastX = e.clientX;
            this.lastT = e.timeStamp;
            $refs.track.setPointerCapture?.(e.pointerId);
        },
        move(e) {
            if (!this.dragging) return;
            const dx = e.clientX - this.startX;
            if (Math.abs(dx) > 4) this.moved = true;
            $refs.track.scrollLeft = this.startLeft - dx;
            const dt = e.timeStamp - this.lastT;
            if (dt > 0) {
                this.vx = (e.clientX - this.lastX) / dt; // px por ms
                this.lastX = e.clientX;
                this.lastT = e.timeStamp;
            }
        },
        up() {
            if (!this.dragging) return;
            this.dragging = false;
            this.glide(this.vx * 16); // px/ms → px por frame (~16ms): inércia
        },
        glide(v) {
            if (Math.abs(v) < 0.4) return; // parou
            $refs.track.scrollLeft -= v;
            this.raf = requestAnimationFrame(() => this.glide(v * 0.92)); // atrito
        },
        click(e) {
            // se houve arrasto (não clique seco), não abre o link do card
            if (this.moved) {
                e.preventDefault();
                e.stopPropagation();
                this.moved = false;
            }
        },
    }"
    x-init="
        update();
        $nextTick(() => update());
        window.addEventListener('resize', () => update());
    "
>
    <button
        type="button"
        class="pcar-arrow prev"
        :class="!scrollable || atStart ? 'hide' : ''"
        @click="nudge(-1)"
        aria-label="Anterior"
    >
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6" /></svg>
    </button>

    <div
        class="pcar-track"
        :class="{ scrollable: scrollable, dragging: dragging }"
        x-ref="track"
        @scroll.passive="update()"
        @pointerdown="down($event)"
        @pointermove="move($event)"
        @pointerup.window="up()"
        @pointercancel.window="up()"
        @click.capture="click($event)"
    >
        {{ $slot }}
    </div>

    <button
        type="button"
        class="pcar-arrow next"
        :class="!scrollable || atEnd ? 'hide' : ''"
        @click="nudge(1)"
        aria-label="Próximo"
    >
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6" /></svg>
    </button>
</div>
