@extends('adminlte::page')

@section('title', 'Libreria documenti')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Libreria documenti</h1>
        @if (Auth::guard('admin')->user()->hasPermission('documents', 'write'))
            <a href="{{ route('admin.documents.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nuovo documento
            </a>
        @endif
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
                        <option value="none" @selected(request('categoria') === 'none')>Senza categoria</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected(request('categoria') == $category->id)>
                                {{ $category->name }}@unless ($category->selectable) (riservata) @endunless
                            </option>
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
                        <th>Formato</th>
                        <th>Pubblicato</th>
                        <th>Link</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @php($canWrite = Auth::guard('admin')->user()->hasPermission('documents', 'write'))
                    @forelse ($documents as $document)
                        <tr>
                            <td class="text-nowrap">
                                @if ($canWrite)
                                    <x-admin.action-button :href="route('admin.documents.edit', $document)" icon="fas fa-pen" label="Modifica" />
                                @else
                                    <x-admin.action-button :href="route('admin.documents.show', $document)" icon="fas fa-eye" label="Dettaglio" />
                                @endif
                            </td>
                            <td>
                                <a href="{{ $document->downloadUrl }}" target="_blank" rel="noopener">{{ $document->title }}</a>
                            </td>
                            <td>
                                <span class="badge badge-secondary">{{ $document->category?->name ?? '—' }}</span>
                                @unless ($document->category?->selectable) <span class="badge badge-light border">riservata</span> @endunless
                            </td>
                            <td class="text-uppercase small">{{ $document->extension }}</td>
                            <td>
                                <span class="badge {{ $document->published ? 'badge-success' : 'badge-secondary' }}">
                                    {{ $document->published ? 'Sì' : 'No' }}
                                </span>
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-copy="{{ $document->downloadUrl }}"
                                        data-toggle="tooltip" title="Copia URL pubblico">
                                    <i class="fas fa-link"></i>
                                </button>
                            </td>
                            <td class="text-right">
                                @if ($canWrite)
                                    <x-admin.danger-actions :action="route('admin.documents.destroy', $document)"
                                        confirm="Eliminare definitivamente questo documento dalla libreria? Il file verrà rimosso e i link esistenti non funzioneranno più." />
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">Nessun documento in libreria.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{ $documents->links('pagination::bootstrap-4') }}
@stop

@section('js')
    <script>
        $(function () {
            $('[data-copy]').on('click', function () {
                navigator.clipboard.writeText(this.dataset.copy);
                $(this).tooltip('hide').attr('data-original-title', 'Copiato!').tooltip('show');
            });
        });
    </script>
@stop
