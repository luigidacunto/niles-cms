@extends('adminlte::page')

@section('title', 'Importa membri')

@section('content_header')
    <h1>Importa membri da Excel</h1>
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

    <div class="card">
        <form method="POST" action="{{ route('admin.membri.import.preview') }}" enctype="multipart/form-data">
            @csrf
            <div class="card-body">
                <p class="text-muted">
                    Il file Excel è la fonte di verità della lista scelta: i membri di quella lista che non compaiono
                    nel file vengono rimossi (recuperabili). Prima di applicare vedrai un'anteprima di tutte le modifiche.
                    Le due liste sono indipendenti: importare i dipendenti non tocca i volontari e viceversa.
                </p>
                <div class="form-group">
                    <label for="lista">Che tipo di file stai caricando? *</label>
                    <select id="lista" name="lista" class="form-control" required>
                        <option value="">— scegli —</option>
                        <option value="dipendenti" @selected(old('lista') === 'dipendenti')>Elenco dipendenti</option>
                        <option value="volontari" @selected(old('lista') === 'volontari')>Elenco volontari (e volontari in estensione)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="file">File Excel (.xlsx) *</label>
                    <input type="file" id="file" name="file" class="form-control-file" accept=".xlsx" required>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">Carica e mostra anteprima</button>
                <a href="{{ route('admin.membri.index') }}" class="btn btn-outline-secondary">Annulla</a>
            </div>
        </form>
    </div>
@stop
