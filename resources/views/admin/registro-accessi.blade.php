@extends('adminlte::page')

@section('title', 'Registro accessi')

@section('content_header')
    <h1>Registro accessi</h1>
@stop

@section('content')
    <p class="text-muted">Accessi di amministratori e soci, conservati {{ \App\Models\LoginAudit::RETENTION_MONTHS }} mesi (poi cancellati automaticamente). Sola lettura.</p>

    <form method="GET" class="form-row align-items-end mb-3">
        <div class="col-md-4 mb-2">
            <label class="mb-1 text-muted small">Cerca (nome, email, IP)</label>
            <input type="text" name="q" value="{{ $filters['q'] }}" class="form-control" placeholder="Cerca…">
        </div>
        <div class="col-md-3 mb-2">
            <label class="mb-1 text-muted small">Utente</label>
            <select name="guard" class="form-control">
                <option value="">Tutti</option>
                <option value="admin" @selected($filters['guard'] === 'admin')>Amministratori/editor</option>
                <option value="member" @selected($filters['guard'] === 'member')>Soci</option>
            </select>
        </div>
        <div class="col-md-3 mb-2">
            <label class="mb-1 text-muted small">Esito</label>
            <select name="event" class="form-control">
                <option value="">Tutti</option>
                <option value="success" @selected($filters['event'] === 'success')>Riusciti</option>
                <option value="failed" @selected($filters['event'] === 'failed')>Falliti</option>
            </select>
        </div>
        <div class="col-md-2 mb-2">
            <div class="btn-group btn-block">
                <button type="submit" class="btn btn-secondary">Filtra</button>
                @if ($filters['q'] !== '' || $filters['guard'] !== '' || $filters['event'] !== '')
                    <a href="{{ route('admin.registro-accessi') }}" class="btn btn-outline-secondary" title="Azzera filtri"><i class="fas fa-times"></i></a>
                @endif
            </div>
        </div>
    </form>

    <div class="card">
        <div class="card-body table-responsive p-0">
            <table class="table table-hover">
                <thead>
                    <tr><th>Data e ora</th><th>Utente</th><th>Chi</th><th>Esito</th><th>IP</th></tr>
                </thead>
                <tbody>
                    @forelse ($log as $row)
                        <tr>
                            <td class="text-nowrap">{{ $row->created_at->format('d/m/Y H:i:s') }}</td>
                            <td>{{ $row->guard === 'admin' ? 'Amministratore/editor' : 'Socio' }}</td>
                            <td>{{ $row->label ?? '—' }}</td>
                            <td>
                                @if ($row->event === 'success')
                                    <span class="badge badge-success">Riuscito</span>
                                @else
                                    <span class="badge badge-danger">Fallito</span>
                                @endif
                            </td>
                            <td><code>{{ $row->ip ?? '—' }}</code></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">Nessun accesso registrato.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($log->hasPages())
            <div class="card-footer">{{ $log->links() }}</div>
        @endif
    </div>
@stop
