@props(['center' => false])

<div {{ $attributes->merge(['class' => 'mb-6 '.($center ? 'text-center' : '')]) }}>
    <h2 class="inline-block bg-white px-4 text-xl font-semibold text-gray-800 relative -mb-3">
        {{ $slot }}
    </h2>
    <div class="border-b border-gray-300"></div>
</div>
