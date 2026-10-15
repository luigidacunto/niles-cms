@extends('adminlte::page')

@section('title', 'Registro download')

@section('content_header')
    <h1>Registro download</h1>
@stop

@section('content')
    <p class="text-muted">
        Apertura dell'anteprima e download dei documenti riservati, per socio. Conservati {{ \App\Models\DocumentoAccesso::RETENTION_MONTHS }} mesi
        (poi cancellati automaticamente). Sola lettura. Il salvataggio fatto dal visualizzatore PDF del browser non è tracciabile:
        sono registrati l'apertura dell'anteprima e il pulsante «Scarica».
    </p>

    <form method="GET" class="form-row align-items-end mb-3">
        <div class="col-md-5 mb-2">
            <label class="mb-1 text-muted small">Cerca (documento, socio, IP)</label>
            <input type="text" name="q" value="{{ $filters['q'] }}" class="form-control" placeholder="Cerca…">
        </div>
        <div class="col-md-3 mb-2">
            <label class="mb-1 text-muted small">Evento</label>
            <select name="event" class="form-control">
                <option value="">Tutti</option>
                <option value="anteprima" @selected($filters['event'] === 'anteprima')>Anteprime</option>
                <option value="download" @selected($filters['event'] === 'download')>Download</option>
            </select>
        </div>
        <div class="col-md-2 mb-2">
            <div class="btn-group btn-block">
                <button type="submit" class="btn btn-secondary">Filtra</button>
                @if ($filters['q'] !== '' || $filters['event'] !== '')
                    <a href="{{ route('admin.registro-download') }}" class="btn btn-outline-secondary" title="Azzera filtri"><i class="fas fa-times"></i></a>
                @endif
            </div>
        </div>
    </form>

    <div class="card">
        <div class="card-body table-responsive p-0">
            <table class="table table-hover">
                <thead>
                    <tr><th>Data e ora</th><th>Socio</th><th>Documento</th><th>Evento</th><th>IP</th></tr>
                </thead>
                <tbody>
                    @forelse ($log as $row)
                        <tr>
                            <td class="text-nowrap">{{ $row->created_at->format('d/m/Y H:i:s') }}</td>
                            <td>{{ $row->member_label }}</td>
                            <td>{{ $row->document_title }}</td>
                            <td>
                                @if ($row->event === 'download')
                                    <span class="badge badge-warning">Download</span>
                                @else
                                    <span class="badge badge-info">Anteprima</span>
                                @endif
                            </td>
                            <td><code>{{ $row->ip ?? '—' }}</code></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">Nessun accesso ai documenti registrato.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($log->hasPages())
            <div class="card-footer">{{ $log->links() }}</div>
        @endif
    </div>
@stop
