@if (config('app.demo'))
    <div class="alert alert-warning mt-3 mb-0 small">
        <strong>Versione dimostrativa.</strong> Puoi provare il pannello, ma nulla viene salvato.<br>
        Amministratore: <code>{{ config('app.initial_admin.email') }}</code>
        @if (config('app.initial_admin.password'))
            / <code>{{ config('app.initial_admin.password') }}</code>
        @endif
        (accesso con password)
        @foreach (config('app.demo_accounts') as $account)
            <br>{{ $account }}
        @endforeach
    </div>
@endif
