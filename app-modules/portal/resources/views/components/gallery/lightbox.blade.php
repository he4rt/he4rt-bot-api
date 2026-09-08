{{-- Overlay do lightbox. Vive dentro do x-data="galleryLightbox(...)" da página. --}}
<div
    x-show="open"
    x-cloak
    x-trap.noscroll="open"
    x-on:keydown.window.escape="close()"
    x-on:keydown.window.arrow-right="next()"
    x-on:keydown.window.arrow-left="prev()"
    x-on:touchstart.passive="touchStart($event)"
    x-on:touchend="touchEnd($event)"
    x-transition.opacity.duration.150ms
    role="dialog"
    aria-modal="true"
    aria-label="Foto ampliada"
    class="fixed inset-0 z-50 flex flex-col bg-black/95 text-white"
    style="display: none"
>
    <div class="flex items-center justify-between px-4 py-3">
        <span class="font-mono text-sm tabular-nums" x-text="(index + 1) + ' / ' + photos.length"></span>
        <button
            type="button"
            x-on:click="close()"
            class="rounded-md p-2 transition-colors hover:bg-white/10 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white"
            aria-label="Fechar"
        >
            <x-filament::icon icon="heroicon-o-x-mark" class="h-6 w-6" />
        </button>
    </div>

    <div class="relative flex min-h-0 flex-1 items-center justify-center px-4" x-on:click.self="close()">
        <button
            type="button"
            x-show="hasMany"
            x-on:click="prev()"
            class="absolute top-1/2 left-2 hidden -translate-y-1/2 rounded-full bg-black/50 p-3 transition-colors hover:bg-white/10 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white sm:block"
            aria-label="Foto anterior"
        >
            <x-filament::icon icon="heroicon-o-chevron-left" class="h-6 w-6" />
        </button>

        <img
            :src="current.l"
            :alt="current.c ?? ''"
            class="max-h-full max-w-full rounded-md object-contain select-none"
            draggable="false"
        />

        <button
            type="button"
            x-show="hasMany"
            x-on:click="next()"
            class="absolute top-1/2 right-2 hidden -translate-y-1/2 rounded-full bg-black/50 p-3 transition-colors hover:bg-white/10 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white sm:block"
            aria-label="Próxima foto"
        >
            <x-filament::icon icon="heroicon-o-chevron-right" class="h-6 w-6" />
        </button>
    </div>

    <p
        x-show="current.c"
        x-text="current.c"
        class="px-4 py-3 text-center text-sm text-white/80"
    ></p>
</div>
