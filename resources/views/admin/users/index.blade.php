@extends('adminlte::page')

@section('title', 'Utenti pannello')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Utenti pannello</h1>
        <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nuovo utente
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
                        <th>Nome</th>
                        <th>Email</th>
                        <th>Ruolo</th>
                        <th>Categorie</th>
                        <th>Password</th>
                        <th>Attivo</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($admins as $item)
                        <tr>
                            <td class="text-nowrap">
                                <x-admin.action-button :href="route('admin.users.edit', $item)" icon="fas fa-pen" label="Modifica" />
                            </td>
                            <td>{{ $item->name }}</td>
                            <td>{{ $item->email }}</td>
                            <td>
                                <span class="badge {{ $item->role === 'admin' ? 'badge-danger' : 'badge-secondary' }}">
                                    {{ $item->role === 'admin' ? 'Admin' : 'Editor' }}
                                </span>
                            </td>
                            <td class="text-muted small">
                                @if ($item->role === 'admin' || $item->all_categories)
                                    Tutte
                                @else
                                    {{ $item->categories->sortBy('name')->pluck('name')->implode(', ') ?: '—' }}
                                @endif
                            </td>
                            <td>{{ $item->password_login_enabled ? 'Abilitata' : '—' }}</td>
                            <td>{{ $item->active ? 'Sì' : 'No' }}</td>
                            <td class="text-right">
                                @unless ($item->is(Auth::guard('admin')->user()) || $item->protected)
                                    <x-admin.danger-actions :action="route('admin.users.destroy', $item)"
                                        confirm="Eliminare questo utente?" />
                                @endunless
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@stop
