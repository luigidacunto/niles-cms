{{-- Stato di un consenso: null | richiesto | confermato | revocato --}}
@switch($stato)
    @case('confermato') <span class="badge badge-success">Confermato</span> @break
    @case('richiesto') <span class="badge badge-warning">Richiesto</span> @break
    @case('revocato') <span class="badge badge-secondary">Revocato</span> @break
    @default <span class="text-muted">—</span>
@endswitch
