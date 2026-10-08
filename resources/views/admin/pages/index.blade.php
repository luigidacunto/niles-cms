@extends('adminlte::page')

@section('title', 'Pagine')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Pagine</h1>
        @if (Auth::guard('admin')->user()->hasPermission('pages', 'write'))
            <a href="{{ route('admin.pages.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nuova pagina
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
            <label class="mb-1 text-muted small">Cerca (titolo o slug)</label>
            <input type="text" name="q" value="{{ $filters['q'] }}" class="form-control" placeholder="Cerca…">
        </div>
        <div class="col-md-3 mb-2">
            <label class="mb-1 text-muted small">Stato</label>
            <select name="stato" class="form-control">
                <option value="">Tutti</option>
                <option value="pubblicate" @selected($filters['stato'] === 'pubblicate')>Solo pubblicate</option>
                <option value="bozze" @selected($filters['stato'] === 'bozze')>Solo bozze</option>
            </select>
        </div>
        <div class="col-md-3 mb-2">
            <label class="mb-1 text-muted small">Menu</label>
            <select name="menu" class="form-control">
                <option value="">Tutte</option>
                <option value="si" @selected($filters['menu'] === 'si')>Solo nel menu</option>
                <option value="no" @selected($filters['menu'] === 'no')>Solo fuori dal menu</option>
            </select>
        </div>
        <div class="col-md-2 mb-2">
            <div class="btn-group btn-block">
                <button type="submit" class="btn btn-secondary">Filtra</button>
                @if ($filtering)
                    <a href="{{ route('admin.pages.index') }}" class="btn btn-outline-secondary" title="Azzera filtri">
                        <i class="fas fa-times"></i>
                    </a>
                @endif
            </div>
        </div>
    </form>

    @if ($filtering)
        <p class="text-muted small">Elenco filtrato (piatto). Togli i filtri per vedere l'albero delle pagine con la parentela.</p>
    @endif

    <div class="card">
        <div class="card-body table-responsive p-0">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th class="text-nowrap" style="width:1%"></th>
                        <th>Titolo @unless ($filtering)<small class="text-muted font-weight-normal">(indentata secondo la parentela)</small>@endunless</th>
                        <th>Slug</th>
                        <th>Ordine</th>
                        <th>Nel menu</th>
                        <th>Pubblicata</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($pages as $page)
                        @php($canWrite = Auth::guard('admin')->user()->hasPermission('pages', 'write'))
                        <tr>
                            <td class="text-nowrap">
                                @if ($canWrite)
                                    <x-admin.action-button :href="route('admin.pages.edit', $page)" icon="fas fa-pen" label="Modifica" />
                                @else
                                    <x-admin.action-button :href="route('admin.pages.show', $page)" icon="fas fa-eye" label="Dettaglio" />
                                @endif
                                <x-admin.action-button :href="route('pages.show', $page)" target="_blank" icon="fas fa-external-link-alt" label="Anteprima" />
                            </td>
                            <td>
                                <span style="display:inline-block; width: {{ ($page->depth ?? 0) * 24 }}px;"></span>
                                @if (($page->depth ?? 0) > 0)
                                    <i class="fas fa-angle-right text-muted"></i>
                                @endif
                                {{ $page->title }}
                                @if ($page->system)
                                    <span class="badge badge-light border ml-1" title="Sezione di sistema: non eliminabile, struttura fissa">
                                        <i class="fas fa-lock"></i> Sistema
                                    </span>
                                @endif
                            </td>
                            <td><code>{{ $page->slug }}</code></td>
                            <td>{{ $page->order }}</td>
                            <td>
                                @if ($page->in_menu)
                                    <span class="badge badge-info">Sì</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $page->published ? 'badge-success' : 'badge-secondary' }}">
                                    {{ $page->published ? 'Sì' : 'Bozza' }}
                                </span>
                            </td>
                            <td class="text-right">
                                @if ($canWrite && ! $page->system)
                                    <x-admin.danger-actions :action="route('admin.pages.destroy', $page)"
                                        confirm="Eliminare questa pagina? Le eventuali sotto-pagine restano ma perdono il genitore." />
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">Nessuna pagina trovata.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@stop
