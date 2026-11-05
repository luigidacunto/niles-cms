{{-- Piè di pagina del pannello (tutte le pagine adminlte::page, iniettato da AppServiceProvider): versione a sinistra, copyright a destra. --}}
<div class="d-flex justify-content-between flex-wrap small text-muted">
    <span>
        Realizzato con <a href="https://github.com/luigidacunto/niles-cms" target="_blank" rel="noopener" class="font-weight-bold">NILES</a>
        v{{ config('app.version') }}
    </span>
    <span>&copy; {{ date('Y') }} {{ \App\Models\CommitteeInfo::current()->denominazione ?: config('app.public_name') }}</span>
</div>
