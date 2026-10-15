@extends('adminlte::page')

@php $editing = $item->exists; @endphp

@section('title', $editing ? 'Modifica comunicazione' : 'Nuova comunicazione')

@section('content_header')
    <h1>{{ $editing ? 'Modifica comunicazione' : 'Nuova comunicazione' }}</h1>
@stop

@section('css')
    <link rel="stylesheet" href="{{ asset('vendor/summernote/summernote-bs4.min.css') }}">
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

    <form method="POST" action="{{ $editing ? route('admin.comunicazioni-soci.update', $item) : route('admin.comunicazioni-soci.store') }}" enctype="multipart/form-data">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <div class="form-group">
                            <label>Titolo</label>
                            <input name="title" value="{{ old('title', $item->title) }}" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label>Estratto <small class="text-muted">(facoltativo)</small></label>
                            <textarea name="excerpt" id="excerpt" class="form-control" rows="2" maxlength="500">{{ old('excerpt', $item->excerpt) }}</textarea>
                            <small class="text-muted">Breve riassunto mostrato nell'elenco delle comunicazioni.</small>
                        </div>

                        <div class="form-group">
                            <label>Testo</label>
                            <textarea name="body" id="body" class="form-control" rows="14">{{ old('body', $item->body) }}</textarea>
                            <small class="text-muted">
                                ⚠️ Le immagini inserite nel testo non sono protette da accesso diretto: per il materiale
                                riservato usa <strong>copertina</strong>, <strong>galleria</strong> e <strong>allegati</strong>,
                                che sono realmente privati.
                            </small>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><h3 class="card-title">Galleria / foto</h3></div>
                    <div class="card-body">
                        @if ($item->images->isNotEmpty())
                            <div class="row">
                                @foreach ($item->images as $image)
                                    <div class="col-md-6 mb-3">
                                        <div class="border rounded p-2">
                                            <img src="{{ route('admin.comunicazioni-soci.immagine', $image) }}" class="img-fluid rounded mb-2" alt="">
                                            <input type="text" name="images[{{ $image->id }}][caption]"
                                                   value="{{ old("images.{$image->id}.caption", $image->caption) }}"
                                                   class="form-control form-control-sm mb-2" placeholder="Didascalia (facoltativa)">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <label class="mb-0 mr-2 text-muted small">Ordine
                                                    <input type="number" min="0" name="images[{{ $image->id }}][order]"
                                                           value="{{ old("images.{$image->id}.order", $image->order) }}"
                                                           class="form-control form-control-sm d-inline-block" style="width:5rem">
                                                </label>
                                                <label class="mb-0 text-danger small">
                                                    <input type="checkbox" name="images[{{ $image->id }}][delete]" value="1"> elimina
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <div class="form-group mb-0">
                            <label>Aggiungi foto</label>
                            <input type="file" name="gallery_images[]" multiple accept=".jpg,.jpeg,.png" class="form-control-file">
                            <small class="text-muted">
                                JPG o PNG, più file insieme, ridimensionate automaticamente. Sono immagini riservate: si vedono solo
                                da soci loggati.
                            </small>
                        </div>
                    </div>
                </div>

                @include('admin.partials.attachments-card', [
                    'model' => $item,
                    'libreria' => [route('admin.documenti-riservati.index'), 'Documenti riservati'],
                ])
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <div class="form-group">
                            <label>Copertina</label>
                            @if ($item->cover_image)
                                <img src="{{ route('admin.comunicazioni-soci.copertina', $item) }}" class="img-fluid rounded mb-2" alt="">
                                <div class="custom-control custom-checkbox mb-2">
                                    <input type="checkbox" class="custom-control-input" id="remove_cover" name="remove_cover" value="1">
                                    <label class="custom-control-label text-danger" for="remove_cover">Rimuovi copertina</label>
                                </div>
                            @endif
                            <input type="file" name="cover_image" accept=".jpg,.jpeg,.png" class="form-control-file">
                            <small class="text-muted">JPG o PNG, facoltativa. Immagine riservata ai soci loggati.</small>
                        </div>

                        <div class="form-group">
                            <label>Data pubblicazione</label>
                            <input type="datetime-local" name="published_at" class="form-control"
                                   value="{{ old('published_at', optional($item->published_at ?? now())->format('Y-m-d\TH:i')) }}">
                        </div>

                        <div class="custom-control custom-switch mb-3">
                            <input type="hidden" name="published" value="0">
                            <input type="checkbox" class="custom-control-input" id="published" name="published" value="1"
                                   @checked(old('published', $item->published ?? true))>
                            <label class="custom-control-label" for="published">Pubblicata</label>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block">{{ $editing ? 'Salva' : 'Crea comunicazione' }}</button>
                        <a href="{{ route('admin.comunicazioni-soci.index') }}" class="btn btn-link btn-block">Annulla</a>
                    </div>
                </div>
            </div>
        </div>
    </form>
@stop

@section('js')
    @include('admin.partials.wysiwyg')
@stop
