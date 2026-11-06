@extends('adminlte::page')

@section('title', 'Guida — '.$pagina['titolo'])

@section('content_header')
    <h1>Guida</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-lg-3">
            <div class="card">
                <div class="card-body p-2 guida-indice">
                    <a href="{{ route('admin.guida.index') }}" class="d-block px-2 py-1 @if ($percorso === null) font-weight-bold @endif">Indice</a>
                    @foreach ($indice as $cartella)
                        <div class="guida-sezione small text-uppercase font-weight-bold px-2 pt-3 pb-1">{{ $cartella['titolo'] }}</div>
                        @foreach ($cartella['pagine'] as $voce)
                            <a href="{{ route('admin.guida.show', $voce['percorso']) }}" class="d-block px-2 py-1 @if ($voce['percorso'] === $percorso) font-weight-bold bg-light @endif">{{ $voce['titolo'] }}</a>
                        @endforeach
                    @endforeach
                </div>
            </div>
        </div>
        <div class="col-lg-9">
            <div class="card">
                <div class="card-body guida">
                    @if ($percorso)
                        <a href="{{ route('admin.guida.pdf', $percorso) }}" class="btn btn-sm btn-outline-secondary float-right ml-3 mb-2"><i class="fas fa-file-pdf"></i> Scarica PDF</a>
                    @endif
                    {!! $pagina['html'] !!}
                </div>
            </div>
        </div>
    </div>
@stop

@push('css')
<style>
    .guida img { max-width: 100%; height: auto; border: 1px solid #dee2e6; border-radius: 4px; margin: .5rem 0; }
    .guida h1 { font-size: 1.8rem; } .guida h2 { font-size: 1.4rem; margin-top: 1.75rem; } .guida h3 { font-size: 1.15rem; margin-top: 1.25rem; }
    .guida blockquote { border-left: 4px solid #dee2e6; padding: .25rem 1rem; color: #555; }
    .guida pre { background: #f4f6f9; padding: .75rem; border-radius: 4px; }
    .guida-indice a { border-radius: 4px; }
    .guida-sezione { color: #cc0000; letter-spacing: .03em; }   /* rosso CRI, come nel sito pubblico */
    @media (min-width: 992px) { .guida-indice { max-height: calc(100vh - 12rem); overflow-y: auto; } }
</style>
@endpush
