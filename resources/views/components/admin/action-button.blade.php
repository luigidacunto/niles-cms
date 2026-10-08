@props(['href', 'icon', 'label', 'variant' => 'outline-secondary', 'target' => null])

<a href="{{ $href }}" @if ($target) target="{{ $target }}" @endif class="btn btn-sm btn-{{ $variant }}" data-toggle="tooltip" title="{{ $label }}">
    <i class="{{ $icon }}"></i><span class="sr-only">{{ $label }}</span>
</a>

@once
    @push('js')
        <script>$(function () { $('[data-toggle="tooltip"]').tooltip(); });</script>
    @endpush
@endonce
