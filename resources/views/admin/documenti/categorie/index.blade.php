@extends('adminlte::page')

@section('title', 'Categorie documenti')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Categorie documenti</h1>
        <a href="{{ route('admin.document-categories.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nuova categoria
        </a>
    </div>
@stop

@section('content')
    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <p class="text-muted">Le categorie della libreria documenti generica. Le aree della Trasparenza si gestiscono nella sezione Trasparenza.</p>

    <div class="card">
        <div class="card-body table-responsive p-0">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th class="text-nowrap" style="width:1%"></th>
                        <th>Ordine</th>
                        <th>Nome</th>
                        <th>Slug</th>
                        <th>Documenti</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($categories as $category)
                        <tr>
                            <td class="text-nowrap">
                                <x-admin.action-button :href="route('admin.document-categories.edit', $category)" icon="fas fa-pen" label="Modifica" />
                            </td>
                            <td>{{ $category->order }}</td>
                            <td>
                                {{ $category->name }}
                                @unless ($category->selectable) <span class="badge badge-light border"><i class="fas fa-lock"></i> riservata</span> @endunless
                            </td>
                            <td><code>{{ $category->slug }}</code></td>
                            <td>{{ $category->documents_count }}</td>
                            <td class="text-right">
                                @if ($category->selectable)
                                    <x-admin.danger-actions :action="route('admin.document-categories.destroy', $category)"
                                        confirm="Eliminare questa categoria? Possibile solo se non ha più documenti." />
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@stop
