@extends('adminlte::page')

@php $editing = $document->exists; @endphp

@section('title', $editing ? 'Modifica documento' : 'Nuovo documento')

@section('content_header')
    <h1>{{ $editing ? 'Modifica documento' : 'Nuovo documento' }}</h1>
@stop

@section('content')
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ $editing ? route('admin.trasparenza-documenti.update', $document) : route('admin.trasparenza-documenti.store') }}" enctype="multipart/form-data">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <div class="form-group">
                            <label>Titolo</label>
                            <input name="title" value="{{ old('title', $document->title) }}" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label>Descrizione <small class="text-muted">(opzionale)</small></label>
                            <textarea name="description" class="form-control" rows="3">{{ old('description', $document->description) }}</textarea>
                        </div>

                        <div class="form-group">
                            <label>File PDF</label>
                            @if ($editing && $document->file_path)
                                <p class="mb-1">
                                    <a href="{{ $document->downloadUrl }}" target="_blank" rel="noopener">
                                        <i class="fas fa-file-pdf"></i> {{ $document->original_filename ?? 'documento.pdf' }}
                                    </a>
                                    <span class="text-muted">({{ $document->humanSize ?? '—' }})</span>
                                </p>
                            @endif
                            <input type="file" name="file" accept="application/pdf" class="form-control-file" {{ $editing ? '' : 'required' }}>
                            <small class="text-muted">
                                Solo PDF, massimo 20 MB.{{ $editing ? ' Carica un nuovo file solo se vuoi sostituire quello attuale.' : '' }}
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <div class="form-group">
                            <label>Area</label>
                            <select name="document_category_id" class="form-control" required>
                                <option value="">— scegli —</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" @selected((int) old('document_category_id', $document->document_category_id) === $category->id)>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Tipologia <small class="text-muted">(etichetta libera)</small></label>
                            <input name="type" value="{{ old('type', $document->type) }}" class="form-control" list="document-types"
                                   placeholder="es. 5x1000, Verbali…">
                            <datalist id="document-types">
                                @foreach ($types as $type)
                                    <option value="{{ $type }}"></option>
                                @endforeach
                            </datalist>
                            <small class="text-muted">
                                Raggruppa i documenti dentro l'area. Digitando, ti vengono suggerite quelle già usate.
                            </small>
                        </div>

                        <div class="form-group">
                            <label>Data di pubblicazione</label>
                            <input type="date" name="published_at" class="form-control"
                                   value="{{ old('published_at', optional($document->published_at)->format('Y-m-d') ?? now()->format('Y-m-d')) }}">
                            <small class="text-muted">Usata per l'ordinamento per anno sulla pagina pubblica.</small>
                        </div>

                        <div class="custom-control custom-switch">
                            <input type="hidden" name="published" value="0">
                            <input type="checkbox" class="custom-control-input" id="published" name="published" value="1"
                                   @checked(old('published', $document->published ?? true))>
                            <label class="custom-control-label" for="published">Pubblicato (visibile su /trasparenza)</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">{{ $editing ? 'Salva' : 'Carica documento' }}</button>
                <a href="{{ route('admin.trasparenza-documenti.index') }}" class="btn btn-link">Annulla</a>
            </div>
        </div>
    </form>
@stop
