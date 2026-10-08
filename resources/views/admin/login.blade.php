@extends('adminlte::auth.auth-page', ['authType' => 'login'])

@section('auth_header', 'Accedi con password')

@section('auth_body')
    <p class="login-box-msg">Solo per gli account abilitati dall'amministratore.</p>

    <form action="{{ route('admin.login.password.confirm') }}" method="post">
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

        <div class="input-group mb-3">
            <input type="password" name="password" class="form-control @error('password') is-invalid @enderror"
                   placeholder="Password" required>
            <div class="input-group-append">
                <div class="input-group-text"><span class="fas fa-lock"></span></div>
            </div>
            @error('password')
                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
            @enderror
        </div>

        <div class="row">
            <div class="col-7">
                <div class="custom-control custom-switch">
                    <input type="checkbox" class="custom-control-input" name="remember" id="remember">
                    <label for="remember" class="custom-control-label">Ricordami</label>
                </div>
            </div>
            <div class="col-5">
                <button type="submit" class="btn btn-block btn-flat btn-primary">Accedi</button>
            </div>
        </div>
    </form>
@stop

@section('auth_footer')
    <p class="my-0">
        <a href="{{ route('admin.login') }}">Accedi con codice via email</a>
    </p>
@stop
