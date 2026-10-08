@extends('adminlte::page')

@section('title', 'Template email')

@section('content_header')
    <h1>Template email</h1>
@stop

@section('content')
    <p class="text-muted">Le email di sistema inviate dal sito. Ogni tipo ha un testo predefinito (sempre presente) ed eventualmente una versione personalizzata che lo sostituisce.</p>

    <div class="card">
        <div class="card-body table-responsive p-0">
            <table class="table table-hover mb-0">
                <thead><tr><th>Email</th><th>Stato</th><th></th></tr></thead>
                <tbody>
                    @foreach ($tipi as $t)
                        <tr>
                            <td>
                                <a href="{{ route('admin.email-templates.edit', $t['tipo']) }}">{{ $t['etichetta'] }}</a>
                                <code class="ml-1">{{ $t['tipo'] }}</code>
                                <div class="small text-muted">{{ $t['descrizione'] }}</div>
                            </td>
                            <td>
                                @if ($t['ha_override'])
                                    <span class="badge badge-info">Personalizzato</span>
                                @else
                                    <span class="badge badge-secondary">Predefinito</span>
                                @endif
                            </td>
                            <td class="text-right">
                                <a href="{{ route('admin.email-templates.edit', $t['tipo']) }}" class="btn btn-sm btn-outline-primary">Modifica</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@stop
