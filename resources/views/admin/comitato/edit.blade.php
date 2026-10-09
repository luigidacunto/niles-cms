<?php // @formatter:off ?>
@extends('adminlte::page')

@section('title', 'Dati del comitato')

@section('content_header')
    <h1>Dati del comitato</h1>
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
    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <p class="text-muted">
        Questi dati compaiono nel footer del sito pubblico (blocco Contatti). Lascia vuoto un campo per
        non mostrarlo.
    </p>

    <form method="POST" action="{{ route('admin.comitato.update') }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="card">
            <div class="card-header"><strong>Logo</strong></div>
            <div class="card-body">
                <p class="text-muted">
                    L'orizzontale è quello mostrato nell'intestazione del sito, a tutte le risoluzioni.
                    Il verticale è un campo pronto per usi futuri (non ancora mostrato da nessuna parte
                    del sito pubblico) — puoi caricarlo comunque, resta salvato e riutilizzabile.
                </p>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <details class="mb-2">
                            <summary class="text-info font-weight-bold" style="cursor:pointer">Come preparare il logo orizzontale</summary>
                            <div class="callout callout-info mt-2 mb-2">
                                <p class="mb-2">
                                    L'intestazione ingrandisce l'immagine al doppio dell'altezza e ne mostra solo la parte
                                    centrale: il margine vuoto attorno al logo viene ritagliato via. Per questo un logo
                                    disegnato fino ai bordi risulterebbe tagliato.
                                </p>
                                <ul class="mb-0">
                                    <li>Formato: PNG con sfondo trasparente, circa 700&times;220 px (rapporto 3:1&ndash;3,2:1).</li>
                                    <li>
                                        Simbolo e scritte nell'area centrale, lasciando libero almeno il
                                        <strong>30% in alto, il 20% in basso e l'8% a destra e a sinistra</strong>
                                        (su 700&times;220 px: 66 px sopra, 44 px sotto, 56 px per lato; area utile circa 588&times;110 px).
                                        Per il logo di riferimento sono circa il 33% sopra, il 22% sotto e il 10% per lato.
                                    </li>
                                    <li>Un logo molto più largo che alto (oltre 5:1) va ridotto in larghezza, non allungato.</li>
                                    <li>Non togliere mai pixel al logo dell'ente: aggiungi il margine vuoto attorno, non ritagliarlo.</li>
                                </ul>
                            </div>
                        </details>
                        <label>Orizzontale <small class="text-muted">PNG trasparente, circa 700&times;220 px</small></label>
                        @if ($info->logo_orizzontale_url)
                            <div class="mb-2">
                                <img src="{{ $info->logo_orizzontale_url }}" alt="" style="max-height:70px" class="d-block mb-1">
                                <label class="mb-0"><input type="checkbox" name="remove_logo_orizzontale" value="1"> rimuovi</label>
                            </div>
                        @endif
                        <input type="file" name="logo_orizzontale" accept="image/*" class="form-control-file">
                    </div>
                    <div class="form-group col-md-6">
                        <details class="mb-2">
                            <summary class="text-info font-weight-bold" style="cursor:pointer">Come preparare il logo verticale</summary>
                            <div class="callout callout-info mt-2 mb-2">
                                <p class="mb-2">
                                    Non è ancora mostrato sul sito pubblico: caricalo comunque, nel formato giusto, per averlo
                                    pronto. L'immagine viene solo ridimensionata, mai ritagliata.
                                </p>
                                <ul class="mb-0">
                                    <li>Formato: PNG con sfondo trasparente, quadrato, circa 500&times;500 px.</li>
                                    <li>
                                        Logo (simbolo e scritte) centrato, con un margine vuoto di circa
                                        <strong>17% per lato, 24% in alto e 9% in basso</strong>
                                        (su 500&times;500 px: 85 px per lato, 120 px sopra, 45 px sotto; area utile circa 330&times;335 px).
                                    </li>
                                    <li>Non togliere mai pixel al logo dell'ente: aggiungi il margine vuoto attorno, non ritagliarlo.</li>
                                </ul>
                            </div>
                        </details>
                        <label>Verticale <small class="text-muted">PNG trasparente, circa 500&times;500 px, non ancora mostrato sul sito</small></label>
                        @if ($info->logo_verticale_url)
                            <div class="mb-2">
                                <img src="{{ $info->logo_verticale_url }}" alt="" style="max-height:70px" class="d-block mb-1">
                                <label class="mb-0"><input type="checkbox" name="remove_logo_verticale" value="1"> rimuovi</label>
                            </div>
                        @endif
                        <input type="file" name="logo_verticale" accept="image/*" class="form-control-file">
                    </div>
                    <div class="form-group col-md-6">
                        <label>Favicon <small class="text-muted">icona nella scheda del browser; PNG quadrato, consigliato 256&times;256px. Senza file vale l'icona generica</small></label>
                        @if ($info->favicon_url)
                            <div class="mb-2">
                                <img src="{{ $info->favicon_url }}" alt="" style="max-height:32px" class="d-block mb-1">
                                <label class="mb-0"><input type="checkbox" name="remove_favicon" value="1"> rimuovi</label>
                            </div>
                        @endif
                        <input type="file" name="favicon" accept="image/png" class="form-control-file">
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><strong>Loghi istituzionali</strong></div>
            <div class="card-body">
                <p class="text-muted">
                    Marchi di terzi, non inclusi nel software: carica i file solo se hai diritto di utilizzarli.
                    Senza file, nell'intestazione e nel piè di pagina compaiono due semplici link testuali
                    (ifrc.org, cri.it). Le immagini vengono ridimensionate ma mai ritagliate.
                </p>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>I.F.R.C. <small class="text-muted">mostrato nell'intestazione e nel piè di pagina</small></label>
                        @if ($info->logo_ifrc_url)
                            <div class="mb-2">
                                <img src="{{ $info->logo_ifrc_url }}" alt="" style="max-height:40px" class="d-block mb-1">
                                <label class="mb-0"><input type="checkbox" name="remove_logo_ifrc" value="1"> rimuovi</label>
                            </div>
                        @endif
                        <input type="file" name="logo_ifrc" accept="image/*" class="form-control-file">
                    </div>
                    <div class="form-group col-md-6">
                        <label>Un'Italia che aiuta <small class="text-muted">mostrato nel piè di pagina</small></label>
                        @if ($info->logo_un_italia_url)
                            <div class="mb-2">
                                <img src="{{ $info->logo_un_italia_url }}" alt="" style="max-height:40px" class="d-block mb-1">
                                <label class="mb-0"><input type="checkbox" name="remove_logo_un_italia" value="1"> rimuovi</label>
                            </div>
                        @endif
                        <input type="file" name="logo_un_italia" accept="image/*" class="form-control-file">
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="form-row">
                    <div class="form-group col-md-8">
                        <label>Denominazione</label>
                        <input name="denominazione" value="{{ old('denominazione', $info->denominazione) }}" class="form-control">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label>P.IVA</label>
                        <input name="piva" value="{{ old('piva', $info->piva) }}" class="form-control">
                    </div>
                    <div class="form-group col-md-4">
                        <label>Codice fiscale</label>
                        <input name="codice_fiscale" value="{{ old('codice_fiscale', $info->codice_fiscale) }}" class="form-control">
                    </div>
                    <div class="form-group col-md-4">
                        <label>Codice fatturazione elettronica</label>
                        <input name="codice_fatturazione_elettronica" value="{{ old('codice_fatturazione_elettronica', $info->codice_fatturazione_elettronica) }}" class="form-control">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label>Telefono</label>
                        <input name="telefono" value="{{ old('telefono', $info->telefono) }}" class="form-control">
                    </div>
                    <div class="form-group col-md-4">
                        <label>Email</label>
                        <input type="email" name="email" value="{{ old('email', $info->email) }}" class="form-control">
                    </div>
                    <div class="form-group col-md-4">
                        <label>PEC</label>
                        <input type="email" name="pec" value="{{ old('pec', $info->pec) }}" class="form-control">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-8">
                        <label>Indirizzo</label>
                        <input name="indirizzo" value="{{ old('indirizzo', $info->indirizzo) }}" class="form-control">
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><strong>Social</strong></div>
            <div class="card-body">
                <p class="text-muted">
                    Icone mostrate nel footer del sito pubblico. Lascia vuoto un campo per non mostrare la
                    relativa icona.
                </p>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Facebook</label>
                        <input type="url" name="facebook_url" value="{{ old('facebook_url', $info->facebook_url) }}" class="form-control" placeholder="https://www.facebook.com/...">
                    </div>
                    <div class="form-group col-md-6">
                        <label>Instagram</label>
                        <input type="url" name="instagram_url" value="{{ old('instagram_url', $info->instagram_url) }}" class="form-control" placeholder="https://www.instagram.com/...">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>YouTube</label>
                        <input type="url" name="youtube_url" value="{{ old('youtube_url', $info->youtube_url) }}" class="form-control" placeholder="https://www.youtube.com/channel/...">
                    </div>
                    <div class="form-group col-md-6">
                        <label>X (Twitter)</label>
                        <input type="url" name="x_url" value="{{ old('x_url', $info->x_url) }}" class="form-control" placeholder="https://x.com/...">
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><strong>Coordinate bancarie e metodi di pagamento</strong></div>
            <div class="card-body">
                <p class="text-muted">
                    Compaiono nell'email di iscrizione e nella pagina personale degli iscritti ai corsi <em>a pagamento</em>
                    che hanno scelto il bonifico (un metodo di pagamento il cui nome contiene «bonifico», come quello di
                    base), insieme alla causale già compilata. Senza IBAN non viene mostrato nulla.
                </p>
                <div class="form-row">
                    <div class="form-group col-md-5">
                        <label>IBAN</label>
                        <input name="iban" value="{{ old('iban', $info->iban) }}" class="form-control text-uppercase" placeholder="IT60 X054 2811 1010 0000 0123 456" maxlength="42">
                    </div>
                    <div class="form-group col-md-4">
                        <label>Intestatario del conto</label>
                        <input name="intestatario_conto" value="{{ old('intestatario_conto', $info->intestatario_conto) }}" class="form-control" placeholder="Se vuoto: {{ $info->denominazione ?: config('app.public_name') }}">
                    </div>
                    <div class="form-group col-md-3">
                        <label>Banca</label>
                        <input name="banca" value="{{ old('banca', $info->banca) }}" class="form-control">
                    </div>
                </div>
                <hr>
                <h6 class="font-weight-bold">Metodi di pagamento <span class="text-danger" title="Obbligatorio: almeno un metodo">*</span></h6>
                <p class="text-muted">
                    Metodi accettati, proposti nei form di iscrizione ai corsi (pubblico e admin). Nessun pagamento
                    online diretto: il comitato incassa e fattura. Attiva quelli che accetti (di base Bonifico e
                    Contanti). Disattivare un metodo non modifica le iscrizioni già registrate.
                </p>
                <input type="hidden" name="metodi_pagamento_inviati" value="1">
                @php $attivi = old('metodi_pagamento', $info->metodiPagamentoCodici()); @endphp
                @foreach (config('pagamenti.metodi') as $codice => $def)
                    <div class="form-check form-check-inline">
                        <input type="checkbox" name="metodi_pagamento[]" id="metodo_{{ $codice }}" value="{{ $codice }}" class="form-check-input"
                            @checked(in_array($codice, $attivi, true))>
                        <label class="form-check-label" for="metodo_{{ $codice }}">{{ $def['label'] }}@if ($codice === 'bonifico') <small class="text-muted">— mostra le coordinate bancarie a chi lo sceglie</small>@endif</label>
                    </div>
                @endforeach
                @error('metodi_pagamento') <div class="text-danger small mt-2">{{ $message }}</div> @enderror
            </div>
        </div>

        {{-- Da 1200 px le ultime due card (poco contenuto) stanno affiancate; le altre restano a tutta larghezza. --}}
        <div class="row">
            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header"><strong>Statistiche</strong></div>
                    <div class="card-body">
                        <p class="text-muted">
                            Interruttore rapido per spegnere temporaneamente uno strumento di statistica senza
                            toccare il file di configurazione del server. Ha effetto solo se lo strumento è già
                            configurato lì (altrimenti la spunta non fa comparire nulla).
                        </p>
                        <div class="form-group">
                            <label class="mb-0">
                                <input type="checkbox" name="goatcounter_enabled" value="1" {{ old('goatcounter_enabled', $info->goatcounter_enabled) ? 'checked' : '' }}>
                                GoatCounter attivo
                            </label>
                            @unless (config('tracking.cookieless.src'))
                                <small class="text-muted d-block">Non configurato nel `.env` del server — la spunta non ha effetto finché non lo è.</small>
                            @endunless
                        </div>
                        <div class="form-group mb-0">
                            <label class="mb-0">
                                <input type="checkbox" name="ga_enabled" value="1" {{ old('ga_enabled', $info->ga_enabled) ? 'checked' : '' }}>
                                Google Analytics attivo
                            </label>
                            @unless (config('tracking.ga.id'))
                                <small class="text-muted d-block">Non configurato nel `.env` del server — la spunta non ha effetto finché non lo è.</small>
                            @endunless
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header"><strong>Area soci</strong></div>
                    <div class="card-body">
                        <p class="text-muted">
                            Interruttore generale dell'area riservata ai soci (accesso con codice via email, comunicazioni
                            interne, elenco membri). <strong>Spenta</strong>: il pulsante nel sito, le pagine riservate e le
                            relative voci del pannello (Membri, Comunicazioni soci) scompaiono. Non viene cancellato nulla:
                            riaccendendola ritrovi tutto com'era.
                        </p>
                        <div class="form-group mb-0">
                            <label class="mb-0">
                                <input type="checkbox" name="area_soci_attiva" value="1" {{ old('area_soci_attiva', $info->area_soci_attiva) ? 'checked' : '' }}>
                                Area soci attiva
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">Salva</button>
    </form>
@stop
