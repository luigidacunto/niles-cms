@props(['action', 'confirm', 'method' => 'DELETE'])

@if (trim($slot) === '')
    {{-- Unica azione pericolosa: bottone cestino rosso inline --}}
    <form method="POST" action="{{ $action }}" class="d-inline" onsubmit="return confirm(@js($confirm));">
        @csrf
        @method($method)
        <button type="submit" class="btn btn-sm btn-outline-danger" data-toggle="tooltip" title="Elimina">
            <i class="fas fa-trash"></i><span class="sr-only">Elimina</span>
        </button>
    </form>
@else
    {{-- Due o più azioni pericolose: menu hamburger rosso a tendina --}}
    <div class="dropdown d-inline">
        <button type="button" class="btn btn-sm btn-danger" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Azioni">
            <i class="fas fa-bars"></i><span class="sr-only">Azioni</span>
        </button>
        <div class="dropdown-menu dropdown-menu-right">
            <button type="submit" form="danger-del-{{ md5($action) }}" class="dropdown-item text-danger">
                <i class="fas fa-trash fa-fw"></i> Elimina
            </button>
            {{ $slot }}
        </div>
    </div>
    <form id="danger-del-{{ md5($action) }}" method="POST" action="{{ $action }}" onsubmit="return confirm(@js($confirm));">
        @csrf
        @method($method)
    </form>
@endif
