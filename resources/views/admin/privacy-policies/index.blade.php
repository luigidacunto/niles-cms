@extends('adminlte::page')

@section('title', 'Informative privacy')

@section('content_header')
    <h1>Informative privacy</h1>
@stop

@section('content')
    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <p class="text-muted">Ogni tipologia ha un testo predefinito (sempre presente) ed eventualmente una versione personalizzata che lo sostituisce.</p>

    <div class="card">
        <div class="card-body table-responsive p-0">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Tipologia</th>
                        <th>Stato</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($tipi as $t)
                        <tr>
                            <td>{{ $t['etichetta'] }} <code class="text-muted">{{ $t['tipo'] }}</code></td>
                            <td>
                                @if ($t['ha_override'])
                                    <span class="badge badge-success">Personalizzata</span>
                                @else
                                    <span class="badge badge-secondary">Predefinita</span>
                                @endif
                            </td>
                            <td class="text-right">
                                <a href="{{ route('admin.privacy-policies.edit', $t['tipo']) }}" class="btn btn-sm btn-outline-secondary">
                                    <i class="fas fa-pen"></i> Gestisci
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@stop
