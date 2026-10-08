@extends('adminlte::page')

@php
    $editing = $page->exists;
    $isAdmin = Auth::guard('admin')->user()->role === 'admin';
    $isSystem = $editing && $page->system;
    // Link alla sezione dedicata che gestisce il contenuto della pagina di sistema (se esiste).
    $systemSectionUrl = match ($page->slug ?? null) {
        'trasparenza' => Route::has('admin.trasparenza-documenti.index') ? route('admin.trasparenza-documenti.index') : null,
        'struttura-organizzativa' => Route::has('admin.struttura-organizzativa.edit') ? route('admin.struttura-organizzativa.edit') : null,
        default => null,
    };
@endphp

@section('title', $editing ? 'Modifica pagina' : 'Nuova pagina')

@section('content_header')
    <h1>{{ $editing ? 'Modifica pagina' : 'Nuova pagina' }}</h1>
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

    @if ($isSystem)
        <div class="alert alert-info">
            <i class="fas fa-lock"></i> <strong>Sezione di sistema.</strong>
            Slug, presenza nel menu e template sono fissi, e non si elimina.
            Puoi modificare il <em>testo introduttivo</em> e l'<em>ordine</em> nel menu; per disattivare
            l'intera sezione togli la spunta a "Sezione attiva".
            @if ($systemSectionUrl)
                Il contenuto vero e proprio si gestisce da
                <a href="{{ $systemSectionUrl }}">la sezione dedicata</a>.
            @endif
        </div>
    @endif

    <form method="POST" action="{{ $editing ? route('admin.pages.update', $page) : route('admin.pages.store') }}">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <div class="form-group">
                            <label>Titolo</label>
                            <input name="title" value="{{ old('title', $page->title) }}" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label>Slug</label>
                            @if ($isSystem)
                                <input value="{{ $page->slug }}" class="form-control" disabled>
                                <small class="text-muted">Fisso per una sezione di sistema.</small>
                            @elseif ($isAdmin)
                                <input name="slug" value="{{ old('slug', $page->slug) }}" class="form-control">
                                <small class="text-muted">Lascia vuoto per generarlo dal titolo.</small>
                            @else
                                <input value="{{ $page->slug ?? 'si genera automaticamente dal titolo' }}" class="form-control" disabled>
                                <small class="text-muted">Generato automaticamente dal titolo, non modificabile.</small>
                            @endif
                        </div>

                        @if ($isSystem && $page->category_id)
                            <div class="form-group">
                                <label>Notizie collegate</label>
                                <input value="{{ $page->category?->name }}" class="form-control" disabled>
                                <small class="text-muted">
                                    Sotto il testo compaiono in automatico le ultime notizie di questa
                                    categoria, con un link all'archivio completo. Legame fisso di sistema.
                                </small>
                            </div>
                        @endif

                        <div class="form-group">
                            <label>Estratto <small class="text-muted">(breve descrizione, usata ad es. nei riquadri/elenchi che riprendono questa pagina)</small></label>
                            <textarea name="excerpt" id="excerpt" class="form-control" rows="2" maxlength="160">{{ old('excerpt', $page->excerpt) }}</textarea>
                            <small class="text-muted"><span id="excerpt-count">0</span>/160 caratteri.</small>
                        </div>

                        <div class="form-group">
                            <label>Testo</label>
                            <textarea name="body" id="body" class="form-control" rows="14">{{ old('body', $page->body) }}</textarea>
                            <small class="text-muted">
                                Per inserire un video YouTube/Vimeo, una mappa Google o un modulo Google:
                                col pulsante <code>&lt;/&gt;</code> incolla il codice "embed" (l'<code>&lt;iframe&gt;</code>).
                                Altri script/moduli esterni vengono rimossi per sicurezza.
                            </small>
                        </div>

                        @if ($isAdmin)
                            <div class="form-group">
                                <label class="text-danger">Codice embed (HTML / JavaScript) <small>(solo admin)</small></label>
                                <textarea name="embed_html" class="form-control text-monospace" rows="6"
                                          placeholder="Es. lo snippet di GoFundMe / PayPal / Satispay">{{ old('embed_html', $page->embed_html) }}</textarea>
                                <small class="text-danger">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    Inserito nella pagina pubblica <strong>così com'è, senza alcun filtro di sicurezza</strong>.
                                    Incolla solo codice preso da piattaforme fidate (donazioni). Compare sotto il testo.
                                </small>
                            </div>
                        @endif
                    </div>
                </div>

                @include('admin.partials.attachments-card', ['model' => $page])
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <div class="form-group">
                            <label>Ordine</label>
                            <input type="number" name="order" min="0" value="{{ old('order', $page->order ?? 0) }}" class="form-control">
                            <small class="text-muted">
                                Posizione nel menu tra le pagine dello stesso gruppo (0 = prima). Se il
                                numero è già occupato, le altre scalano in automatico.
                            </small>
                        </div>

                        @if ($isAdmin && ! $isSystem)
                            <hr>
                            <h6 class="text-muted">Struttura del sito <small>(solo admin)</small></h6>

                            <div class="form-group">
                                <label>Pagina genitore</label>
                                <select name="parent_id" class="form-control">
                                    <option value="">— Nessuna (pagina di primo livello) —</option>
                                    @foreach ($parents as $parent)
                                        <option value="{{ $parent->id }}" @selected(old('parent_id', $page->parent_id) == $parent->id)>
                                            {{ $parent->title }}
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Decide dove finisce questa pagina nell'albero (breadcrumb ed eventuale sottomenu).</small>
                            </div>

                            <div class="custom-control custom-switch mb-3">
                                <input type="hidden" name="in_menu" value="0">
                                <input type="checkbox" class="custom-control-input" id="in_menu" name="in_menu" value="1"
                                       @checked(old('in_menu', $page->in_menu))>
                                <label class="custom-control-label" for="in_menu">Mostra nel menu di navigazione</label>
                            </div>

                            <div class="form-group">
                                <label>Template <small class="text-muted">(tecnico)</small></label>
                                <input name="template" value="{{ old('template', $page->template) }}" class="form-control">
                                <small class="text-muted">
                                    Chiave di una vista scritta a mano nel codice (es. mappa, elenco news di una
                                    categoria) da includere sotto il testo di questa pagina — vuoto per una pagina
                                    normale. Non tocca il contenuto testuale sopra, va coordinato con lo sviluppo.
                                </small>
                            </div>
                            <hr>
                        @endif

                        <div class="custom-control custom-switch mb-3">
                            <input type="hidden" name="published" value="0">
                            <input type="checkbox" class="custom-control-input" id="published" name="published" value="1"
                                   @checked(old('published', $page->published ?? true))>
                            <label class="custom-control-label" for="published">{{ $isSystem ? 'Sezione attiva' : 'Pubblicata' }}</label>
                            @if ($isSystem)
                                <small class="d-block text-muted">Se spenta: sparisce dal menu e la sua pagina pubblica dà "non trovata".</small>
                            @endif
                        </div>

                        <button type="submit" class="btn btn-primary btn-block">{{ $editing ? 'Salva' : 'Crea pagina' }}</button>
                        <a href="{{ route('admin.pages.index') }}" class="btn btn-link btn-block">Annulla</a>
                    </div>
                </div>
            </div>
        </div>
    </form>
@stop

@section('js')
    @include('admin.partials.wysiwyg')
@stop
