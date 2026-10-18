@extends('adminlte::page')

@section('title', 'Posta in uscita')

@section('content_header')
    <h1>Posta in uscita</h1>
@stop

@section('content')
    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if (session('prova_errore'))
        <div class="alert alert-danger">{{ session('prova_errore') }}</div>
    @endif

    <p class="text-muted">Il server con cui NILES spedisce le email (codice di accesso, iscrizioni ai corsi, comunicazioni). Le impostazioni sono salvate nel database; la password è cifrata e non viene mai rimostrata.</p>

    @if ($demo)
        <div class="alert alert-secondary">
            <strong>Versione dimostrativa — esempi degli stati possibili (dati di fantasia, nulla viene salvato):</strong>
            <ul class="mb-0">
                <li>Impostazioni attive: <em>salvate dal pannello</em>, server <code>smtp.example.org</code>.</li>
                <li>Se nel file <code>.env</code> comparissero righe <code>MAIL_*</code>, qui apparirebbe l'avviso «Attive le impostazioni del file .env».</li>
                <li>Dopo «Invia email di prova» compare «Email di prova inviata» oppure l'errore del server di posta.</li>
            </ul>
        </div>
    @endif

    @if ($daEnv)
        <div class="alert alert-warning">
            <strong>Attive le impostazioni del file .env.</strong> Server: <code>{{ $envHost }}</code>@if ($envUtente), utente <code>{{ $envUtente }}</code>@endif, invio <code>{{ $envMailer }}</code>.
            Quelle salvate qui non verranno usate finché non togli o commenti le righe <code>MAIL_*</code> dal file <code>.env</code> (righe presenti: {{ implode(', ', $envPresenti) }}).
        </div>
        @if ($envMancanti)
            <div class="alert alert-danger">
                <strong>Configurazione .env incompleta.</strong> Mancano: <code>{{ implode(', ', $envMancanti) }}</code>.
                Completa le variabili nel file <code>.env</code>, oppure toglile tutte e configura la posta da questa pagina.
            </div>
        @endif
    @elseif (! $demo)
        <div class="alert alert-info">
            <strong>Impostazioni attive:</strong>
            @if (! filled($info->getAttributes()['mail_mailer'] ?? null))
                nessuna salvata: le email non vengono spedite (finiscono solo nel file di log).
            @elseif ($passwordIllegibile)
                salvate dal pannello, ma la password non si può leggere (la chiave dell'applicazione è cambiata): riscrivila e salva. Nel frattempo la posta resta disattivata.
            @elseif ($info->mail_mailer === 'log')
                salvate dal pannello — posta disattivata, le email finiscono solo nel file di log.
            @else
                salvate dal pannello — server <code>{{ $info->mail_host }}</code>, utente <code>{{ $info->mail_username }}</code>.
            @endif
        </div>
    @endif

    <form method="POST" action="{{ route('admin.posta.update') }}" id="form-posta">
        @csrf
        @method('PUT')

        <div class="card">
            <div class="card-body">
                <div class="form-group">
                    <label for="mail_mailer">Tipo di invio</label>
                    <select name="mail_mailer" id="mail_mailer" class="form-control col-md-6 @error('mail_mailer') is-invalid @enderror">
                        @foreach (['smtp' => 'SMTP (spedisce davvero le email)', 'log' => 'Disattivata (le email finiscono solo nel file di log)'] as $v => $e)
                            <option value="{{ $v }}" @selected(old('mail_mailer', $info->mail_mailer ?: 'smtp') === $v)>{{ $e }}</option>
                        @endforeach
                    </select>
                    @error('mail_mailer') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div id="campi-smtp">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="mail_host">Server (host)</label>
                            <input name="mail_host" id="mail_host" class="form-control @error('mail_host') is-invalid @enderror" value="{{ old('mail_host', $info->mail_host) }}" autocomplete="off">
                            @error('mail_host') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="form-group col-md-3">
                            <label for="mail_port">Porta</label>
                            <input type="number" min="1" max="65535" name="mail_port" id="mail_port" class="form-control @error('mail_port') is-invalid @enderror" value="{{ old('mail_port', $info->mail_port) }}">
                            @error('mail_port') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="form-group col-md-3">
                            <label for="mail_encryption">Cifratura</label>
                            <select name="mail_encryption" id="mail_encryption" class="form-control @error('mail_encryption') is-invalid @enderror">
                                @foreach (['ssl' => 'SSL (porta 465)', 'tls' => 'TLS / STARTTLS (porta 587)', 'none' => 'Nessuna'] as $v => $e)
                                    <option value="{{ $v }}" @selected(old('mail_encryption', $info->mail_encryption ?: 'ssl') === $v)>{{ $e }}</option>
                                @endforeach
                            </select>
                            @error('mail_encryption') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="mail_username">Utente</label>
                            <input name="mail_username" id="mail_username" class="form-control @error('mail_username') is-invalid @enderror" value="{{ old('mail_username', $info->mail_username) }}" autocomplete="off">
                            @error('mail_username') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="form-group col-md-6">
                            <label for="mail_password">Password</label>
                            <input type="password" name="mail_password" id="mail_password" class="form-control @error('mail_password') is-invalid @enderror" autocomplete="new-password"
                                placeholder="{{ $passwordSalvata ? '•••••••• salvata — lascia vuoto per non cambiarla' : '' }}">
                            @error('mail_password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <small class="text-muted">Dopo una prova va riscritta, se non è ancora stata salvata.</small>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="mail_from_address">Indirizzo mittente</label>
                            <input type="email" name="mail_from_address" id="mail_from_address" class="form-control @error('mail_from_address') is-invalid @enderror" value="{{ old('mail_from_address', $info->mail_from_address) }}" placeholder="vuoto = l'utente SMTP">
                            @error('mail_from_address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="form-group col-md-6">
                            <label for="mail_from_name">Nome mittente</label>
                            <input name="mail_from_name" id="mail_from_name" class="form-control @error('mail_from_name') is-invalid @enderror" value="{{ old('mail_from_name', $info->mail_from_name) }}">
                            @error('mail_from_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>

                @if ($info->mail_modificata_at)
                    <p class="text-muted small mb-0">Ultima modifica: {{ $info->mail_modificata_at->format('d/m/Y H:i') }}@if ($info->mail_modificata_da) — {{ $info->mail_modificata_da }}@endif.</p>
                @endif
            </div>

            <div class="card-footer">
                <button type="submit" class="btn btn-primary">Salva</button>
                <button type="submit" class="btn btn-outline-secondary ml-2" id="btn-prova" formaction="{{ route('admin.posta.prova') }}">Invia email di prova</button>
                <small class="text-muted ml-2">La prova usa i valori scritti qui (anche non salvati) e spedisce solo alla tua email: {{ auth('admin')->user()->email }}.</small>
            </div>
        </div>
    </form>

    @if ($info->mail_prova_at)
        @php($p = $info->mail_prova_parametri ?? [])
        <div class="card">
            <div class="card-body">
                <strong>Ultima prova:</strong> {{ $info->mail_prova_at->format('d/m/Y H:i') }} —
                @if ($info->mail_prova_esito === 'ok') <span class="text-success">riuscita</span>@else <span class="text-danger">non riuscita</span>: {{ $info->mail_prova_esito }}@endif
                <div class="text-muted small">Parametri usati: server {{ $p['server'] ?? '—' }}, porta {{ $p['porta'] ?? '—' }}, cifratura {{ $p['cifratura'] ?? '—' }}, utente {{ $p['utente'] ?? '—' }}, mittente {{ $p['mittente'] ?? '—' }}.</div>
            </div>
        </div>
    @endif

    {{-- Velo con rotella: durante la prova la pagina non resta mai ferma --}}
    <div id="occupato" hidden>
        <div class="rot"></div>
        <b>Invio della email di prova in corso…</b>
        <span id="occ-t" class="text-muted"></span>
    </div>
@stop

@push('css')
    <style>
        .alert code{color:inherit;font-weight:600;background:rgba(0,0,0,.1);padding:1px 5px;border-radius:3px}
        #occupato{position:fixed;inset:0;background:rgba(255,255,255,.93);display:flex;flex-direction:column;align-items:center;justify-content:center;gap:14px;z-index:99999;text-align:center;padding:20px}
        #occupato[hidden]{display:none}
        .rot{width:56px;height:56px;border:6px solid #ddd;border-top-color:#dc3545;border-radius:50%;animation:niles-giro 1s linear infinite}
        @keyframes niles-giro{to{transform:rotate(360deg)}}
    </style>
@endpush

@push('js')
    <script>
        (function () {
            var tipo = document.getElementById('mail_mailer'), campi = document.getElementById('campi-smtp'), prova = document.getElementById('btn-prova');
            function aggiorna() { var smtp = tipo.value === 'smtp'; campi.style.display = smtp ? '' : 'none'; prova.disabled = !smtp; }
            tipo.addEventListener('change', aggiorna); aggiorna();

            var velo = document.getElementById('occupato'), tempo = document.getElementById('occ-t');
            document.getElementById('form-posta').addEventListener('submit', function (e) {
                if (e.submitter !== prova) return;
                var t0 = Date.now(); velo.hidden = false;
                setInterval(function () {
                    var s = Math.floor((Date.now() - t0) / 1000);
                    tempo.textContent = 'Trascorsi ' + Math.floor(s / 60) + ':' + String(s % 60).padStart(2, '0');
                }, 1000);
            });
        })();
    </script>
@endpush
