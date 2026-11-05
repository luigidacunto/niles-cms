{{-- Icona «i» con tooltip per le note esplicative dei form (convenzione: niente testo piccolo sotto i campi). Testo semplice, niente link. --}}
<span class="aiuto-icona" tabindex="0" role="img" aria-label="Informazione" data-toggle="tooltip" data-placement="top" title="{{ trim(preg_replace('/\s+/', ' ', strip_tags($slot))) }}"><i class="fas fa-info-circle"></i></span>

@once
    @push('js')
        <script>$(function () { $('.aiuto-icona').tooltip({ customClass: 'aiuto-tooltip' }); });</script>
    @endpush
@endonce
