@extends('adminlte::page')

@section('title', 'Corsi')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Corsi</h1>
        @if (Auth::guard('admin')->user()->hasPermission('corsi', 'write'))
            <a href="{{ route('admin.corsi.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nuovo corso
            </a>
        @endif
    </div>
@stop

@section('content')
    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <form method="GET" class="form-row align-items-end mb-3">
        <div class="col-md-4 mb-2">
            <label class="mb-1 text-muted small">Cerca (protocollo)</label>
            <input type="text" name="q" value="{{ $filters['q'] }}" class="form-control" placeholder="Cerca…">
        </div>
        <div class="col-md-3 mb-2">
            <label class="mb-1 text-muted small">Tipologia</label>
            <select name="tipologia" class="form-control">
                <option value="">Tutte</option>
                @foreach ($tipologie as $tipologia)
                    <option value="{{ $tipologia->id }}" @selected((string) $filters['tipologia'] === (string) $tipologia->id)>{{ $tipologia->nome }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3 mb-2">
            <label class="mb-1 text-muted small">Stato</label>
            <select name="stato" class="form-control">
                <option value="">Tutti</option>
                <option value="attivi" @selected($filters['stato'] === 'attivi')>Attivi</option>
                <option value="annullati" @selected($filters['stato'] === 'annullati')>Annullati</option>
                <option value="chiusi" @selected($filters['stato'] === 'chiusi')>Chiusi (gestione interna)</option>
            </select>
        </div>
        <div class="col-md-2 mb-2">
            <div class="btn-group btn-block">
                <button type="submit" class="btn btn-secondary">Filtra</button>
                @if ($filters['q'] !== '' || $filters['tipologia'] !== '' || $filters['stato'] !== '')
                    <a href="{{ route('admin.corsi.index') }}" class="btn btn-outline-secondary" title="Azzera filtri">
                        <i class="fas fa-times"></i>
                    </a>
                @endif
            </div>
        </div>
    </form>

    <div class="card">
        <div class="card-body table-responsive p-0">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th class="text-nowrap" style="width:1%"></th>
                        <th>Protocollo</th>
                        <th>Tipologia</th>
                        <th>Data</th>
                        <th>Stato</th>
                        <th>Iscritti</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($corsi as $corso)
                        @php
                            $canWrite = Auth::guard('admin')->user()->hasPermission('corsi', 'write');
                            $linkPubblico = route('corsi.iscrizione.show', $corso);
                            $iscrittiCount = $corso->iscrizioni()->attive()->count();
                        @endphp
                        <tr>
                            <td class="text-nowrap">
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-toggle="tooltip" title="Copia link pubblico"
                                    onclick="copiaLinkCorso(this, '{{ $linkPubblico }}')">
                                    <i class="fas fa-link"></i><span class="sr-only">Copia link</span>
                                </button>
                                @if ($canWrite)
                                    <x-admin.action-button :href="route('admin.corsi.edit', $corso)" icon="fas fa-pen" label="Modifica" />
                                @else
                                    <x-admin.action-button :href="route('admin.corsi.show', $corso)" icon="fas fa-eye" label="Dettaglio" />
                                @endif
                                <x-admin.action-button :href="route('admin.corsi.iscritti.index', $corso)" icon="fas fa-users" label="Iscritti" />
                            </td>
                            <td><code>{{ $corso->protocollo }}</code></td>
                            <td>{{ $corso->tipologia->nome }}</td>
                            <td>{{ $corso->data_inizio->format('d/m/Y H:i') }}</td>
                            <td>
                                @if ($corso->annullato())
                                    <span class="badge badge-danger" data-toggle="tooltip" title="{{ $corso->motivo_annullamento }}">Annullato</span>
                                @elseif ($corso->chiuso)
                                    <span class="badge badge-dark">Chiuso</span>
                                @elseif (! $corso->pubblicato)
                                    <span class="badge badge-secondary">Bozza</span>
                                @else
                                    @php($stato = $corso->statoPubblico())
                                    @if ($stato === 'concluso')
                                        <span class="badge badge-light border">Concluso</span>
                                    @elseif ($stato === 'iscrizioni_chiuse')
                                        <span class="badge badge-warning">Iscrizioni chiuse</span>
                                    @else
                                        <span class="badge badge-success">Iscrizioni aperte</span>
                                    @endif
                                @endif
                            </td>
                            <td>{{ $iscrittiCount }}</td>
                            <td class="text-right">
                                @if ($canWrite)
                                    <div class="dropdown d-inline">
                                        <button type="button" class="btn btn-sm btn-outline-secondary" data-toggle="dropdown" title="Azioni">
                                            <i class="fas fa-bars"></i><span class="sr-only">Azioni</span>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-right">
                                            @if ($corso->chiuso)
                                                <button type="submit" form="riapri-{{ $corso->id }}" class="dropdown-item">Riapri (gestione interna)</button>
                                            @else
                                                <button type="submit" form="chiudi-{{ $corso->id }}" class="dropdown-item">Chiudi (gestione interna)</button>
                                            @endif
                                            @if ($corso->annullato())
                                                @if ($corso->puoRiattivare())
                                                    <button type="submit" form="riattiva-{{ $corso->id }}" class="dropdown-item">Riattiva corso</button>
                                                @endif
                                            @else
                                                <button type="button" class="dropdown-item" onclick="annullaCorsoRiga({{ $corso->id }})">Annulla corso</button>
                                            @endif
                                            <button type="submit" form="elimina-{{ $corso->id }}" class="dropdown-item text-danger">Elimina</button>
                                        </div>
                                    </div>

                                    <form id="chiudi-{{ $corso->id }}" method="POST" action="{{ route('admin.corsi.chiudi', $corso) }}" hidden>@csrf @method('PUT')</form>
                                    <form id="riapri-{{ $corso->id }}" method="POST" action="{{ route('admin.corsi.riapri', $corso) }}" hidden>@csrf @method('PUT')</form>
                                    <form id="riattiva-{{ $corso->id }}" method="POST" action="{{ route('admin.corsi.riattiva', $corso) }}"
                                        onsubmit="return confirm('Riattivare questo corso?');" hidden>@csrf @method('PUT')</form>
                                    <form id="annulla-{{ $corso->id }}" method="POST" action="{{ route('admin.corsi.annulla', $corso) }}" hidden>
                                        @csrf @method('PUT')
                                        <input type="hidden" name="motivo" id="annulla-motivo-{{ $corso->id }}">
                                    </form>
                                    <form id="elimina-{{ $corso->id }}" method="POST" action="{{ route('admin.corsi.destroy', $corso) }}"
                                        onsubmit="return confirm('Eliminare questo corso? Possibile solo se non ha ancora iscritti.');" hidden>@csrf @method('DELETE')</form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">Nessun corso trovato.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{ $corsi->links('pagination::bootstrap-4') }}

    @push('js')
        <script>
            function copiaLinkCorso(button, url) {
                navigator.clipboard.writeText(url).then(function () {
                    const icon = button.querySelector('i');
                    icon.classList.remove('fa-link');
                    icon.classList.add('fa-check');
                    setTimeout(function () {
                        icon.classList.remove('fa-check');
                        icon.classList.add('fa-link');
                    }, 1500);
                });
            }

            function annullaCorsoRiga(id) {
                const motivo = prompt('Motivo dell\'annullamento (visibile anche nella pagina pubblica del corso):');
                if (motivo === null || motivo.trim() === '') return;
                document.getElementById('annulla-motivo-' + id).value = motivo.trim();
                document.getElementById('annulla-' + id).submit();
            }

            $(function () { $('[data-toggle="tooltip"]').tooltip(); });
        </script>
    @endpush
@stop
