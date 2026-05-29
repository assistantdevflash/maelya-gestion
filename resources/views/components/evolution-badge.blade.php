@props([
    'pct'   => 0,
    'title' => null,
])

@php
    $p = (float) $pct;
    $isUp = $p > 0;
    $isFlat = $p == 0;
    $classes = $isFlat
        ? 'bg-gray-100 text-gray-500'
        : ($isUp ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700');
    $sign = $isUp ? '+' : '';
@endphp

<span @if($title) title="{{ $title }}" @endif
      class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded-full text-[10px] font-semibold {{ $classes }}">
    @if($isUp)
        <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 12 12"><path d="M6 2l4 6H2z"/></svg>
    @elseif(!$isFlat)
        <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 12 12"><path d="M6 10L2 4h8z"/></svg>
    @else
        <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 12 12"><rect x="2" y="5" width="8" height="2" rx="1"/></svg>
    @endif
    {{ $sign }}{{ rtrim(rtrim(number_format($p, 1, '.', ''), '0'), '.') }}%
</span>
