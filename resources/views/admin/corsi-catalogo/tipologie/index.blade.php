@extends('adminlte::page')

@section('title', 'Tipologie corso')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Tipologie corso</h1>
        <a href="{{ route('admin.corsi-tipologie.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nuova tipologia
        </a>
    </div>
@stop

@section('content')
    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <p class="text-muted">Catalogo delle tipologie di corso attivabili per la popolazione (es. BLSD). Un editor con permesso "Corsi" ne sceglie una quando crea un nuovo corso.</p>

    <div class="card">
        <div class="card-body table-responsive p-0">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th class="text-nowrap" style="width:1%"></th>
                        <th>Sigla</th>
                        <th>Nome</th>
                        <th>Attestato</th>
                        <th>Costo predefinito</th>
                        <th>Validità attestato</th>
                        <th>Attiva</th>
                        <th>Corsi</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($tipologie as $tipologia)
                        <tr>
                            <td class="text-nowrap">
                                <x-admin.action-button :href="route('admin.corsi-tipologie.edit', $tipologia)" icon="fas fa-pen" label="Modifica" />
                            </td>
                            <td><code>{{ $tipologia->sigla }}</code></td>
                            <td>{{ $tipologia->nome }}</td>
                            <td>@if($tipologia->rilascia_attestato) <span class="badge badge-info">Sì</span> @else <span class="text-muted">No</span> @endif</td>
                            <td>{{ number_format($tipologia->costo_predefinito, 2, ',', '.') }} €</td>
                            <td>{{ $tipologia->validitaLabel() }}</td>
                            <td>@if($tipologia->attivo) <span class="badge badge-success">Attiva</span> @else <span class="badge badge-secondary">Disattivata</span> @endif</td>
                            <td>{{ $tipologia->corsi_count }}</td>
                            <td class="text-right">
                                <x-admin.danger-actions :action="route('admin.corsi-tipologie.destroy', $tipologia)"
                                    confirm="Eliminare questa tipologia? Possibile solo se non ha più corsi collegati." />
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@stop
