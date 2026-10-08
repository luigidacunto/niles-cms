@extends('adminlte::page')

@php $editing = $category->exists; @endphp

@section('title', $editing ? 'Modifica categoria' : 'Nuova categoria')

@section('content_header')
    <h1>{{ $editing ? 'Modifica categoria' : 'Nuova categoria' }}</h1>
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

    <form method="POST" action="{{ $editing ? route('admin.categories.update', $category) : route('admin.categories.store') }}">
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
                        <label>Descrizione</label>
                        <textarea name="description" class="form-control" rows="3">{{ old('description', $category->description) }}</textarea>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Ordine</label>
                        <input name="order" type="number" min="0" value="{{ old('order', $category->order ?? 0) }}" class="form-control">
                    </div>
                </div>
            </div>

            <div class="card-footer">
                <button type="submit" class="btn btn-primary">{{ $editing ? 'Salva' : 'Crea categoria' }}</button>
                <a href="{{ route('admin.categories.index') }}" class="btn btn-link">Annulla</a>
            </div>
        </div>
    </form>
@stop
