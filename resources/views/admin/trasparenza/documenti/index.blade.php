@extends('adminlte::page')

@section('title', 'Documenti Trasparenza')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Documenti Trasparenza</h1>
        @if (Auth::guard('admin')->user()->hasPermission('trasparenza', 'write'))
            <a href="{{ route('admin.trasparenza-documenti.create') }}" class="btn btn-primary">
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
        <div class="card-body table-responsive p-0">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th class="text-nowrap" style="width:1%"></th>
                        <th>Titolo</th>
                        <th>Area</th>
                        <th>Tipologia</th>
                        <th>Pubblicato</th>
                        <th>Data</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($documents as $document)
                        @php($canWrite = Auth::guard('admin')->user()->hasPermission('trasparenza', 'write'))
                        <tr>
                            <td class="text-nowrap">
                                @if ($canWrite)
                                    <x-admin.action-button :href="route('admin.trasparenza-documenti.edit', $document)" icon="fas fa-pen" label="Modifica" />
                                @else
                                    <x-admin.action-button :href="route('admin.trasparenza-documenti.show', $document)" icon="fas fa-eye" label="Dettaglio" />
                                @endif
                            </td>
                            <td>
                                <a href="{{ $document->downloadUrl }}" target="_blank" rel="noopener">{{ $document->title }}</a>
                            </td>
                            <td><span class="badge badge-secondary">{{ $document->category?->name ?? '—' }}</span></td>
                            <td>{{ $document->type ?? '—' }}</td>
                            <td>
                                <span class="badge {{ $document->published ? 'badge-success' : 'badge-secondary' }}">
                                    {{ $document->published ? 'Sì' : 'No' }}
                                </span>
                            </td>
                            <td>{{ $document->published_at?->format('d/m/Y') }}</td>
                            <td class="text-right">
                                @if ($canWrite)
                                    <x-admin.danger-actions :action="route('admin.trasparenza-documenti.destroy', $document)"
                                        confirm="Eliminare questo documento? Il file PDF verrà rimosso." />
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">Nessun documento caricato.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{ $documents->links('pagination::bootstrap-4') }}
@stop
