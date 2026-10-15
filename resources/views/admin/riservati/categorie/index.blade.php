@extends('adminlte::page')

@section('title', 'Categorie riservate')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Categorie riservate</h1>
        <a href="{{ route('admin.categorie-riservate.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nuova categoria
        </a>
    </div>
@stop

@section('content')
    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <p class="text-muted">
        Una categoria decide chi vede i suoi documenti: «Senza categoria» è aperta a tutti i soci, le altre solo ai soci a cui
        sono assegnate (dalla scheda del socio, in <a href="{{ route('admin.membri.index') }}">Membri</a>).
    </p>

    <div class="card">
        <div class="card-body table-responsive p-0">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th class="text-nowrap" style="width:1%"></th>
                        <th>Ordine</th>
                        <th>Nome</th>
                        <th>Documenti</th>
                        <th>Soci abilitati</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($categories as $category)
                        <tr>
                            <td class="text-nowrap">
                                @unless ($category->isGenerale())
                                    <x-admin.action-button :href="route('admin.categorie-riservate.edit', $category)" icon="fas fa-pen" label="Modifica" />
                                @endunless
                            </td>
                            <td>{{ $category->isGenerale() ? '—' : $category->order }}</td>
                            <td>
                                {{ $category->label() }}
                                @if ($category->isGenerale()) <span class="badge badge-light border"><i class="fas fa-lock"></i> di sistema</span> @endif
                            </td>
                            <td>{{ $category->documents_count }}</td>
                            <td>{{ $category->isGenerale() ? 'tutti' : $category->members_count }}</td>
                            <td class="text-right">
                                @unless ($category->isGenerale())
                                    <x-admin.danger-actions :action="route('admin.categorie-riservate.destroy', $category)"
                                        confirm="Eliminare questa categoria? Possibile solo se non ha più documenti; le assegnazioni ai soci vengono tolte." />
                                @endunless
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@stop
