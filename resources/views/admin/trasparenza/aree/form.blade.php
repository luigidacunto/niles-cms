@extends('adminlte::page')

@php $editing = $category->exists; @endphp

@section('title', $editing ? 'Modifica area' : 'Nuova area')

@section('content_header')
    <h1>{{ $editing ? 'Modifica area' : 'Nuova area' }}</h1>
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

    <form method="POST" action="{{ $editing ? route('admin.trasparenza-aree.update', $category) : route('admin.trasparenza-aree.store') }}">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="card">
            <div class="card-body">
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Nome</label>
                        <input name="name" value="{{ old('name', $category->name) }}" class="form-control" required>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Slug</label>
                        <input name="slug" value="{{ old('slug', $category->slug) }}" class="form-control" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-8">
                        <label>Descrizione <small class="text-muted">(opzionale, mostrata sotto il titolo dell'area)</small></label>
                        <textarea name="description" class="form-control" rows="2">{{ old('description', $category->description) }}</textarea>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Ordine</label>
                        <input name="order" type="number" min="0" value="{{ old('order', $category->order ?? 0) }}" class="form-control">
                    </div>
                </div>
            </div>

            <div class="card-footer">
                <button type="submit" class="btn btn-primary">{{ $editing ? 'Salva' : 'Crea area' }}</button>
                <a href="{{ route('admin.trasparenza-aree.index') }}" class="btn btn-link">Annulla</a>
            </div>
        </div>
    </form>
@stop
