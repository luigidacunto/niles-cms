@extends('adminlte::page')

@section('title', 'Iscritti — '.$corso->protocollo)

@php $canWrite = Auth::guard('admin')->user()->hasPermission('corsi', 'write'); @endphp

@section('content_header')
    <div class="d-flex justify-content-between align-items-center flex-wrap">
        <h1>Iscritti — {{ $corso->tipologia->nome }} <small class="text-muted">({{ $corso->protocollo }})</small></h1>
        <div>
            @if ($canWrite)
                <a href="{{ route('admin.corsi.iscritti.create', $corso) }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Nuovo iscritto
                </a>
            @endif
            <a href="{{ route('admin.corsi.iscritti.stampa', $corso) }}" target="_blank" class="btn btn-outline-secondary" title="Elenco con le caselle da spuntare a mano (solo iscritti attivi)">
                <i class="fas fa-print"></i> Stampa elenco
            </a>
            <div class="btn-group">
                {{-- Gli export seguono il filtro scelto sotto (attivi / ritirati / tutti). --}}
                <a href="{{ route('admin.corsi.iscritti.export', [$corso, 'csv', 'stato' => $stato]) }}" class="btn btn-outline-secondary">CSV</a>
                <a href="{{ route('admin.corsi.iscritti.export', [$corso, 'xlsx', 'stato' => $stato]) }}" class="btn btn-outline-secondary">Excel</a>
                <a href="{{ route('admin.corsi.iscritti.export', [$corso, 'pdf', 'stato' => $stato]) }}" class="btn btn-outline-secondary">PDF</a>
            </div>
        </div>
    </div>
@stop

@section('content')
    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <div class="d-flex justify-content-between align-items-center flex-wrap mb-2">
        <div class="btn-group" role="group" aria-label="Filtro iscritti">
            <a href="{{ route('admin.corsi.iscritti.index', $corso) }}" class="btn btn-sm {{ $stato === 'attivi' ? 'btn-primary' : 'btn-outline-primary' }}">Attivi ({{ $conteggi['attivi'] }})</a>
            <a href="{{ route('admin.corsi.iscritti.index', [$corso, 'stato' => 'ritirati']) }}" class="btn btn-sm {{ $stato === 'ritirati' ? 'btn-primary' : 'btn-outline-primary' }}">Ritirati ({{ $conteggi['ritirati'] }})</a>
            <a href="{{ route('admin.corsi.iscritti.index', [$corso, 'stato' => 'tutti']) }}" class="btn btn-sm {{ $stato === 'tutti' ? 'btn-primary' : 'btn-outline-primary' }}">Tutti ({{ $conteggi['attivi'] + $conteggi['ritirati'] }})</a>
        </div>
        <span class="text-muted">
            @if ($corso->posti_max) Posti indicati: {{ $corso->posti_max }} (limite informativo, non bloccante). @endif
        </span>
    </div>

    <div class="card">
        <div class="card-body table-responsive p-0">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Email</th>
                        <th>Telefono</th>
                        <th>Codice fiscale</th>
                        <th>Fatturazione a</th>
                        <th>Metodo pagamento</th>
                        <th>Presenza</th>
                        <th>Iscritto il</th>
                        @if ($canWrite)<th></th>@endif
                    </tr>
                </thead>
                <tbody>
                    @forelse ($iscrizioni as $iscrizione)
                        <tr @class(['text-muted' => $iscrizione->ritirata()])>
                            <td>{{ $iscrizione->nome }} {{ $iscrizione->cognome }}
                                @if ($iscrizione->ritirata())
                                    <span class="badge badge-secondary" title="Ritirato il {{ $iscrizione->ritirata_at->format('d/m/Y H:i') }}{{ $iscrizione->nota_ritiro ? ' — '.$iscrizione->nota_ritiro : '' }}">Ritirato</span>
                                @endif
                            </td>
                            <td>{{ $iscrizione->email }}</td>
                            <td>{{ $iscrizione->telefono }}</td>
                            <td><code>{{ $iscrizione->codice_fiscale }}</code></td>
                            <td>
                                {{ $iscrizione->datiFatturazione->tipo === 'azienda'
                                    ? $iscrizione->datiFatturazione->ragione_sociale
                                    : trim($iscrizione->datiFatturazione->nome.' '.$iscrizione->datiFatturazione->cognome) }}
                            </td>
                            <td>{{ $iscrizione->datiFatturazione->metodo_pagamento ?: '—' }}</td>
                            <td>
                                @if ($iscrizione->presenza_confermata_at)
                                    {{-- Data e ora della conferma nel tooltip (nativo) della pillola. --}}
                                    <span class="badge badge-success" title="Confermata il {{ $iscrizione->presenza_confermata_at->format('d/m/Y \a\l\l\e H:i') }}">Confermata</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>{{ $iscrizione->created_at->format('d/m/Y H:i') }}</td>
                            @if ($canWrite)
                                <td class="text-right text-nowrap">
                                    @if ($iscrizione->ritirata())
                                        <form method="POST" action="{{ route('admin.corsi.iscritti.ripristina', [$corso, $iscrizione]) }}" class="d-inline">
                                            @csrf @method('PUT')
                                            <button class="btn btn-sm btn-outline-success" title="Rimetti tra gli iscritti attivi"><i class="fas fa-undo"></i> Ripristina</button>
                                        </form>
                                    @else
                                        {{-- Non cancella: segna l'iscritto come ritirato (motivo facoltativo), si può ripristinare. --}}
                                        <form method="POST" action="{{ route('admin.corsi.iscritti.ritira', [$corso, $iscrizione]) }}" class="d-inline"
                                            onsubmit="var n = prompt('Ritirare {{ addslashes($iscrizione->nome.' '.$iscrizione->cognome) }} dal corso? Motivo (facoltativo):', ''); if (n === null) { return false; } this.nota.value = n;">
                                            @csrf @method('PUT')
                                            <input type="hidden" name="nota">
                                            <button class="btn btn-sm btn-outline-danger" title="Ritira dal corso (non lo cancella)"><i class="fas fa-user-minus"></i> Ritira</button>
                                        </form>
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $canWrite ? 9 : 8 }}" class="text-center text-muted py-4">
                                {{ $stato === 'ritirati' ? 'Nessun iscritto ritirato.' : 'Nessun iscritto ancora.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <a href="{{ route('admin.corsi.index') }}" class="btn btn-link">Torna ai corsi</a>
@stop
