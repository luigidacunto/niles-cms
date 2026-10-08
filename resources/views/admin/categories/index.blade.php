@extends('adminlte::page')

@section('title', 'Categorie')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Categorie</h1>
        <a href="{{ route('admin.categories.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nuova categoria
        </a>
    </div>
@stop

@section('content')
    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="card">
        <div class="card-body table-responsive p-0">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th class="text-nowrap" style="width:1%"></th>
                        <th>Ordine</th>
                        <th>Nome</th>
                        <th>Slug</th>
                        <th>Post</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($categories as $category)
                        <tr>
                            <td class="text-nowrap">
                                <x-admin.action-button :href="route('admin.categories.edit', $category)" icon="fas fa-pen" label="Modifica" />
                            </td>
                            <td>{{ $category->order }}</td>
                            <td>{{ $category->name }}</td>
                            <td><code>{{ $category->slug }}</code></td>
                            <td>{{ $category->posts()->count() }}</td>
                            <td class="text-right">
                                <x-admin.danger-actions :action="route('admin.categories.destroy', $category)"
                                    confirm="Eliminare questa categoria? Possibile solo se non ha più post assegnati." />
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@stop
