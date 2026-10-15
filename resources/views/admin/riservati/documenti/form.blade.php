@extends('adminlte::page')

@php $editing = $document->exists; @endphp

@section('title', $editing ? 'Modifica documento riservato' : 'Nuovo documento riservato')

@section('content_header')
    <h1>{{ $editing ? 'Modifica documento riservato' : 'Nuovo documento riservato' }}</h1>
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

    <form method="POST" action="{{ $editing ? route('admin.documenti-riservati.update', $document) : route('admin.documenti-riservati.store') }}" enctype="multipart/form-data">
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

                        <div class="form-group mb-0">
                            <label>File</label>
                            @if ($editing && $document->file_path)
                                <p class="mb-1">
                                    <a href="{{ $document->downloadUrl }}" target="_blank" rel="noopener">
                                        <i class="fas {{ $document->fa_icon }}"></i> {{ $document->original_filename ?? $document->download_name }}
                                    </a>
                                    <span class="text-muted">({{ $document->humanSize ?? '—' }})</span>
                                </p>
                            @endif
                            <input type="file" name="file" class="form-control-file" accept=".pdf,.jpg,.jpeg,.png,.webp" {{ $editing ? '' : 'required' }}>
                            <small class="text-muted">
                                Solo PDF e immagini (JPG, PNG, WebP), massimo 20 MB: si aprono sempre in anteprima sul sito.{{ $editing ? ' Carica un nuovo file solo per sostituire quello attuale.' : '' }}
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <div class="form-group">
                            <label>Categoria</label>
                            <select name="document_category_id" class="form-control" required>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" @selected((int) old('document_category_id', $document->document_category_id ?? $categories->first()?->id) === $category->id)>
                                        {{ $category->label() }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">
                                Decide chi lo vede. Si gestiscono da <a href="{{ route('admin.categorie-riservate.index') }}">Categorie riservate</a>.
                            </small>
                        </div>

                        <div class="form-group">
                            <label>Sottocategoria <small class="text-muted">(facoltativa)</small></label>
                            <input name="sottocategoria" list="sottocategorie" maxlength="100" class="form-control"
                                   value="{{ old('sottocategoria', $document->sottocategoria) }}">
                            <datalist id="sottocategorie">
                                @foreach ($sottocategorie as $sottocategoria)
                                    <option value="{{ $sottocategoria }}">
                                @endforeach
                            </datalist>
                            <small class="text-muted">Solo per raggruppare i documenti nella pagina dei soci (es. BLSD, Primo soccorso).</small>
                        </div>

                        <div class="custom-control custom-switch">
                            <input type="hidden" name="published" value="0">
                            <input type="checkbox" class="custom-control-input" id="published" name="published" value="1"
                                   @checked(old('published', $document->published ?? true))>
                            <label class="custom-control-label" for="published">Pubblicato</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">{{ $editing ? 'Salva' : 'Carica documento' }}</button>
                <a href="{{ route('admin.documenti-riservati.index') }}" class="btn btn-link">Annulla</a>
            </div>
        </div>
    </form>
@stop
