@extends('adminlte::page')

@section('title', 'Sicurezza moduli pubblici')

@section('content_header')
    <h1>Sicurezza moduli pubblici</h1>
@stop

@section('content')
    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <p class="text-muted">Protezioni anti-bot per i moduli compilabili dal pubblico (oggi: iscrizione ai corsi).</p>

    <form method="POST" action="{{ route('admin.sicurezza-form.update') }}">
        @csrf
        @method('PUT')

        <div class="card">
            <div class="card-body">
                <div class="form-check mb-4">
                    <input type="checkbox" name="rate_limiting_attivo" id="rate_limiting_attivo" value="1" class="form-check-input"
                        @checked(old('rate_limiting_attivo', $impostazioni->rate_limiting_attivo))>
                    <label class="form-check-label" for="rate_limiting_attivo">
                        <strong>Limite tentativi (rate limiting)</strong>
                        <br><small class="text-muted">Limita le richieste per indirizzo IP sulla pagina e sull'invio del modulo di iscrizione ai corsi, per rallentare tentativi automatici di indovinare i link o spammare le iscrizioni. Nessun servizio esterno.</small>
                    </label>
                </div>

                <div class="form-row ml-3 mb-4">
                    @foreach ([
                        'invii_minuto' => 'Invii del modulo al minuto', 'invii_ora' => 'Invii del modulo all\'ora',
                        'visite_minuto' => 'Aperture della pagina al minuto', 'visite_ora' => 'Aperture della pagina all\'ora',
                    ] as $chiave => $etichetta)
                        <div class="form-group col-md-3">
                            <label class="small" for="limite_{{ $chiave }}">{{ $etichetta }}</label>
                            <input type="number" min="1" max="1000" name="limite_{{ $chiave }}" id="limite_{{ $chiave }}" class="form-control @error('limite_'.$chiave) is-invalid @enderror"
                                value="{{ old('limite_'.$chiave, $impostazioni->{'limite_'.$chiave}) }}" placeholder="{{ config('sicurezza.limiti.'.$chiave) }}">
                            @error('limite_'.$chiave) <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    @endforeach
                    <div class="col-12"><small class="text-muted">Per indirizzo IP. Valgono due soglie insieme: al minuto ferma chi invia a raffica (uno script), all'ora chi va piano. Vuoto = valore predefinito (mostrato nel campo).</small></div>
                </div>

                <div class="form-check">
                    <input type="checkbox" name="captcha_attivo" id="captcha_attivo" value="1" class="form-check-input @error('captcha_attivo') is-invalid @enderror"
                        @checked(old('captcha_attivo', $impostazioni->captcha_attivo))>
                    <label class="form-check-label" for="captcha_attivo">
                        <strong>CAPTCHA (Cloudflare Turnstile)</strong>
                        <br><small class="text-muted">Verifica anti-robot sul modulo di iscrizione ai corsi. Opzionale: ogni comitato usa il proprio account Cloudflare (gratuito), crea un widget Turnstile e incolla qui le due chiavi. Se Cloudflare non è raggiungibile, l'iscrizione passa comunque.</small>
                    </label>
                    @error('captcha_attivo') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>

                <div class="form-row mt-3">
                    <div class="form-group col-md-6">
                        <label for="turnstile_site_key">Site key</label>
                        <input name="turnstile_site_key" id="turnstile_site_key" class="form-control"
                            value="{{ old('turnstile_site_key', $impostazioni->turnstile_site_key) }}" autocomplete="off">
                    </div>
                    <div class="form-group col-md-6">
                        <label for="turnstile_secret_key">Secret key</label>
                        <input type="password" name="turnstile_secret_key" id="turnstile_secret_key" class="form-control" autocomplete="new-password"
                            placeholder="{{ filled($impostazioni->turnstile_secret_key) ? '•••••••• salvata — lascia vuoto per non cambiarla' : '' }}">
                        <small class="text-muted">Salvata cifrata, non viene mai rimostrata.</small>
                    </div>
                </div>
            </div>

            <div class="card-footer">
                <button type="submit" class="btn btn-primary">Salva</button>
            </div>
        </div>
    </form>
@stop
