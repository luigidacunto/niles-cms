@extends('adminlte::page')

@section('title', 'Aree Trasparenza')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Aree Trasparenza</h1>
        <a href="{{ route('admin.trasparenza-aree.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nuova area
        </a>
    </div>
@stop

@section('content')
    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <p class="text-muted">
        Le aree in cui sono suddivisi i documenti pubblicati nella pagina Trasparenza
        (es. Sovvenzioni, Consiglio Direttivo). La "tipologia" dei singoli documenti si imposta invece
        direttamente sul documento, come etichetta libera.
    </p>

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
                                <x-admin.action-button :href="route('admin.trasparenza-aree.edit', $category)" icon="fas fa-pen" label="Modifica" />
                            </td>
                            <td>{{ $category->order }}</td>
                            <td>{{ $category->name }}</td>
                            <td><code>{{ $category->slug }}</code></td>
                            <td>{{ $category->documents_count }}</td>
                            <td class="text-right">
                                <x-admin.danger-actions :action="route('admin.trasparenza-aree.destroy', $category)"
                                    confirm="Eliminare questa area? Possibile solo se non ha più documenti." />
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@stop
