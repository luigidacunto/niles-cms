@extends('adminlte::page')

@php $editing = $member->exists; @endphp

@section('title', $editing ? 'Modifica membro' : 'Nuovo membro')

@section('content_header')
    <h1>{{ $editing ? 'Modifica membro' : 'Nuovo membro' }}</h1>
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

    @if ($editing && $member->richiesta_disattivazione_at && ! $member->disabilitato)
        <div class="alert alert-info d-flex justify-content-between align-items-center">
            <span>
                <strong>Il socio ha chiesto la disattivazione del suo accesso</strong> il {{ $member->richiesta_disattivazione_at->format('d/m/Y') }}.
                Per confermarla attiva "Disabilitato" qui sotto e salva.
            </span>
            <button type="submit" form="respingi-richiesta" class="btn btn-sm btn-outline-secondary">Respingi richiesta</button>
        </div>
        <form id="respingi-richiesta" method="POST" action="{{ route('admin.membri.respingi-richiesta', $member) }}" hidden>@csrf @method('PUT')</form>
    @endif

    <div class="card">
        <form method="POST" action="{{ $editing ? route('admin.membri.update', $member) : route('admin.membri.store') }}">
            @csrf
            @if ($editing) @method('PUT') @endif
            <div class="card-body">
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label for="cognome">Cognome *</label>
                        <input type="text" id="cognome" name="cognome" class="form-control" value="{{ old('cognome', $member->cognome) }}" required>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="nome">Nome *</label>
                        <input type="text" id="nome" name="nome" class="form-control" value="{{ old('nome', $member->nome) }}" required>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="data_nascita">Data di nascita</label>
                        <input type="date" id="data_nascita" name="data_nascita" class="form-control" value="{{ old('data_nascita', $member->data_nascita?->toDateString()) }}">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="codice_fiscale">Codice fiscale *</label>
                        <input type="text" id="codice_fiscale" name="codice_fiscale" class="form-control text-uppercase" maxlength="16"
                               value="{{ old('codice_fiscale', $member->codice_fiscale) }}" required>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="ruolo">Ruolo *</label>
                        <select id="ruolo" name="ruolo" class="form-control" required>
                            @foreach (\App\Models\Member::RUOLI as $value => $label)
                                <option value="{{ $value }}" @selected(old('ruolo', $member->ruolo) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="email">Email <small class="text-muted">— usata per il futuro accesso con codice OTP</small></label>
                        <input type="email" id="email" name="email" class="form-control" value="{{ old('email', $member->email) }}">
                    </div>
                    <div class="form-group col-md-3">
                        <label for="telefono">Telefono principale</label>
                        <input type="text" id="telefono" name="telefono" class="form-control" value="{{ old('telefono', $member->telefono) }}">
                    </div>
                    <div class="form-group col-md-3">
                        <label for="telefoni_aggiuntivi">Altri numeri <small class="text-muted">— uno per riga</small></label>
                        <textarea id="telefoni_aggiuntivi" name="telefoni_aggiuntivi" class="form-control" rows="2">{{ old('telefoni_aggiuntivi', implode("\n", $member->telefoni_aggiuntivi ?? [])) }}</textarea>
                    </div>
                </div>
                <div class="custom-control custom-switch">
                    <input type="checkbox" class="custom-control-input" id="disabilitato" name="disabilitato" value="1" @checked(old('disabilitato', $member->disabilitato))>
                    <label class="custom-control-label" for="disabilitato">Disabilitato (il membro resta in anagrafica ma non può accedere all'area riservata)</label>
                </div>
            </div>
            <div class="card-footer d-flex justify-content-between">
                <div>
                    <button type="submit" class="btn btn-primary">Salva</button>
                    <a href="{{ route('admin.membri.index') }}" class="btn btn-outline-secondary">Annulla</a>
                </div>
                @if ($editing)
                    <button type="submit" form="rimuovi-membro" class="btn btn-outline-danger">Rimuovi membro</button>
                @endif
            </div>
        </form>
        @if ($editing)
            <form id="rimuovi-membro" method="POST" action="{{ route('admin.membri.destroy', $member) }}"
                  onsubmit="return confirm('Rimuovere questo membro? Viene disabilitato e nascosto, ma resta recuperabile.');" hidden>@csrf @method('DELETE')</form>
        @endif
    </div>
@stop
