{{-- Guscio comune delle pagine sulla riga committee_info (dati, loghi, pagamenti, funzioni attive): $titolo, $azione (URL del PUT). --}}
@extends('adminlte::page')

@section('title', $titolo)

@section('content_header')
    <h1>{{ $titolo }}</h1>
@stop

@section('content')
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ $azione }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        @yield('campi')
        <button type="submit" class="btn btn-primary">Salva</button>
    </form>
@stop
