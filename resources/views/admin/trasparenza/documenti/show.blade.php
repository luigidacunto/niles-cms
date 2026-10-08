@extends('adminlte::page')

@section('title', 'Dettaglio documento')

@section('content_header')
    <h1>{{ $document->title }}</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    @if ($document->description)
                        <div class="form-group">
                            <label class="text-muted">Descrizione</label>
                            <p>{{ $document->description }}</p>
                        </div>
                    @endif

                    <div class="form-group">
                        <label class="text-muted">File</label>
                        <p>
                            <a href="{{ $document->downloadUrl }}" target="_blank" rel="noopener">
                                <i class="fas fa-file-pdf"></i> {{ $document->original_filename ?? 'documento.pdf' }}
                            </a>
                            <span class="text-muted">({{ $document->humanSize ?? '—' }})</span>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <div class="form-group">
                        <label class="text-muted">Area</label>
                        <p><span class="badge badge-secondary">{{ $document->category?->name ?? '—' }}</span></p>
                    </div>
                    <div class="form-group">
                        <label class="text-muted">Tipologia</label>
                        <p>{{ $document->type ?? '—' }}</p>
                    </div>
                    <div class="form-group">
                        <label class="text-muted">Stato</label>
                        <p>
                            <span class="badge {{ $document->published ? 'badge-success' : 'badge-secondary' }}">
                                {{ $document->published ? 'Pubblicato' : 'Non pubblicato' }}
                            </span>
                        </p>
                    </div>
                    <div class="form-group">
                        <label class="text-muted">Data pubblicazione</label>
                        <p>{{ $document->published_at?->format('d/m/Y') ?? '—' }}</p>
                    </div>

                    <a href="{{ route('admin.trasparenza-documenti.index') }}" class="btn btn-link btn-block">Torna all'elenco</a>
                </div>
            </div>
        </div>
    </div>
@stop
