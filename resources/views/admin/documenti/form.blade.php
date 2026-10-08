@extends('adminlte::page')

@php $editing = $document->exists; @endphp

@section('title', $editing ? 'Modifica documento' : 'Nuovo documento')

@section('content_header')
    <h1>{{ $editing ? 'Modifica documento' : 'Nuovo documento in libreria' }}</h1>
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

    <form method="POST" action="{{ $editing ? route('admin.documents.update', $document) : route('admin.documents.store') }}" enctype="multipart/form-data">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <div class="form-group">
                            <label>Titolo</label>
                            <input name="title" value="{{ old('title', $document->title) }}" class="form-control" required>
                            <small class="text-muted">Usato anche come nome del file quando viene scaricato.</small>
                        </div>

                        <div class="form-group">
                            <label>Descrizione <small class="text-muted">(opzionale)</small></label>
                            <textarea name="description" class="form-control" rows="3">{{ old('description', $document->description) }}</textarea>
                        </div>

                        <div class="form-group">
                            <label>File</label>
                            @if ($editing && $document->file_path)
                                <p class="mb-1">
                                    <a href="{{ $document->downloadUrl }}" target="_blank" rel="noopener">
                                        <i class="fas fa-file"></i> {{ $document->original_filename ?? $document->download_name }}
                                    </a>
                                    <span class="text-muted">({{ $document->humanSize ?? '—' }})</span>
                                </p>
                            @endif
                            <input type="file" name="file" class="form-control-file" {{ $editing ? '' : 'required' }}>
                            <small class="text-muted">
                                PDF, Word, Excel, PowerPoint, OpenDocument, testo. Massimo 20 MB.{{ $editing ? ' Carica un nuovo file solo per sostituire quello attuale.' : '' }}
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <div class="form-group">
                            <label>Categoria <small class="text-muted">(facoltativa)</small></label>
                            <select name="document_category_id" class="form-control">
                                <option value="">— nessuna —</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" @selected((int) old('document_category_id', $document->document_category_id) === $category->id)>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">Le categorie si gestiscono da <a href="{{ route('admin.document-categories.index') }}">Categorie documenti</a>.</small>
                        </div>

                        <div class="custom-control custom-switch">
                            <input type="hidden" name="published" value="0">
                            <input type="checkbox" class="custom-control-input" id="published" name="published" value="1"
                                   @checked(old('published', $document->published ?? true))>
                            <label class="custom-control-label" for="published">Pubblicato (link raggiungibile)</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">{{ $editing ? 'Salva' : 'Carica documento' }}</button>
                <a href="{{ route('admin.documents.index') }}" class="btn btn-link">Annulla</a>
            </div>
        </div>
    </form>
@stop
