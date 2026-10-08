@extends('adminlte::page')

@section('title', 'Informativa: '.$etichetta)

@section('content_header')
    <h1>{{ $etichetta }} <small class="text-muted">{{ $tipo }}</small></h1>
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

    <div class="card mb-4">
        <div class="card-header"><strong>Segnaposto disponibili</strong></div>
        <div class="card-body">
            <p class="text-muted mb-2">Vengono sostituiti con i dati da <a href="{{ route('admin.comitato.edit') }}">Dati del comitato</a> quando la pagina viene mostrata al pubblico.</p>
            <code>{nome_comitato}</code> · <code>{email_comitato}</code> · <code>{telefono_comitato}</code> ·
            <code>{indirizzo_comitato}</code> · <code>{piva_comitato}</code> ·
            <code>{codice_fiscale_comitato}</code> · <code>{pec_comitato}</code>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><strong>Testo predefinito</strong> <span class="text-muted">(di sistema, sola lettura — usato quando non c'è una personalizzazione)</span></div>
        <div class="card-body">
            @if ($default)
                <p class="font-weight-bold">{{ $default->titolo }}</p>
                <div class="border rounded p-3 bg-light" style="max-height:300px; overflow:auto;">{!! $default->testo !!}</div>
            @else
                <p class="text-muted mb-0">Nessun testo predefinito seedato per questa tipologia.</p>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-header"><strong>Personalizzazione</strong></div>
        <form method="POST" action="{{ route('admin.privacy-policies.update', $tipo) }}">
            @csrf
            @method('PUT')
            <div class="card-body">
                <div class="form-group">
                    <label>Titolo</label>
                    <input name="titolo" value="{{ old('titolo', $override->titolo ?? ($default->titolo ?? '')) }}" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Testo (HTML)</label>
                    <textarea name="testo" rows="16" class="form-control" style="font-family: monospace; font-size: 13px;" required>{{ old('testo', $override->testo ?? '') }}</textarea>
                    @unless($override)
                        <small class="text-muted">Vuoto: parti pure copiando il testo predefinito sopra come base, poi modificalo.</small>
                    @endunless
                </div>
            </div>
            <div class="card-footer d-flex justify-content-between">
                <div>
                    <button type="submit" class="btn btn-primary">Salva personalizzazione</button>
                    <a href="{{ route('admin.privacy-policies.index') }}" class="btn btn-link">Torna all'elenco</a>
                </div>
                @if ($override)
                    <form method="POST" action="{{ route('admin.privacy-policies.destroy', $tipo) }}" class="d-inline"
                        onsubmit="return confirm('Rimuovere la personalizzazione? Tornerà in uso il testo predefinito.');">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger">Rimuovi personalizzazione</button>
                    </form>
                @endif
            </div>
        </form>
    </div>
@stop
