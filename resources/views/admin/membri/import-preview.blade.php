@extends('adminlte::page')

@section('title', 'Anteprima import membri')

@section('content_header')
    <h1>Anteprima import — {{ $lista === 'dipendenti' ? 'dipendenti' : 'volontari' }}</h1>
@stop

@php
    $labels = [
        'nuovo' => ['Nuovi', 'success'],
        'cambio_ruolo' => ['Cambio ruolo', 'info'],
        'ripristino' => ['Ripristinati', 'primary'],
        'rimosso' => ['Da rimuovere', 'danger'],
        'errore' => ['Errori', 'warning'],
        'invariato' => ['Invariati', 'secondary'],
    ];
    $ruoli = \App\Models\Member::RUOLI;
@endphp

@section('content')
    @if ($plan['blocking'])
        <div class="alert alert-danger">
            <strong>Import bloccato.</strong> {{ $plan['blocking'] }}
        </div>
        <a href="{{ route('admin.membri.import') }}" class="btn btn-outline-secondary">Torna indietro</a>
    @else
        <div class="mb-3">
            <button type="button" class="btn btn-sm btn-dark filtro-op" data-op="">Tutti ({{ count($plan['ops']) }})</button>
            @foreach ($labels as $op => [$label, $color])
                @if ($plan['counts'][$op] > 0)
                    <button type="button" class="btn btn-sm btn-outline-{{ $color }} filtro-op" data-op="{{ $op }}">{{ $label }} ({{ $plan['counts'][$op] }})</button>
                @endif
            @endforeach
            <input type="text" id="filtro-testo" class="form-control form-control-sm d-inline-block ml-2" style="width:220px" placeholder="Cerca nome o CF…">
        </div>

        <div class="card">
            <div class="card-body table-responsive p-0" style="max-height:60vh">
                <table class="table table-sm table-hover mb-0">
                    <thead>
                        <tr><th>Operazione</th><th>Cognome Nome</th><th>Codice fiscale</th><th>Ruolo</th><th>Note</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($plan['ops'] as $o)
                            <tr data-op="{{ $o['op'] }}" data-text="{{ strtolower($o['cognome'].' '.$o['nome'].' '.$o['cf']) }}">
                                <td><span class="badge badge-{{ $labels[$o['op']][1] }}">{{ $labels[$o['op']][0] }}</span></td>
                                <td>{{ $o['cognome'] }} {{ $o['nome'] }}</td>
                                <td><code>{{ $o['cf'] }}</code></td>
                                <td>
                                    @if ($o['op'] === 'rimosso')
                                        {{ $ruoli[$o['ruolo_da']] ?? $o['ruolo_da'] }}
                                    @elseif ($o['ruolo_da'] && $o['ruolo_da'] !== $o['ruolo_a'])
                                        {{ $ruoli[$o['ruolo_da']] ?? $o['ruolo_da'] }} → <strong>{{ $ruoli[$o['ruolo_a']] ?? $o['ruolo_a'] }}</strong>
                                    @else
                                        {{ $ruoli[$o['ruolo_a']] ?? $o['ruolo_a'] }}
                                    @endif
                                </td>
                                <td class="text-muted small">{{ $o['avviso'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-footer">
                @if ($plan['counts']['errore'] > 0)
                    <p class="text-warning mb-2"><i class="fas fa-exclamation-triangle"></i> Le righe con errori non verranno importate.</p>
                @endif
                <form method="POST" action="{{ route('admin.membri.import.apply') }}" class="d-inline">
                    @csrf
                    <input type="hidden" name="lista" value="{{ $lista }}">
                    <input type="hidden" name="token" value="{{ $token }}">
                    <button type="submit" class="btn btn-primary"
                        @if ($plan['counts']['rimosso'] > 0) onclick="return confirm('{{ $plan['counts']['rimosso'] }} membri verranno rimossi. Procedere?');" @endif>
                        Applica import
                    </button>
                </form>
                <a href="{{ route('admin.membri.import') }}" class="btn btn-outline-secondary">Annulla</a>
            </div>
        </div>
    @endif
@stop

@push('js')
    <script>
        $(function () {
            var op = '', text = '';
            function apply() {
                $('tbody tr').each(function () {
                    var show = (!op || $(this).data('op') === op) && (!text || String($(this).data('text')).indexOf(text) !== -1);
                    $(this).toggle(show);
                });
            }
            $('.filtro-op').on('click', function () { op = $(this).data('op'); apply(); });
            $('#filtro-testo').on('input', function () { text = this.value.toLowerCase(); apply(); });
        });
    </script>
@endpush
