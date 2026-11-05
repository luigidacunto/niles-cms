@extends('admin.comitato.layout', ['titolo' => 'Coordinate e pagamenti', 'azione' => route('admin.comitato.pagamenti.update')])

@section('campi')
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
@endsection
