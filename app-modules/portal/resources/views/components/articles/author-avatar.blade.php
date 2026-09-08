@props(['name', 'avatar' => null, 'size' => 'size-5'])

{{-- Só quem vinculou a identidade tem avatar. Para o resto, a inicial do handle
     ocupa o mesmo espaço e mantém a linha de assinatura alinhada. --}}
@if ($avatar)
    <img
        src="{{ $avatar }}"
        alt=""
        loading="lazy"
        decoding="async"
        width="90"
        height="90"
        {{ $attributes->class([$size, 'shrink-0 rounded-full']) }}
    />
@else
    <span
        {{ $attributes->class([$size, 'bg-primary/20 text-text-high flex shrink-0 items-center justify-center rounded-full font-mono text-[0.6em] uppercase']) }}
        aria-hidden="true"
    >{{ Str::substr($name, 0, 1) }}</span>
@endif
