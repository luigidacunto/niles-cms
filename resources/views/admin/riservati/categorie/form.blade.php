@extends('adminlte::page')

@php $editing = $category->exists; @endphp

@section('title', $editing ? 'Modifica categoria riservata' : 'Nuova categoria riservata')

@section('content_header')
    <h1>{{ $editing ? 'Modifica categoria riservata' : 'Nuova categoria riservata' }}</h1>
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

    <form method="POST" action="{{ $editing ? route('admin.categorie-riservate.update', $category) : route('admin.categorie-riservate.store') }}">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="card">
            <div class="card-body">
                <div class="form-row">
                    <div class="form-group col-md-8">
                        <label>Nome</label>
                        <input name="name" value="{{ old('name', $category->name) }}" class="form-control" required>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Ordine</label>
                        <input name="order" type="number" min="0" value="{{ old('order', $category->order ?? 0) }}" class="form-control">
                    </div>
                </div>
                <div class="form-group mb-0">
                    <label>Descrizione <small class="text-muted">(opzionale, solo per te)</small></label>
                    <textarea name="description" class="form-control" rows="2">{{ old('description', $category->description) }}</textarea>
                </div>
                <small class="text-muted d-block mt-2">I soci che la vedono si scelgono nella scheda di ciascun socio.</small>
            </div>

            <div class="card-footer">
                <button type="submit" class="btn btn-primary">{{ $editing ? 'Salva' : 'Crea categoria' }}</button>
                <a href="{{ route('admin.categorie-riservate.index') }}" class="btn btn-link">Annulla</a>
            </div>
        </div>
    </form>
@stop
