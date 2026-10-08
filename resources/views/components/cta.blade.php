@props(['href' => null])

@php
    $classes = 'inline-block bg-[#cc0000] text-white text-sm font-medium px-5 py-2 rounded hover:bg-[#a30000] disabled:opacity-50';
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button type="button" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
@endif
