@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
    <h1>Ciao, {{ Str::of($admin->name)->explode(' ')->first() }} 👋</h1>
    <p class="text-muted mb-0">Riepilogo delle attività e dei contenuti del sito.</p>
@stop

@php($canWritePosts = $admin->hasPermission('posts', 'write'))
@php($postLink = fn ($p) => $canWritePosts ? route('admin.posts.edit', $p) : route('admin.posts.show', $p))

@section('content')

    {{-- Richieste in attesa (solo se ce ne sono, solo a chi le gestisce) --}}
    @if ($daFare)
        <div class="callout callout-warning">
            <h5><i class="fas fa-bell mr-1"></i> Da fare</h5>
            <ul class="list-unstyled mb-0">
                @foreach ($daFare as $voce)
                    <li class="py-1">
                        <i class="fas {{ $voce['icona'] }} fa-fw text-muted mr-1"></i>
                        <a href="{{ $voce['url'] }}">{{ $voce['testo'] }}</a>
                        <span class="badge badge-warning ml-1">{{ $voce['numero'] }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Formazione (permesso Corsi) e pagamenti (funzione attiva + permesso) --}}
    @if ($formazione || $pagamenti)
        <h5 class="mb-2"><i class="fas fa-chalkboard-teacher mr-1"></i> Formazione</h5>
        <div class="row">
            @if ($formazione)
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-primary">
                        <div class="inner">
                            <h3>{{ $formazione['aperti'] }}</h3>
                            <p>Corsi con iscrizioni aperte</p>
                        </div>
                        <div class="icon"><i class="fas fa-door-open"></i></div>
                        <a href="{{ route('admin.corsi.index') }}" class="small-box-footer">
                            Corsi <i class="fas fa-arrow-circle-right"></i>
                        </a>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="info-box">
                        <span class="info-box-icon bg-primary"><i class="fas fa-user-check"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Iscritti ai prossimi corsi</span>
                            <span class="info-box-number">{{ $formazione['iscrittiFuturi'] }}</span>
                        </div>
                    </div>
                    <div class="info-box">
                        <span class="info-box-icon bg-info"><i class="fas fa-calendar-plus"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Iscrizioni negli ultimi 7 giorni</span>
                            <span class="info-box-number">{{ $formazione['iscrizioniSettimana'] }}</span>
                        </div>
                    </div>
                </div>
            @endif
            @if ($pagamenti)
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-warning">
                        <div class="inner">
                            <h3>{{ $pagamenti['da_fatturare'] }}</h3>
                            <p>Pagamenti da fatturare</p>
                        </div>
                        <div class="icon"><i class="fas fa-file-invoice"></i></div>
                        <a href="{{ route('admin.pagamenti-corsi.index') }}" class="small-box-footer">
                            Pagamenti corsi <i class="fas fa-arrow-circle-right"></i>
                        </a>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-secondary">
                        <div class="inner">
                            <h3>{{ $pagamenti['da_incassare'] }}</h3>
                            <p>Pagamenti da incassare</p>
                        </div>
                        <div class="icon"><i class="fas fa-hand-holding-usd"></i></div>
                        <a href="{{ route('admin.pagamenti-corsi.index') }}" class="small-box-footer">
                            Pagamenti corsi <i class="fas fa-arrow-circle-right"></i>
                        </a>
                    </div>
                </div>
            @endif
        </div>

        @if ($formazione)
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-calendar-alt mr-1"></i> Prossimi corsi</h3>
                </div>
                <div class="card-body p-0">
                    @if ($formazione['prossimi']->isEmpty())
                        <p class="text-muted p-3 mb-0">Nessun corso in programma.</p>
                    @else
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr><th>Data</th><th>Protocollo</th><th>Tipologia</th><th class="text-right">Iscritti</th><th></th></tr>
                            </thead>
                            <tbody>
                                @foreach ($formazione['prossimi'] as $corso)
                                    <tr>
                                        <td>{{ $corso->data_inizio->format('d/m/Y H:i') }}</td>
                                        <td>{{ $corso->protocollo }}</td>
                                        <td>{{ $corso->tipologia->nome }}</td>
                                        <td class="text-right">{{ $corso->attivi_count }}@if ($corso->posti_max) / {{ $corso->posti_max }}@endif</td>
                                        <td class="text-right">
                                            <a href="{{ route('admin.corsi.iscritti.index', $corso) }}" class="btn btn-xs btn-outline-primary"
                                               title="Iscritti"><i class="fas fa-users"></i></a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        @endif
    @endif

    {{-- Area soci (funzione attiva): ogni parte con il suo permesso, l'attività solo agli amministratori --}}
    @if ($soci)
        <h5 class="mb-2"><i class="fas fa-id-card mr-1"></i> Area soci</h5>
        <div class="row">
            @isset($soci['membri'])
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-teal">
                        <div class="inner">
                            <h3>{{ $soci['membri']['totale'] }}</h3>
                            <p>Membri attivi</p>
                        </div>
                        <div class="icon"><i class="fas fa-id-card"></i></div>
                        <a href="{{ route('admin.membri.index') }}" class="small-box-footer">
                            Membri <i class="fas fa-arrow-circle-right"></i>
                        </a>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="card mb-3">
                        <div class="card-body py-2">
                            @foreach (\App\Models\Member::RUOLI as $ruolo => $etichetta)
                                <div class="d-flex justify-content-between py-1">
                                    <span>{{ $etichetta }}</span><strong>{{ $soci['membri']['perRuolo'][$ruolo] ?? 0 }}</strong>
                                </div>
                            @endforeach
                            <div class="d-flex justify-content-between py-1 border-top {{ $soci['membri']['senzaEmail'] ? 'text-danger' : 'text-muted' }}">
                                <span>Senza email (non possono accedere)</span><strong>{{ $soci['membri']['senzaEmail'] }}</strong>
                            </div>
                        </div>
                    </div>
                </div>
            @endisset
            @isset($soci['contenuti'])
                <div class="col-lg-3 col-6">
                    <div class="info-box">
                        <span class="info-box-icon bg-teal"><i class="fas fa-bullhorn"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Comunicazioni pubblicate</span>
                            <span class="info-box-number">{{ $soci['contenuti']['comunicazioni'] }}</span>
                        </div>
                    </div>
                    <div class="info-box">
                        <span class="info-box-icon bg-teal"><i class="fas fa-lock"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Documenti riservati pubblicati</span>
                            <span class="info-box-number">{{ $soci['contenuti']['documenti'] }}</span>
                        </div>
                    </div>
                </div>
            @endisset
            @isset($soci['attivita'])
                <div class="col-lg-3 col-6">
                    <div class="info-box">
                        <span class="info-box-icon bg-secondary"><i class="fas fa-sign-in-alt"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Soci entrati (30 giorni)</span>
                            <span class="info-box-number">{{ $soci['attivita']['sociEntrati'] }}</span>
                        </div>
                    </div>
                    <div class="info-box">
                        <span class="info-box-icon bg-secondary"><i class="fas fa-file-download"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Documenti aperti (30 giorni)</span>
                            <span class="info-box-number">{{ $soci['attivita']['documentiAperti'] }}</span>
                        </div>
                    </div>
                </div>
            @endisset
        </div>
    @endif

    @if ($daFare || $formazione || $pagamenti || $soci)
        <h5 class="mb-2"><i class="fas fa-globe mr-1"></i> Contenuti del sito</h5>
    @endif

    {{-- KPI principali --}}
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>{{ $stats['posts'] }}</h3>
                    <p>Post</p>
                </div>
                <div class="icon"><i class="fas fa-newspaper"></i></div>
                <a href="{{ route('admin.posts.index') }}" class="small-box-footer">
                    Gestisci <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        @if ($canPages)
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3>{{ $stats['pages'] }}</h3>
                        <p>Pagine</p>
                    </div>
                    <div class="icon"><i class="fas fa-file-alt"></i></div>
                    <a href="{{ route('admin.pages.index') }}" class="small-box-footer">
                        Gestisci <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
        @endif

        <div class="col-lg-3 col-6">
            <div class="small-box bg-secondary">
                <div class="inner">
                    <h3>{{ $stats['documents'] }}</h3>
                    <p>Documenti</p>
                </div>
                <div class="icon"><i class="fas fa-folder-open"></i></div>
                @if ($canDocs)
                    <a href="{{ route('admin.documents.index') }}" class="small-box-footer">
                        Gestisci <i class="fas fa-arrow-circle-right"></i>
                    </a>
                @endif
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $stats['galleryPhotos'] }}</h3>
                    <p>Foto nelle gallerie</p>
                </div>
                <div class="icon"><i class="fas fa-images"></i></div>
            </div>
        </div>
    </div>

    {{-- Metriche secondarie / cose da sistemare --}}
    <div class="row">
        <div class="col-md-3 col-sm-6">
            <div class="info-box">
                <span class="info-box-icon bg-success"><i class="fas fa-check"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Post pubblicati</span>
                    <span class="info-box-number">{{ $stats['postsPublished'] }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <a href="{{ route('admin.posts.index', ['stato' => 'bozze']) }}" class="text-dark">
                <div class="info-box">
                    <span class="info-box-icon bg-warning"><i class="fas fa-pen"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Bozze</span>
                        <span class="info-box-number">{{ $stats['postsDraft'] }}</span>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="info-box">
                <span class="info-box-icon bg-secondary"><i class="fas fa-image"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Post senza copertina</span>
                    <span class="info-box-number">{{ $stats['postsNoCover'] }}</span>
                </div>
            </div>
        </div>
        @if ($canPages)
            <div class="col-md-3 col-sm-6">
                <a href="{{ route('admin.pages.index', ['menu' => 'no']) }}" class="text-dark">
                    <div class="info-box">
                        <span class="info-box-icon bg-secondary"><i class="fas fa-eye-slash"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Pagine fuori dal menu</span>
                            <span class="info-box-number">{{ $stats['pagesOutOfMenu'] }}</span>
                        </div>
                    </div>
                </a>
            </div>
        @endif
    </div>

    <div class="row">
        {{-- Ultimi post --}}
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-clock mr-1"></i> Ultimi post</h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.posts.index') }}" class="btn btn-tool"><i class="fas fa-list"></i></a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <tbody>
                            @forelse ($recentPosts as $post)
                                <tr>
                                    <td>
                                        <a href="{{ $postLink($post) }}">{{ $post->title }}</a>
                                        @unless ($post->published)
                                            <span class="badge badge-warning ml-1">Bozza</span>
                                        @endunless
                                    </td>
                                    <td class="text-nowrap text-muted" style="width:1%">
                                        {{ optional($post->category)->name ?? '—' }}
                                    </td>
                                    <td class="text-nowrap text-muted text-right" style="width:1%">
                                        {{ $post->published_at?->format('d/m/Y') }}
                                    </td>
                                </tr>
                            @empty
                                <tr><td class="text-center text-muted py-4">Ancora nessun post.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Colonna destra: Riepilogo (con versione NILES) sopra Post per categoria --}}
        <div class="col-lg-4">
            {{-- Riepilogo --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-info-circle mr-1"></i> Riepilogo</h3>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span>Versione NILES corrente</span>
                        <span class="badge badge-primary">{{ $versione }}</span>
                    </li>
                    @if ($verificaAggiornamenti)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>Versione NILES disponibile</span>
                            <span id="niles-disponibile" class="badge badge-light" data-url="{{ route('admin.aggiornamenti') }}">Verifica…</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>Stato</span>
                            <span>
                                <span id="niles-stato" class="badge"></span>
                                @if ($aggiornamentoDalPannello)
                                    {{-- Compare solo se c'è una versione più recente (vedi lo script sotto). --}}
                                    <a id="niles-aggiorna" href="{{ route('admin.aggiornamento.index') }}" class="btn btn-xs btn-primary ml-1 d-none">Aggiorna</a>
                                @endif
                            </span>
                        </li>
                    @endif
                </ul>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-tags mr-1"></i> Post per categoria</h3>
                </div>
                <div class="card-body">
                    @php($maxCat = $byCategory->max('posts_count') ?: 1)
                    @forelse ($byCategory as $cat)
                        <div class="mb-2">
                            <div class="d-flex justify-content-between small">
                                <span>{{ $cat->name }}</span>
                                <span class="text-muted">{{ $cat->posts_count }}</span>
                            </div>
                            <div class="progress" style="height:6px">
                                <div class="progress-bar bg-danger" role="progressbar"
                                     style="width: {{ round($cat->posts_count / $maxCat * 100) }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted mb-0">Nessuna categoria.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Bozze da rivedere --}}
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-pen-nib mr-1"></i> Bozze da rivedere</h3>
                </div>
                <div class="card-body p-0">
                    @if ($draftPosts->isEmpty())
                        <p class="text-success text-center py-4 mb-0">
                            <i class="fas fa-check-circle"></i> Nessuna bozza in sospeso: tutto pubblicato.
                        </p>
                    @else
                        <table class="table table-sm mb-0">
                            <tbody>
                                @foreach ($draftPosts as $post)
                                    <tr>
                                        <td><a href="{{ $postLink($post) }}">{{ $post->title }}</a></td>
                                        <td class="text-nowrap text-muted text-right" style="width:1%">
                                            {{ optional($post->category)->name ?? '—' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        </div>
    </div>

@if ($verificaAggiornamenti)
    @push('js')
        <script>
            // Verifica aggiornamenti in async: la dashboard non attende GitHub.
            // Versione disponibile + stato (colore in base a quanto è distante la versione: patch/minor/major).
            (function () {
                var disp = document.getElementById('niles-disponibile');
                var stato = document.getElementById('niles-stato');
                if (!disp || !stato) return;
                var colori = { patch: 'badge-info', minor: 'badge-warning', major: 'badge-danger' };
                function pillola(el, classe, testo, url, titolo) {
                    el.className = 'badge ' + classe;
                    el.textContent = testo;
                    el.title = titolo || '';
                    if (url) { el.outerHTML = '<a href="' + url + '" target="_blank" rel="noopener" class="' + el.className + '" id="' + el.id + '" title="' + (titolo || '') + '">' + testo + '</a>'; }
                }
                function nonDisponibile() {
                    pillola(disp, 'badge-secondary', 'Non disponibile', null, "Impossibile verificare l'ultima versione pubblicata");
                    pillola(stato, 'badge-secondary', 'N/A', null, 'Stato non determinabile: versione disponibile non verificabile');
                }
                var ctrl = new AbortController();
                var timer = setTimeout(function () { ctrl.abort(); }, 6000);
                fetch(disp.dataset.url, { headers: { Accept: 'application/json' }, credentials: 'same-origin', signal: ctrl.signal })
                    .then(function (r) { return r.ok ? r.json() : Promise.reject(); })
                    .then(function (d) {
                        if (d.stato === 'aggiornato') {
                            pillola(disp, 'badge-secondary', d.versione);
                            pillola(stato, 'badge-success', 'Aggiornato');
                        } else if (d.stato === 'disponibile') {
                            pillola(disp, 'badge-secondary', d.versione);
                            pillola(document.getElementById('niles-stato'), colori[d.livello] || 'badge-warning',
                                'Aggiornamento ' + d.livello, d.url, 'Vai alla release ' + d.versione);
                            var aggiorna = document.getElementById('niles-aggiorna');
                            if (aggiorna) { aggiorna.classList.remove('d-none'); }
                        } else {
                            nonDisponibile();
                        }
                    })
                    .catch(nonDisponibile)
                    .finally(function () { clearTimeout(timer); });
            })();
        </script>
    @endpush
@endif

@stop
