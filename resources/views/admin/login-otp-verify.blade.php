@extends('adminlte::auth.auth-page', ['authType' => 'login'])

@section('auth_header', 'Inserisci il codice')

@section('auth_body')
    <p class="login-box-msg">Inviato a <strong>{{ $email }}</strong>, se corrisponde a un account.</p>

    <form action="{{ route('admin.login.otp.confirm') }}" method="post">
        @csrf

        <div class="input-group mb-3">
            <input type="text" name="code" inputmode="numeric" pattern="[0-9]*" maxlength="6"
                   class="form-control text-center @error('code') is-invalid @enderror"
                   style="letter-spacing: .5em; font-size: 1.4rem;"
                   value="" placeholder="000000" autofocus required>
            <div class="input-group-append">
                <div class="input-group-text"><span class="fas fa-key"></span></div>
            </div>
            @error('code')
                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
            @enderror
        </div>

        <button type="submit" class="btn btn-block btn-flat btn-primary">Verifica</button>
    </form>
@stop

@section('auth_footer')
    @include('partials.demo-credenziali')
    <p class="my-0">
        <a href="{{ route('admin.login') }}">Richiedi un nuovo codice</a>
    </p>
@stop
