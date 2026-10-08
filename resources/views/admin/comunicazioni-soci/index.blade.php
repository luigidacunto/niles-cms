@extends('adminlte::page')

@section('title', 'Comunicazioni soci')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Comunicazioni soci</h1>
        <a href="{{ route('admin.comunicazioni-soci.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nuova comunicazione
        </a>
    </div>
@stop

@section('content')
    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <p class="text-muted">Visibili solo ai soci che hanno effettuato l'accesso all'area riservata. Gli allegati sono privati.</p>

    <form method="GET" class="form-row align-items-end mb-3">
        <div class="col-md-5 mb-2">
            <label class="mb-1 text-muted small">Cerca (titolo)</label>
            <input type="text" name="q" value="{{ $filters['q'] }}" class="form-control" placeholder="Cerca…">
        </div>
        <div class="col-md-3 mb-2">
            <label class="mb-1 text-muted small">Stato</label>
            <select name="stato" class="form-control">
                <option value="">Tutti</option>
                <option value="pubblicati" @selected($filters['stato'] === 'pubblicati')>Solo pubblicate</option>
                <option value="bozze" @selected($filters['stato'] === 'bozze')>Solo bozze</option>
            </select>
        </div>
        <div class="col-md-2 mb-2">
            <div class="btn-group btn-block">
                <button type="submit" class="btn btn-secondary">Filtra</button>
                @if ($filters['q'] !== '' || $filters['stato'] !== '')
                    <a href="{{ route('admin.comunicazioni-soci.index') }}" class="btn btn-outline-secondary" title="Azzera filtri"><i class="fas fa-times"></i></a>
                @endif
            </div>
        </div>
    </form>

    <div class="card">
        <div class="card-body table-responsive p-0">
            <table class="table table-hover">
                <thead>
                    <tr><th class="text-nowrap" style="width:1%"></th><th>Titolo</th><th>Pubblicata</th><th>Data</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse ($items as $item)
                        <tr>
                            <td class="text-nowrap">
                                <x-admin.action-button :href="route('admin.comunicazioni-soci.edit', $item)" icon="fas fa-pen" label="Modifica" />
                            </td>
                            <td>{{ $item->title }}</td>
                            <td>
                                <span class="badge {{ $item->published ? 'badge-success' : 'badge-secondary' }}">{{ $item->published ? 'Sì' : 'Bozza' }}</span>
                            </td>
                            <td>{{ $item->published_at?->format('d/m/Y') }}</td>
                            <td class="text-right">
                                <x-admin.danger-actions :action="route('admin.comunicazioni-soci.destroy', $item)"
                                    confirm="Eliminare questa comunicazione e i suoi allegati?" />
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">Nessuna comunicazione.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{ $items->links('pagination::bootstrap-4') }}
@stop
