@extends('adminlte::page')

@section('title', $corso->tipologia->nome.' — '.$corso->protocollo)

@section('content_header')
    <h1>{{ $corso->tipologia->nome }} <small class="text-muted">{{ $corso->protocollo }}</small></h1>
@stop

@section('content')
    @if ($corso->annullato())
        <div class="alert alert-danger">
            <strong>Corso annullato</strong> il {{ $corso->annullato_at->format('d/m/Y H:i') }}
            da {{ $corso->annullatoDa?->name ?? '—' }}. Motivo: {{ $corso->motivo_annullamento }}
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3">Protocollo</dt>
                <dd class="col-sm-9"><code>{{ $corso->protocollo }}</code></dd>

                <dt class="col-sm-3">Tipologia</dt>
                <dd class="col-sm-9">{{ $corso->tipologia->nome }}</dd>

                <dt class="col-sm-3">Sede</dt>
                <dd class="col-sm-9">{{ $corso->sedeLabel() ?? '—' }}</dd>

                <dt class="col-sm-3">Data</dt>
                <dd class="col-sm-9">{{ $corso->periodoLabel() }}</dd>

                <dt class="col-sm-3">Costo</dt>
                <dd class="col-sm-9">{{ number_format($corso->costo, 2, ',', '.') }} €</dd>

                <dt class="col-sm-3">Stato</dt>
                <dd class="col-sm-9">
                    {{ $corso->pubblicato ? 'Pubblicato' : 'Bozza' }}
                    @if ($corso->chiuso) — <span class="badge badge-dark">Chiuso (gestione interna)</span> @endif
                    — iscrizioni {{ $corso->iscrizioniAperte() ? 'aperte' : 'chiuse' }}
                </dd>

                <dt class="col-sm-3">Creato da</dt>
                <dd class="col-sm-9">{{ $corso->creatoDa->name }}</dd>
            </dl>
        </div>
        <div class="card-footer">
            <a href="{{ route('admin.corsi.iscritti.index', $corso) }}" class="btn btn-secondary"><i class="fas fa-users"></i> Iscritti</a>
            <a href="{{ route('admin.corsi.index') }}" class="btn btn-link">Torna all'elenco</a>
        </div>
    </div>
@stop
