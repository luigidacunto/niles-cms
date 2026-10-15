@extends('adminlte::page')

@section('title', 'Documenti riservati')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Documenti riservati</h1>
        <a href="{{ route('admin.documenti-riservati.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nuovo documento
        </a>
    </div>
@stop

@section('content')
    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="card">
        <div class="card-body">
            <form method="GET" class="form-row align-items-end">
                <div class="form-group col-md-4 mb-2">
                    <label class="mb-1 small text-muted">Cerca nel titolo</label>
                    <input name="q" value="{{ request('q') }}" class="form-control form-control-sm">
                </div>
                <div class="form-group col-md-4 mb-2">
                    <label class="mb-1 small text-muted">Categoria</label>
                    <select name="categoria" class="form-control form-control-sm">
                        <option value="">Tutte</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected(request('categoria') == $category->id)>{{ $category->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-2 mb-2">
                    <button class="btn btn-sm btn-outline-secondary btn-block">Filtra</button>
                </div>
            </form>
        </div>

        <div class="card-body table-responsive p-0 border-top">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th class="text-nowrap" style="width:1%"></th>
                        <th>Titolo</th>
                        <th>Categoria</th>
                        <th>Sottocategoria</th>
                        <th>Formato</th>
                        <th>Pubblicato</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($documents as $document)
                        <tr>
                            <td class="text-nowrap">
                                <x-admin.action-button :href="route('admin.documenti-riservati.edit', $document)" icon="fas fa-pen" label="Modifica" />
                            </td>
                            <td><a href="{{ $document->downloadUrl }}" target="_blank" rel="noopener">{{ $document->title }}</a></td>
                            <td><span class="badge badge-secondary">{{ $document->category->label() }}</span></td>
                            <td>{{ $document->sottocategoria ?? '—' }}</td>
                            <td class="text-uppercase small">{{ $document->extension }}</td>
                            <td>
                                <span class="badge {{ $document->published ? 'badge-success' : 'badge-secondary' }}">{{ $document->published ? 'Sì' : 'No' }}</span>
                            </td>
                            <td class="text-right">
                                <x-admin.danger-actions :action="route('admin.documenti-riservati.destroy', $document)"
                                    confirm="Eliminare definitivamente questo documento? Il file viene rimosso e sparisce anche dalle comunicazioni a cui è allegato." />
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">Nessun documento riservato.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{ $documents->links('pagination::bootstrap-4') }}
@stop
