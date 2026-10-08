@extends('adminlte::page')

@php
    $editing = $post->exists;
    $isAdmin = Auth::guard('admin')->user()->role === 'admin';
@endphp

@section('title', $editing ? 'Modifica post' : 'Nuovo post')

@section('content_header')
    <h1>{{ $editing ? 'Modifica post' : 'Nuovo post' }}</h1>
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

    <form method="POST" action="{{ $editing ? route('admin.posts.update', $post) : route('admin.posts.store') }}" enctype="multipart/form-data">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <div class="form-group">
                            <label>Titolo</label>
                            <input name="title" value="{{ old('title', $post->title) }}" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Sottotitolo</label>
                            <input name="subtitle" value="{{ old('subtitle', $post->subtitle) }}" class="form-control">
                        </div>

                        <div class="form-group">
                            <label>Slug</label>
                            @if ($isAdmin)
                                <input name="slug" value="{{ old('slug', $post->slug) }}" class="form-control">
                                <small class="text-muted">Lascia vuoto per generarlo dal titolo.</small>
                            @else
                                <input value="{{ $post->slug ?? 'si genera automaticamente dal titolo' }}" class="form-control" disabled>
                                <small class="text-muted">Generato automaticamente dal titolo, non modificabile.</small>
                            @endif
                        </div>

                        <div class="form-group">
                            <label>Estratto</label>
                            <textarea name="excerpt" id="excerpt" class="form-control" rows="3" maxlength="160">{{ old('excerpt', $post->excerpt) }}</textarea>
                            <small class="text-muted">
                                Breve descrizione (2-3 righe) che spiega di cosa parla il post: compare nelle liste
                                (home, archivio) e nei risultati dei motori di ricerca. Non è il contenuto vero e
                                proprio, è un riassunto per far capire a colpo d'occhio se interessa aprire il post.
                                <span id="excerpt-count">0</span>/160 caratteri.
                            </small>
                        </div>

                        <div class="form-group">
                            <label>Testo</label>
                            <textarea name="body" id="body" class="form-control" rows="14">{{ old('body', $post->body) }}</textarea>
                            <small class="text-muted">
                                Per inserire un video YouTube/Vimeo, una mappa Google o un modulo Google:
                                col pulsante <code>&lt;/&gt;</code> incolla il codice "embed" (l'<code>&lt;iframe&gt;</code>).
                                Altri script/moduli esterni vengono rimossi per sicurezza.
                            </small>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><h3 class="card-title">Galleria / foto</h3></div>
                    <div class="card-body">
                        @if ($post->images->isNotEmpty())
                            <div class="row">
                                @foreach ($post->images as $image)
                                    <div class="col-md-6 mb-3">
                                        <div class="border rounded p-2">
                                            <img src="{{ $image->url }}" class="img-fluid rounded mb-2" alt="">
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
                                JPG o PNG, più file insieme. Le immagini troppo grandi vengono ridimensionate
                                automaticamente. Compaiono in una griglia in fondo alla pagina del post.
                            </small>
                        </div>
                    </div>
                </div>

                @include('admin.partials.attachments-card', ['model' => $post])
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <div class="form-group">
                            <label>Categoria</label>
                            <select name="category_id" class="form-control" required>
                                <option value="" disabled @selected(!old('category_id', $post->category_id))>— Seleziona —</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" @selected(old('category_id', $post->category_id) == $category->id)>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">Ogni post deve avere una categoria. "Notizie" è la categoria generica se non ce n'è una più specifica.</small>
                        </div>

                        <div class="form-group">
                            <label>Tag <small class="text-muted">(separati da virgola)</small></label>
                            <input name="tags" value="{{ old('tags', $post->tags->pluck('name')->implode(', ')) }}" class="form-control">
                        </div>

                        <div class="form-group">
                            <label>Copertina</label>
                            @if ($post->cover_image)
                                <img src="{{ $post->cover_url }}" class="img-fluid rounded mb-2" alt="">
                            @endif
                            <input type="file" name="cover_image" accept=".jpg,.jpeg,.png" class="form-control-file">
                            <small class="text-muted">
                                Formati accettati: JPG, PNG. Opzionale — se assente viene mostrata un'immagine generica
                                al posto della copertina. Le immagini troppo grandi vengono ridimensionate automaticamente.
                            </small>
                        </div>

                        <div class="form-group">
                            <label>Data pubblicazione</label>
                            <input type="datetime-local" name="published_at" class="form-control"
                                   value="{{ old('published_at', optional($post->published_at ?? now())->format('Y-m-d\TH:i')) }}">
                        </div>

                        <div class="custom-control custom-switch mb-3">
                            <input type="hidden" name="published" value="0">
                            <input type="checkbox" class="custom-control-input" id="published" name="published" value="1"
                                   @checked(old('published', $post->published ?? true))>
                            <label class="custom-control-label" for="published">Pubblicato</label>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block">{{ $editing ? 'Salva' : 'Crea post' }}</button>
                        <a href="{{ route('admin.posts.index') }}" class="btn btn-link btn-block">Annulla</a>
                    </div>
                </div>
            </div>
        </div>
    </form>
@stop

@section('js')
    @include('admin.partials.wysiwyg')
@stop
