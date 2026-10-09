@if (config('app.demo'))
    <div class="alert alert-warning mt-3 mb-3 small">
        <strong>Versione dimostrativa.</strong> Puoi provare il pannello, ma nulla viene salvato.<br>
        Amministratore: {{ config('app.initial_admin.email') }}
        @if (config('app.initial_admin.password'))
            / {{ config('app.initial_admin.password') }}
        @endif
        (accesso con password)
        @foreach (config('app.demo_accounts') as $account)
            <br>{{ $account }}
        @endforeach
    </div>
@endif
