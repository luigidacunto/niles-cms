@extends('adminlte::page')

@section('title', 'Persone')

@section('content_header')
    <h1>Persone <small class="text-muted">anagrafica dei corsi e consensi</small></h1>
@stop

@section('content')
    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <div class="row">
        <div class="col-md-4"><div class="info-box"><span class="info-box-icon bg-secondary"><i class="fas fa-users"></i></span>
            <div class="info-box-content"><span class="info-box-text">Persone in anagrafica</span><span class="info-box-number">{{ $totali['persone'] }}</span></div></div></div>
        <div class="col-md-4"><div class="info-box"><span class="info-box-icon bg-success"><i class="fas fa-envelope-open-text"></i></span>
            <div class="info-box-content"><span class="info-box-text">Consenso newsletter</span><span class="info-box-number">{{ $totali['newsletter'] }}</span></div></div></div>
        <div class="col-md-4"><div class="info-box"><span class="info-box-icon bg-info"><i class="fas fa-bell"></i></span>
            <div class="info-box-content"><span class="info-box-text">Consenso promemoria attestati</span><span class="info-box-number">{{ $totali['promemoria'] }}</span></div></div></div>
    </div>

    @if ($richieste->isNotEmpty())
        <div class="card card-danger card-outline">
            <div class="card-header"><strong>Richieste di cancellazione dei dati in attesa ({{ $richieste->count() }})</strong></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    @foreach ($richieste as $r)
                        <tr>
                            <td><a href="{{ route('admin.persone.show', $r) }}">{{ $r->cognome }} {{ $r->nome }}</a> <span class="text-muted">{{ $r->email }}</span></td>
                            <td class="text-muted">richiesta il {{ $r->richiesta_cancellazione_at->format('d/m/Y H:i') }}</td>
                            <td class="text-right"><a href="{{ route('admin.persone.show', $r) }}" class="btn btn-sm btn-outline-danger">Valuta</a></td>
                        </tr>
                    @endforeach
                </table>
            </div>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <form method="GET" class="form-row align-items-end">
                <div class="form-group col-md-3">
                    <label class="small">Cerca (nome, cognome, codice fiscale, email)</label>
                    <input name="q" value="{{ $filtri['q'] ?? '' }}" class="form-control">
                </div>
                @foreach (['newsletter' => 'Newsletter', 'promemoria' => 'Promemoria'] as $chiave => $etichetta)
                    <div class="form-group col-md-2">
                        <label class="small">{{ $etichetta }}</label>
                        <select name="{{ $chiave }}" class="form-control">
                            <option value="">Tutti</option>
                            @foreach (['confermato' => 'Confermato', 'richiesto' => 'Richiesto (senza risposta)', 'revocato' => 'Revocato', 'nessuno' => 'Nessuna scelta'] as $v => $e)
                                <option value="{{ $v }}" @selected(($filtri[$chiave] ?? '') === $v)>{{ $e }}</option>
                            @endforeach
                        </select>
                    </div>
                @endforeach
                <div class="form-group col-md-3">
                    <label class="small">Cancellazione dati</label>
                    <select name="cancellazione" class="form-control">
                        <option value="">Persone attive</option>
                        <option value="in_attesa" @selected(($filtri['cancellazione'] ?? '') === 'in_attesa')>Richieste in attesa</option>
                        <option value="anonimizzate" @selected(($filtri['cancellazione'] ?? '') === 'anonimizzate')>Anonimizzate</option>
                    </select>
                </div>
                <div class="form-group col-md-2">
                    <button type="submit" class="btn btn-primary">Filtra</button>
                    <a href="{{ route('admin.persone.index') }}" class="btn btn-link">Azzera</a>
                </div>
            </form>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-hover mb-0">
                <thead><tr><th>Persona</th><th>Codice fiscale</th><th>Email</th><th>Corsi</th><th>Newsletter</th><th>Promemoria</th></tr></thead>
                <tbody>
                    @forelse ($persone as $p)
                        <tr>
                            <td><a href="{{ route('admin.persone.show', $p) }}">{{ $p->cognome }} {{ $p->nome }}</a>
                                @if ($p->richiesta_cancellazione_at && ! $p->anonimizzata()) <span class="badge badge-danger">cancellazione richiesta</span> @endif
                                @if ($p->anonimizzata()) <span class="badge badge-secondary">anonimizzata</span> @endif</td>
                            <td><code>{{ $p->codice_fiscale }}</code></td>
                            <td>{{ $p->email ?: '—' }}</td>
                            <td>{{ $p->iscrizioni_count }}</td>
                            @foreach (['newsletter', 'promemoria'] as $finalita)
                                <td>@include('admin.persone.partials.stato', ['stato' => $p->statoConsenso($finalita)])</td>
                            @endforeach
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">Nessuna persona con questi filtri.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($persone->hasPages())<div class="card-footer">{{ $persone->links('pagination::bootstrap-4') }}</div>@endif
    </div>

    <div class="card">
        <div class="card-header"><strong>Esporta i contatti con consenso newsletter confermato (CSV)</strong></div>
        <form method="GET" action="{{ route('admin.persone.esporta') }}">
            <div class="card-body">
                <p class="text-muted mb-2">Solo chi ha confermato la newsletter e non è stato anonimizzato ({{ $totali['newsletter'] }} persone). Scegli le colonne che servono al tuo strumento di invio.</p>
                @foreach ($colonneExport as $chiave => $etichetta)
                    <div class="form-check form-check-inline">
                        <input type="checkbox" name="colonne[]" value="{{ $chiave }}" id="col_{{ $chiave }}" class="form-check-input" @checked(in_array($chiave, ['email', 'nome', 'cognome']))>
                        <label class="form-check-label" for="col_{{ $chiave }}">{{ $etichetta }}</label>
                    </div>
                @endforeach
                <div class="form-group mt-3 col-md-3 pl-0">
                    <label class="small">Separatore</label>
                    <select name="separatore" class="form-control">
                        <option value=";">Punto e virgola (Excel italiano)</option>
                        <option value=",">Virgola</option>
                    </select>
                </div>
            </div>
            <div class="card-footer"><button type="submit" class="btn btn-outline-primary"><i class="fas fa-download"></i> Scarica CSV</button></div>
        </form>
    </div>
@stop
