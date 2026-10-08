@extends('adminlte::page')

@section('title', 'Membri')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Membri</h1>
        <div>
            <a href="{{ route('admin.membri.import') }}" class="btn btn-outline-primary">
                <i class="fas fa-file-excel"></i> Importa da Excel
            </a>
            <a href="{{ route('admin.membri.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nuovo membro
            </a>
        </div>
    </div>
@stop

@section('content')
    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <form method="GET" class="form-row align-items-end mb-3">
        <div class="col-md-4 mb-2">
            <label class="mb-1 text-muted small">Cerca (nome, CF, email)</label>
            <input type="text" name="q" value="{{ $filters['q'] }}" class="form-control" placeholder="Cerca…">
        </div>
        <div class="col-md-3 mb-2">
            <label class="mb-1 text-muted small">Ruolo</label>
            <select name="ruolo" class="form-control">
                <option value="">Tutte</option>
                @foreach (\App\Models\Member::RUOLI as $value => $label)
                    <option value="{{ $value }}" @selected($filters['ruolo'] === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3 mb-2">
            <label class="mb-1 text-muted small">Presenza</label>
            <select name="presenza" class="form-control">
                <option value="">Attivi</option>
                <option value="richieste" @selected($filters['presenza'] === 'richieste')>Richiesta di disattivazione</option>
                <option value="disabilitati" @selected($filters['presenza'] === 'disabilitati')>Disabilitati</option>
                <option value="rimossi" @selected($filters['presenza'] === 'rimossi')>Rimossi</option>
                <option value="tutti" @selected($filters['presenza'] === 'tutti')>Tutti</option>
            </select>
        </div>
        <div class="col-md-2 mb-2">
            <div class="btn-group btn-block">
                <button type="submit" class="btn btn-secondary">Filtra</button>
                @if ($filters['q'] !== '' || $filters['ruolo'] !== '' || $filters['presenza'] !== '')
                    <a href="{{ route('admin.membri.index') }}" class="btn btn-outline-secondary" title="Azzera filtri">
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
                        <th>Cognome Nome</th>
                        <th>Codice fiscale</th>
                        <th>Ruolo</th>
                        <th>Email</th>
                        <th>Telefono</th>
                        <th>Stato</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($members as $member)
                        <tr>
                            <td class="text-nowrap">
                                @if ($member->trashed())
                                    <button type="submit" form="ripristina-{{ $member->id }}" class="btn btn-sm btn-outline-success" data-toggle="tooltip" title="Ripristina">
                                        <i class="fas fa-undo"></i><span class="sr-only">Ripristina</span>
                                    </button>
                                    <form id="ripristina-{{ $member->id }}" method="POST" action="{{ route('admin.membri.restore', $member->id) }}" hidden>@csrf @method('PUT')</form>
                                @else
                                    <x-admin.action-button :href="route('admin.membri.edit', $member)" icon="fas fa-pen" label="Modifica" />
                                @endif
                            </td>
                            <td>{{ $member->nomeCompleto() }}</td>
                            <td><code>{{ $member->codice_fiscale }}</code></td>
                            <td>{{ $member->ruoloLabel() }}</td>
                            <td>{{ $member->email ?? '—' }}</td>
                            <td>{{ $member->telefono ?? '—' }}@if ($member->telefoni_aggiuntivi) <small class="text-muted">(+{{ count($member->telefoni_aggiuntivi) }})</small>@endif</td>
                            <td>
                                @if ($member->trashed())
                                    <span class="badge badge-danger">Rimosso</span>
                                @elseif ($member->disabilitato)
                                    <span class="badge badge-warning">Disabilitato</span>
                                @elseif ($member->richiesta_disattivazione_at)
                                    <span class="badge badge-info" data-toggle="tooltip" title="Richiesta il {{ $member->richiesta_disattivazione_at->format('d/m/Y') }}">Disattivazione richiesta</span>
                                @else
                                    <span class="badge badge-success">Attivo</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">Nessun membro.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($members->hasPages())
            <div class="card-footer">{{ $members->links() }}</div>
        @endif
    </div>
@stop
