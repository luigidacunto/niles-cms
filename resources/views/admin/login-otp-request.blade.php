@extends('adminlte::auth.auth-page', ['authType' => 'login'])

@section('auth_header', 'Accedi a NILES')

@section('auth_body')
    <p class="login-box-msg">Ti mandiamo un codice via email, valido 10 minuti.</p>

    <form action="{{ route('admin.login.otp.send') }}" method="post">
        @csrf

        <div class="input-group mb-3">
            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                   value="{{ old('email') }}" placeholder="Email" autofocus required>
            <div class="input-group-append">
                <div class="input-group-text"><span class="fas fa-envelope"></span></div>
            </div>
            @error('email')
                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
            @enderror
        </div>

        <button type="submit" class="btn btn-block btn-flat btn-primary">
            <span class="fas fa-paper-plane"></span> Invia il codice
        </button>
    </form>
@stop

@section('auth_footer')
    @include('partials.demo-credenziali')
    <p class="my-0">
        Non ti arriva l'email o non riesci a leggere il codice?
        <a href="{{ route('admin.login.password') }}">Accedi con password</a>
    </p>
@stop
