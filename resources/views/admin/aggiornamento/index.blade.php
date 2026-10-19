<?php // @formatter:off ?>
@extends('adminlte::page')

@section('title', 'Aggiornamento')

@section('content_header')
    <h1>Aggiornamento di NILES</h1>
@stop

@section('content')
    @php($tuttoOk = collect($requisiti)->every(fn ($r) => $r[1]))

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong>Versione</strong>
            <form method="POST" action="{{ route('admin.aggiornamento.controlla') }}" class="m-0">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-secondary" title="Di norma la verifica si ripete una volta al giorno">
                    <i class="fas fa-redo mr-1"></i> Controlla ora
                </button>
            </form>
        </div>
        <ul class="list-group list-group-flush">
            <li class="list-group-item d-flex justify-content-between"><span>Installata</span><span class="badge badge-primary">{{ $versione }}</span></li>
            <li class="list-group-item d-flex justify-content-between">
                <span>Disponibile</span>
                @if ($disponibile)
                    <span><span class="badge badge-warning">{{ $disponibile }}</span> <a href="{{ $stato['url'] ?? '#' }}" target="_blank" rel="noopener">note di versione</a></span>
                @elseif (($stato['stato'] ?? null) === 'aggiornato')
                    <span class="badge badge-success">nessuna: sei aggiornato</span>
                @else
                    <span class="badge badge-secondary">non verificabile</span>
                @endif
            </li>
        </ul>
    </div>

    <div id="esito" class="alert alert-danger d-none"></div>

    @if ($disponibile)
        <div class="card">
            <div class="card-header"><strong>Requisiti</strong></div>
            <ul class="list-group list-group-flush">
                @foreach ($requisiti as [$etichetta, $ok, $dettaglio])
                    <li class="list-group-item d-flex justify-content-between">
                        <span>{{ $etichetta }}</span>
                        <span class="{{ $ok ? 'text-success' : 'text-danger' }}"><i class="fas {{ $ok ? 'fa-check' : 'fa-times' }}"></i> {{ $dettaglio }}</span>
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="card">
            <div class="card-header"><strong>Cosa succede</strong></div>
            <div class="card-body">
                <ol class="mb-2">
                    <li>Si scarica la versione <b>{{ $disponibile }}</b> e se ne verifica l'integrità (il sito resta online).</li>
                    <li>Si fa una <b>copia di sicurezza del database</b>, che puoi scaricare qui sotto.</li>
                    <li>Per pochi secondi il sito va in manutenzione: si sostituisce il codice e si aggiornano le tabelle. Se qualcosa va storto, il codice precedente viene rimesso al suo posto.</li>
                    <li>Si torna da soli al pannello.</li>
                </ol>
                <p class="text-muted mb-0">Non chiudere né ricaricare la pagina mentre l'operazione è in corso. Non vengono toccati <code>.env</code>, i file caricati e la configurazione.</p>
            </div>
        </div>

        <button id="avvia" type="button" class="btn btn-primary btn-lg" @disabled(! $tuttoOk)>
            <i class="fas fa-sync-alt mr-1"></i> Aggiorna a {{ $disponibile }}
        </button>
        @unless ($tuttoOk)
            <span class="text-danger ml-2">Risolvi i requisiti non soddisfatti per poter aggiornare.</span>
        @endunless
    @endif

    <div class="card mt-4">
        <div class="card-header"><strong>Copie del database</strong></div>
        <div class="card-body">
            <p class="text-muted">Conservate sul server in una cartella non raggiungibile dal web (contengono dati personali): restano le ultime due. Il ripristino si fa importando il file, ad esempio da phpMyAdmin.</p>
            @forelse ($copie as $c)
                <div><a href="{{ route('admin.aggiornamento.copia', $c['nome']) }}"><i class="fas fa-download mr-1"></i>{{ $c['nome'] }}</a>
                    <span class="text-muted">— {{ number_format($c['dimensione'] / 1048576, 1, ',', '.') }} MB, {{ \Carbon\Carbon::createFromTimestamp($c['data'])->timezone(config('app.committee_timezone', 'Europe/Rome'))->format('d/m/Y H:i') }}</span></div>
            @empty
                <p class="mb-0">Nessuna copia presente.</p>
            @endforelse
        </div>
    </div>

    {{-- Velo con rotella: durante le operazioni lunghe la pagina non resta mai ferma --}}
    <div id="occupato" hidden>
        <div class="rot"></div>
        <b id="occ-msg"></b>
        <div class="progress" style="width:min(420px,80vw);height:10px"><div id="occ-bar" class="progress-bar bg-danger" style="width:0%"></div></div>
        <span id="occ-t" class="text-muted"></span>
    </div>
@stop

@push('css')
    <style>
        #occupato{position:fixed;inset:0;background:rgba(255,255,255,.93);display:flex;flex-direction:column;align-items:center;justify-content:center;gap:14px;z-index:99999;text-align:center;padding:20px}
        #occupato[hidden]{display:none}
        .rot{width:56px;height:56px;border:6px solid #ddd;border-top-color:#dc3545;border-radius:50%;animation:niles-giro 1s linear infinite}
        @keyframes niles-giro{to{transform:rotate(360deg)}}
    </style>
@endpush

@push('js')
    <script>
        (function () {
            var avvia = document.getElementById('avvia');
            if (!avvia) return;
            var url = @json(route('admin.aggiornamento.passo'));
            var csrf = @json(csrf_token());
            var velo = document.getElementById('occupato'), msg = document.getElementById('occ-msg'),
                barra = document.getElementById('occ-bar'), tempo = document.getElementById('occ-t'), esito = document.getElementById('esito');
            var timer, t0;

            function mostra(testo, percento) {
                msg.textContent = testo;
                barra.parentNode.style.visibility = percento === null ? 'hidden' : 'visible';
                if (percento !== null) barra.style.width = percento + '%';
            }
            function avviaVelo() {
                t0 = Date.now(); velo.hidden = false;
                timer = setInterval(function () {
                    var s = Math.floor((Date.now() - t0) / 1000);
                    tempo.textContent = 'Trascorsi ' + Math.floor(s / 60) + ':' + String(s % 60).padStart(2, '0');
                }, 1000);
            }
            function fermaVelo() { velo.hidden = true; clearInterval(timer); }
            function chiama(fase) {
                return fetch(url, {
                    method: 'POST', credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify({ fase: fase })
                }).then(function (r) {
                    return r.json().catch(function () { throw new Error('Risposta inattesa del server (tempo scaduto?). Ricarica la pagina e riprova.'); })
                        .then(function (d) { if (!d.ok) throw new Error(d.err || 'Errore sconosciuto'); return d; });
                });
            }
            // Ripete un passo finché risponde fatto=true, aggiornando il testo e la barra a ogni giro.
            function ripeti(fase, descrivi) {
                return chiama(fase).then(function (d) {
                    descrivi(d);
                    return d.fatto ? d : ripeti(fase, descrivi);
                });
            }

            avvia.addEventListener('click', function () {
                if (!confirm('Avviare l\'aggiornamento? Il sito resterà in manutenzione per pochi secondi.')) return;
                esito.classList.add('d-none');
                avviaVelo();
                mostra('Download del pacchetto…', null);
                chiama('scarica')
                    .then(function () { return ripeti('estrai', function (d) { mostra('Estrazione del pacchetto… ' + d.i + ' / ' + d.n + ' file', Math.round(d.i / d.n * 100)); }); })
                    .then(function () { mostra('Copia di sicurezza del database…', null); return chiama('dump-inizia'); })
                    .then(function () { return ripeti('dump', function (d) { mostra('Copia del database… tabella ' + d.tabella, Math.round(d.t / Math.max(d.n, 1) * 100)); }); })
                    .then(function () { mostra('Avvio dell\'aggiornamento…', null); return chiama('applica'); })
                    .then(function (d) { window.location.href = d.url; })   // fase B: il programma di aggiornamento prosegue da solo
                    .catch(function (e) {
                        fermaVelo();
                        esito.textContent = 'Operazione interrotta: ' + e.message;
                        esito.classList.remove('d-none');
                    });
            });
        })();
    </script>
@endpush
