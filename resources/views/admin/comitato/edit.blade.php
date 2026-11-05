@extends('admin.comitato.layout', ['titolo' => 'Dati del comitato', 'azione' => route('admin.comitato.update')])

@section('campi')
    <p class="text-muted">
    Questi dati compaiono nel footer del sito pubblico (blocco Contatti). Lascia vuoto un campo per
    non mostrarlo.
    </p>

    <div class="card">
        <div class="card-header"><strong>Anagrafica e contatti</strong></div>
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
@endsection
