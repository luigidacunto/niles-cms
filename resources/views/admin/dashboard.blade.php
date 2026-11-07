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

    {{-- Contenuti del sito e Riepilogo --}}
    @if ($formazione || $pagamenti || $soci)
        <h5 class="mb-2"><i class="fas fa-globe mr-1"></i> Contenuti del sito</h5>
    @endif
    <div class="row dash-row">
        <div class="col-lg-3 col-6">
            <x-admin.riquadro :numero="$stats['posts']" testo="Post" icona="fa-newspaper" colore="danger" :url="route('admin.posts.index')" />
        </div>
        @if ($canPages)
            <div class="col-lg-3 col-6">
                <x-admin.riquadro :numero="$stats['pages']" testo="Pagine" icona="fa-file-alt" colore="info" :url="route('admin.pages.index')" />
            </div>
        @endif
        <div class="col-lg-3 col-6">
            <x-admin.riquadro :numero="$stats['documents']" testo="Documenti" icona="fa-folder-open"
                              :url="$canDocs ? route('admin.documents.index') : null" />
        </div>
        <div class="col-lg-3 col-6">
            <x-admin.riquadro :numero="$stats['galleryPhotos']" testo="Foto nelle gallerie" icona="fa-images" colore="success" />
        </div>
    </div>

    <div class="row dash-row">
        <div class="col-lg-3 col-sm-6">
            <x-admin.riquadro piccolo :numero="$stats['postsPublished']" testo="Post pubblicati" icona="fa-check" colore="success" />
            <x-admin.riquadro piccolo :numero="$stats['postsDraft']" testo="Bozze" icona="fa-pen" colore="warning"
                              :url="route('admin.posts.index', ['stato' => 'bozze'])" />
        </div>
        <div class="col-lg-3 col-sm-6">
            <x-admin.riquadro piccolo :numero="$stats['postsNoCover']" testo="Post senza copertina" icona="fa-image" />
            @if ($canPages)
                <x-admin.riquadro piccolo :numero="$stats['pagesOutOfMenu']" testo="Pagine fuori dal menu" icona="fa-eye-slash"
                                  :url="route('admin.pages.index', ['menu' => 'no'])" />
            @endif
        </div>
        <div class="col-lg-6">
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
        </div>
    </div>

    {{-- Formazione (permesso Corsi) e pagamenti (funzione attiva + permesso) --}}
    @if ($formazione || $pagamenti)
        <h5 class="mb-2"><i class="fas fa-chalkboard-teacher mr-1"></i> Formazione</h5>
        <div class="row dash-row">
            @if ($formazione)
                <div class="col-lg-3 col-6">
                    <x-admin.riquadro :numero="$formazione['aperti']" testo="Corsi con iscrizioni aperte" icona="fa-door-open"
                                      colore="primary" :url="route('admin.corsi.index')" link="Corsi" />
                </div>
                <div class="col-lg-3 col-6">
                    <x-admin.riquadro piccolo :numero="$formazione['iscrittiFuturi']" testo="Iscritti ai prossimi corsi" icona="fa-user-check" colore="primary" />
                    <x-admin.riquadro piccolo :numero="$formazione['iscrizioniSettimana']" testo="Iscrizioni negli ultimi 7 giorni" icona="fa-calendar-plus" colore="info" />
                </div>
            @endif
            @if ($pagamenti)
                <div class="col-lg-3 col-6">
                    <x-admin.riquadro :numero="$pagamenti['da_fatturare']" testo="Pagamenti da fatturare" icona="fa-file-invoice"
                                      colore="warning" :url="route('admin.pagamenti-corsi.index')" link="Pagamenti corsi" />
                </div>
                <div class="col-lg-3 col-6">
                    <x-admin.riquadro :numero="$pagamenti['da_incassare']" testo="Pagamenti da incassare" icona="fa-hand-holding-usd"
                                      :url="route('admin.pagamenti-corsi.index')" link="Pagamenti corsi" />
                </div>
            @endif
        </div>

        @if ($formazione)
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-calendar-alt mr-1"></i> Prossimi corsi</h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.corsi.index') }}" class="btn btn-tool" title="Corsi"><i class="fas fa-list"></i></a>
                    </div>
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
                                        <td class="text-right" style="width:1%">
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
        <div class="row dash-row">
            @isset($soci['membri'])
                <div class="col-lg-3 col-6">
                    <x-admin.riquadro :numero="$soci['membri']['totale']" testo="Membri attivi" icona="fa-id-card" colore="teal"
                                      :url="route('admin.membri.index')" link="Membri" />
                </div>
                <div class="col-lg-3 col-6">
                    <div class="card">
                        <div class="card-body py-2 d-flex flex-column justify-content-center">
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
                <div class="col-lg-3 col-sm-6">
                    <x-admin.riquadro piccolo :numero="$soci['contenuti']['comunicazioni']" testo="Comunicazioni pubblicate" icona="fa-bullhorn"
                                      colore="teal" :url="route('admin.comunicazioni-soci.index')" />
                    <x-admin.riquadro piccolo :numero="$soci['contenuti']['documenti']" testo="Documenti riservati pubblicati" icona="fa-lock"
                                      colore="teal" :url="route('admin.documenti-riservati.index')" />
                </div>
            @endisset
            @isset($soci['attivita'])
                <div class="col-lg-3 col-sm-6">
                    <x-admin.riquadro piccolo :numero="$soci['attivita']['sociEntrati']" testo="Soci entrati (30 giorni)" icona="fa-sign-in-alt"
                                      :url="route('admin.registro-accessi')" />
                    <x-admin.riquadro piccolo :numero="$soci['attivita']['documentiAperti']" testo="Documenti aperti (30 giorni)" icona="fa-file-download"
                                      :url="route('admin.registro-download')" />
                </div>
            @endisset
        </div>
    @endif

    {{-- Elenchi dei contenuti --}}
    <div class="row dash-row">
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

        <div class="col-lg-4">
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
