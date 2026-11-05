@extends('adminlte::page')

@section('title', 'Pagamenti corsi')

@section('content_header')
    <h1>Pagamenti corsi</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Corso</th>
                        <th>Data</th>
                        <th class="text-right">Quota</th>
                        <th class="text-center">Fatturazioni</th>
                        <th class="text-center">Da fatturare</th>
                        <th class="text-center">Da incassare</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($corsi as $corso)
                        @php($r = $riepilogo[$corso->id])
                        <tr>
                            <td>
                                {{ $corso->tipologia->nome }} <code>{{ $corso->protocollo }}</code>
                                @if ($corso->annullato()) <span class="badge badge-danger">Annullato</span> @endif
                            </td>
                            <td>{{ $corso->periodoLabel() }}</td>
                            <td class="text-right">{{ number_format($corso->costo, 2, ',', '.') }} €</td>
                            <td class="text-center">{{ $r['blocchi'] }}</td>
                            <td class="text-center">@if ($r['da_fatturare'])<span class="badge badge-warning">{{ $r['da_fatturare'] }}</span>@else<span class="text-muted">—</span>@endif</td>
                            <td class="text-center">@if ($r['da_incassare'])<span class="badge badge-warning">{{ $r['da_incassare'] }}</span>@else<span class="text-muted">—</span>@endif</td>
                            <td class="text-right"><a href="{{ route('admin.pagamenti-corsi.show', $corso) }}" class="btn btn-sm btn-primary">Apri</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">Nessun corso a pagamento.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($corsi->hasPages())
            <div class="card-footer">{{ $corsi->links() }}</div>
        @endif
    </div>
@stop
