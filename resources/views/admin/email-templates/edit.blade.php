@extends('adminlte::page')

@section('title', 'Template email: '.$def['label'])

@section('css')
    <link rel="stylesheet" href="{{ asset('vendor/summernote/summernote-bs4.min.css') }}">
@stop

@section('content_header')
    <h1>{{ $def['label'] }} <small class="text-muted">{{ $tipo }}</small></h1>
@stop

@section('content')
    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <p class="text-muted">{{ $def['descrizione'] }}</p>

    <div class="card mb-4">
        <div class="card-header"><strong>Segnaposto disponibili</strong> <span class="text-muted">(sostituiti al momento dell'invio; vanno scritti con le graffe)</span></div>
        <div class="card-body">
            <p class="mb-2"><strong>Di questa email</strong></p>
            <ul class="mb-3">
                @foreach ($def['segnaposto'] as $chiave => $descrizione)
                    <li><code>{{ $chiave }}</code> — {{ $descrizione }}</li>
                @endforeach
            </ul>
            <p class="mb-1"><strong>Dati del comitato</strong> (da <a href="{{ route('admin.comitato.edit') }}">Dati del comitato</a>)</p>
            @foreach ($segnapostoComitato as $s)<code>{{ $s }}</code> @endforeach
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><strong>Testo predefinito</strong> <span class="text-muted">(di sistema, sola lettura — usato quando non c'è una personalizzazione)</span></div>
        <div class="card-body">
            @if ($default)
                <p class="font-weight-bold">Oggetto: {{ $default->oggetto }}</p>
                <div class="border rounded p-3 bg-light" style="max-height:300px; overflow:auto;">{!! $default->corpo !!}</div>
            @else
                <p class="text-muted mb-0">Nessun testo predefinito per questo tipo di email.</p>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-header"><strong>Personalizzazione</strong></div>
        <form method="POST" action="{{ route('admin.email-templates.update', $tipo) }}">
            @csrf
            @method('PUT')
            <div class="card-body">
                <div class="form-group">
                    <label>Oggetto</label>
                    <input name="oggetto" value="{{ old('oggetto', $override->oggetto ?? ($default->oggetto ?? '')) }}" class="form-control" required>
                    <small class="text-muted">Testo semplice, senza HTML.</small>
                </div>
                <div id="avviso-ripristino" class="alert alert-warning d-none">
                    <strong>Testo predefinito caricato nell'editor, non ancora salvato.</strong>
                    Premi <em>«Salva personalizzazione»</em> per confermare, oppure
                    <a href="{{ route('admin.email-templates.edit', $tipo) }}" class="alert-link">annulla</a> per tornare a com'era.
                </div>

                <div class="form-group">
                    <label>Testo dell'email</label>
                    <textarea name="corpo" id="corpo" rows="18" class="form-control" required>{{ old('corpo', $override->corpo ?? ($default->corpo ?? '')) }}</textarea>
                    <small class="text-muted">Scrivi come in un documento: grassetto, elenchi, link. Per inserire un dato automatico (nome, corso, data…) usa il menu <strong>«Inserisci dato»</strong> nella barra: viene sostituito al momento dell'invio. Il pulsante <code>&lt;/&gt;</code> mostra l'HTML per chi lo conosce.
                    @unless ($override) Il testo parte da quello predefinito: modificalo e salva per creare la tua versione. @endunless</small>
                </div>
            </div>
            <div class="card-footer d-flex justify-content-between">
                <div>
                    <button type="submit" class="btn btn-primary">Salva personalizzazione</button>
                    @if ($testoSorgente)
                        <button type="button" id="ripristina-predefinito" class="btn btn-outline-warning"
                            title="Carica nell'editor il testo originale. Non salva nulla finché non premi «Salva personalizzazione».">Ripristina il testo predefinito</button>
                    @endif
                    <a href="{{ route('admin.email-templates.anteprima', $tipo) }}" target="_blank" class="btn btn-outline-secondary">Anteprima (testo in uso)</a>
                    <a href="{{ route('admin.email-templates.index') }}" class="btn btn-link">Torna all'elenco</a>
                </div>
                @if ($override)
                    <button type="submit" form="rimuovi-override" class="btn btn-outline-danger"
                        onclick="return confirm('Rimuovere la personalizzazione? Tornerà in uso il testo predefinito.');">Rimuovi personalizzazione</button>
                @endif
            </div>
        </form>
        @if ($override)
            <form id="rimuovi-override" method="POST" action="{{ route('admin.email-templates.destroy', $tipo) }}">@csrf @method('DELETE')</form>
        @endif
    </div>
@stop

@section('js')
    <script src="{{ asset('vendor/summernote/summernote-bs4.min.js') }}"></script>
    <script src="{{ asset('vendor/summernote/summernote-it-IT.min.js') }}"></script>
    <script>
        $(function () {
            // Dati automatici inseribili: segnaposto di questa email + dati del comitato. Il link alla pagina personale
            // si inserisce come link già pronto (il campo "indirizzo" della finestra link non è adatto a un segnaposto).
            var dati = @json(array_merge(array_keys($def['segnaposto']), $segnapostoComitato));

            var buttonDati = function (context) {
                var ui = $.summernote.ui;
                return ui.buttonGroup([
                    ui.button({
                        className: 'dropdown-toggle',
                        contents: 'Inserisci dato <span class="caret"></span>',
                        tooltip: 'Inserisci un dato che viene riempito automaticamente quando l\'email parte',
                        data: { toggle: 'dropdown' },
                    }),
                    ui.dropdown({
                        className: 'dropdown-style',
                        items: dati,
                        click: function (event) {
                            event.preventDefault();
                            var valore = $(event.target).closest('a').data('value');
                            if (valore === '{link_preferenze}') {
                                context.invoke('editor.pasteHTML', '<a href="{link_preferenze}">Apri la mia pagina</a>');
                            } else {
                                context.invoke('editor.insertText', valore);
                            }
                        },
                    }),
                ]).render();
            };

            // «Ripristina»: carica nell'editor oggetto e testo originali (sorgente del seeder). Non salva: serve «Salva
            // personalizzazione», oppure «annulla» (ricarica la pagina) per lasciare tutto com'era.
            var originale = @json($testoSorgente);
            $('#ripristina-predefinito').on('click', function () {
                if (! originale) { return; }
                if (! confirm('Sostituire oggetto e testo che stai modificando con quelli predefiniti? Non verrà salvato nulla finché non premi «Salva personalizzazione».')) { return; }
                $('input[name="oggetto"]').val(originale.oggetto);
                $('#corpo').summernote('code', originale.corpo);
                $('#avviso-ripristino').removeClass('d-none')[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
            });

            $('#corpo').summernote({
                lang: 'it-IT',
                height: 420,
                toolbar: [
                    ['style', ['style']],
                    ['font', ['bold', 'italic', 'underline', 'clear']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['insert', ['link', 'table', 'hr']],
                    ['dati', ['dati']],
                    ['view', ['codeview']],
                ],
                buttons: { dati: buttonDati },
                // Un indirizzo che è un segnaposto ({...}) resta com'è: senza questo, Summernote gli antepone "http://".
                callbacks: {
                    onCreateLink: function (link) {
                        if (/^\{[a-z_]+\}$/.test(link)) { return link; }
                        return /^([A-Za-z][A-Za-z0-9+.-]*:|\/\/|#)/.test(link) ? link : 'http://' + link;
                    },
                },
            });
        });
    </script>
@stop
