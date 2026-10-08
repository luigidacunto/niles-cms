@props(['url' => null, 'title' => ''])

@php
    $url = $url ?: url()->current();
    $u = urlencode($url);
    $t = rawurlencode($title);
@endphp

{{-- Condivisione social con soli link statici: nessuno SDK, nessun cookie di terze parti, nessun
     consenso richiesto. --}}
<div class="mt-12 pt-6 border-t border-gray-200 flex flex-wrap items-center gap-x-4 gap-y-3"
     x-data="{ copied: false }">
    <span class="text-sm font-semibold uppercase tracking-wide text-gray-500">Condividi</span>

    <a href="https://www.facebook.com/sharer/sharer.php?u={{ $u }}" target="_blank" rel="noopener"
       aria-label="Condividi su Facebook" class="text-gray-500 hover:text-[#cc0000]">
        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M22 12.06C22 6.5 17.52 2 12 2S2 6.5 2 12.06c0 5 3.66 9.15 8.44 9.94v-7.03H7.9v-2.91h2.54V9.85c0-2.51 1.49-3.9 3.77-3.9 1.09 0 2.24.2 2.24.2v2.46h-1.26c-1.24 0-1.63.77-1.63 1.56v1.87h2.78l-.44 2.91h-2.34V22c4.78-.79 8.44-4.94 8.44-9.94Z"/></svg>
    </a>

    <a href="https://api.whatsapp.com/send?text={{ $t }}%20{{ $u }}" target="_blank" rel="noopener"
       aria-label="Condividi su WhatsApp" class="text-gray-500 hover:text-[#cc0000]">
        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2 22l5.25-1.38a9.9 9.9 0 0 0 4.79 1.22h.01c5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.82 9.82 0 0 0 12.04 2Zm0 1.67c2.2 0 4.27.86 5.83 2.42a8.19 8.19 0 0 1 2.42 5.83c0 4.54-3.7 8.24-8.25 8.24a8.2 8.2 0 0 1-4.19-1.15l-.3-.18-3.11.82.83-3.04-.2-.31a8.2 8.2 0 0 1-1.26-4.38c0-4.54 3.7-8.24 8.24-8.24Zm-2.79 4.4c-.13 0-.35.05-.53.25-.18.2-.7.68-.7 1.66 0 .98.72 1.93.82 2.06.1.13 1.4 2.13 3.38 2.99 1.66.72 2 .58 2.36.54.36-.03 1.16-.47 1.32-.93.16-.46.16-.85.12-.93-.05-.08-.18-.13-.38-.23-.2-.1-1.16-.57-1.34-.64-.18-.06-.31-.1-.44.1-.13.2-.5.64-.62.77-.11.13-.23.15-.42.05-.2-.1-.83-.31-1.58-.98-.58-.52-.98-1.16-1.09-1.36-.11-.2-.01-.3.09-.4.09-.09.2-.23.29-.35.1-.11.13-.2.2-.33.06-.13.03-.25-.02-.35-.05-.1-.44-1.08-.62-1.48-.16-.38-.32-.33-.44-.34l-.37-.01Z"/></svg>
    </a>

    <a href="https://t.me/share/url?url={{ $u }}&text={{ $t }}" target="_blank" rel="noopener"
       aria-label="Condividi su Telegram" class="text-gray-500 hover:text-[#cc0000]">
        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M9.78 18.65l.28-4.23 7.68-6.92c.34-.31-.07-.46-.52-.19L7.74 13.3 3.64 12c-.88-.25-.89-.86.2-1.3l15.97-6.16c.73-.33 1.43.18 1.15 1.3l-2.72 12.81c-.19.91-.74 1.13-1.5.71l-4.14-3.05-1.99 1.93c-.23.23-.42.42-.83.42Z"/></svg>
    </a>

    <a href="https://twitter.com/intent/tweet?url={{ $u }}&text={{ $t }}" target="_blank" rel="noopener"
       aria-label="Condividi su X" class="text-gray-500 hover:text-[#cc0000]">
        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M18.24 2.25h3.31l-7.23 8.26 8.5 11.24h-6.66l-5.21-6.82-5.97 6.82H1.9l7.73-8.84L1.48 2.25h6.83l4.71 6.23 5.22-6.23Zm-1.16 17.52h1.83L7.01 4.13H5.05l12.03 15.64Z"/></svg>
    </a>

    <a href="mailto:?subject={{ rawurlencode($title) }}&body={{ $u }}"
       aria-label="Condividi via email" class="text-gray-500 hover:text-[#cc0000]">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
    </a>

    <button type="button" aria-label="Copia il link"
            @click="navigator.clipboard?.writeText(@js($url)); copied = true; setTimeout(() => copied = false, 2000)"
            class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-[#cc0000]">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 010 5.656l-3 3a4 4 0 01-5.656-5.656l1.5-1.5m6.656-6.656l1.5-1.5a4 4 0 115.656 5.656l-3 3a4 4 0 01-5.656 0"/></svg>
        <span x-text="copied ? 'Link copiato' : 'Copia link'"></span>
    </button>
</div>
