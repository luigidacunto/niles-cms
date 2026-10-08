@extends('adminlte::page')

@section('title', 'Persona: '.$persona->cognome.' '.$persona->nome)

@section('content_header')
    <h1>{{ $persona->cognome }} {{ $persona->nome }}
        @if ($persona->anonimizzata()) <span class="badge badge-secondary">anonimizzata</span> @endif
    </h1>
@stop

@section('content')
    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <p><a href="{{ route('admin.persone.index') }}">&larr; Tutte le persone</a></p>

    @if ($persona->richiesta_cancellazione_at && ! $persona->anonimizzata())
        <div class="alert alert-danger">
            <strong>Richiesta di cancellazione dei dati</strong> ricevuta il {{ $persona->richiesta_cancellazione_at->format('d/m/Y H:i') }}.
            I consensi sono già stati revocati. Valuta e, se confermi, usa «Anonimizza i dati» in fondo alla pagina.
        </div>
    @endif

    <div class="row">
        <div class="col-md-6">
            <div class="card"><div class="card-header"><strong>Dati</strong></div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Codice fiscale</dt><dd class="col-sm-8"><code>{{ $persona->codice_fiscale }}</code></dd>
                        <dt class="col-sm-4">Email</dt><dd class="col-sm-8">{{ $persona->email ?: '—' }}</dd>
                        <dt class="col-sm-4">Telefono</dt><dd class="col-sm-8">{{ $persona->cellulare ?: '—' }}</dd>
                        <dt class="col-sm-4">In anagrafica dal</dt><dd class="col-sm-8">{{ $persona->created_at->format('d/m/Y') }}</dd>
                        @if ($persona->anonimizzata())
                            <dt class="col-sm-4">Anonimizzata il</dt><dd class="col-sm-8">{{ $persona->anonimizzata_at->format('d/m/Y H:i') }}</dd>
                        @endif
                    </dl>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card"><div class="card-header"><strong>Consensi</strong></div>
                <div class="card-body">
                    @foreach (['newsletter' => 'Newsletter e inviti a eventi', 'promemoria' => 'Promemoria scadenza attestati'] as $finalita => $etichetta)
                        <div class="d-flex justify-content-between align-items-center py-1">
                            <div>{{ $etichetta }}: @include('admin.persone.partials.stato', ['stato' => $persona->statoConsenso($finalita)])
                                @if ($persona->{$finalita.'_stato_at'}) <small class="text-muted">dal {{ $persona->{$finalita.'_stato_at'}->format('d/m/Y') }}</small> @endif</div>
                            @if (in_array($persona->statoConsenso($finalita), ['confermato', 'richiesto'], true) && ! $persona->anonimizzata() && auth('admin')->user()->hasPermission('corsi', 'write'))
                                <form method="POST" action="{{ route('admin.persone.revoca', [$persona, $finalita]) }}" onsubmit="return confirm('Revocare questo consenso?');">
                                    @csrf <button class="btn btn-sm btn-outline-secondary">Revoca</button>
                                </form>
                            @endif
                        </div>
                    @endforeach
                    <small class="text-muted d-block mt-2">Puoi revocare un consenso, ma non concederlo al posto della persona: lo dà solo lei, dal modulo o dalla sua pagina personale.</small>
                </div>
            </div>
        </div>
    </div>

    <div class="card"><div class="card-header"><strong>Corsi frequentati</strong></div>
        <div class="card-body p-0">
            <table class="table table-sm mb-0">
                @forelse ($iscrizioni as $i)
                    <tr>
                        <td>{{ $i->corso->tipologia->nome }} <code>{{ $i->corso->protocollo }}</code></td>
                        <td>{{ $i->corso->periodoLabel() }}</td>
                        <td>@if ($i->ritirata()) <span class="badge badge-secondary">ritirato dal corso</span> @elseif ($i->presenza_confermata_at) <span class="badge badge-success">presenza confermata</span> @endif
                            @if ($i->referente) <span class="text-muted small">iscritta da {{ $i->referente->nome }} {{ $i->referente->cognome }}</span> @endif</td>
                    </tr>
                @empty
                    <tr><td class="text-muted p-3">Nessuna iscrizione.</td></tr>
                @endforelse
            </table>
        </div>
    </div>

    @if ($comeReferente->isNotEmpty())
        <div class="card"><div class="card-header"><strong>Ha iscritto altre persone ({{ $comeReferente->count() }})</strong></div>
            <div class="card-body p-0"><table class="table table-sm mb-0">
                @foreach ($comeReferente as $i)
                    <tr><td>{{ $i->corso->tipologia->nome }} <code>{{ $i->corso->protocollo }}</code></td><td>{{ $i->nome }} {{ $i->cognome }}</td></tr>
                @endforeach
            </table></div>
        </div>
    @endif

    <div class="card"><div class="card-header"><strong>Storico dei consensi</strong></div>
        <div class="card-body p-0">
            <table class="table table-sm mb-0">
                @forelse ($storico as $c)
                    <tr>
                        <td class="text-nowrap">{{ $c->created_at->format('d/m/Y H:i') }}</td>
                        <td>{{ config('consensi.'.$c->finalita.'.label', $c->finalita) }}</td>
                        <td>@include('admin.persone.partials.stato', ['stato' => $c->evento])</td>
                        <td class="text-muted small">{{ str_replace('_', ' ', $c->origine) }}</td>
                    </tr>
                @empty
                    <tr><td class="text-muted p-3">Nessun evento registrato.</td></tr>
                @endforelse
            </table>
        </div>
    </div>

    @if (! $persona->anonimizzata() && auth('admin')->user()->role === 'admin')
        <div class="card card-danger card-outline">
            <div class="card-header"><strong>Cancellazione dei dati</strong></div>
            <form method="POST" action="{{ route('admin.persone.anonimizza', $persona) }}"
                onsubmit="return confirm('Anonimizzare definitivamente i dati di questa persona? L\'operazione non si può annullare.');">
                @csrf
                <div class="card-body">
                    <p><strong>Cosa succede:</strong> nome, cognome, codice fiscale, email e telefono della persona, e le copie nelle sue iscrizioni
                    (con la residenza), diventano anonimi. Il registro dei consensi viene cancellato e il suo link personale smette di funzionare.
                    Le iscrizioni restano come presenze anonime.</p>
                    <p><strong>Cosa NON viene toccato:</strong> i dati di fatturazione, che vanno conservati per le fatture emesse.</p>
                    <div class="form-check">
                        <input type="checkbox" name="conferma" value="1" id="conferma" class="form-check-input">
                        <label class="form-check-label" for="conferma">Ho capito: l'operazione è irreversibile.</label>
                    </div>
                </div>
                <div class="card-footer"><button class="btn btn-danger">Anonimizza i dati</button></div>
            </form>
        </div>
    @endif
@stop
