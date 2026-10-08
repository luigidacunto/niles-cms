@extends('layouts.public')

@section('title', 'Inserisci il codice — '.config('app.public_name'))

@section('noindex', '1')

@section('content')
    <div class="max-w-md mx-auto px-4 py-10">
        <h1 class="text-2xl font-bold text-gray-900 mb-2">Inserisci il codice</h1>
        <p class="text-sm text-gray-600 mb-6">Se <strong>{{ $email }}</strong> corrisponde a un socio, abbiamo inviato un codice di 6 cifre.</p>

        <form method="POST" action="{{ route('soci.login.confirm') }}" class="space-y-4">
            @csrf
            <div>
                <label for="code" class="block text-sm font-medium text-gray-700 mb-1">Codice</label>
                <input type="text" id="code" name="code" inputmode="numeric" pattern="[0-9]*" maxlength="6" required autofocus
                       autocomplete="one-time-code" placeholder="000000"
                       class="w-full rounded border border-gray-300 px-3 py-2 text-center text-2xl tracking-[.5em] focus:border-[#cc0000] focus:outline-none focus:ring-1 focus:ring-[#cc0000]">
                @error('code')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit" class="w-full rounded bg-[#cc0000] px-4 py-2 font-semibold text-white hover:bg-[#a30000]">
                Accedi
            </button>
        </form>

        <p class="mt-4 text-sm"><a href="{{ route('soci.login') }}" class="text-[#cc0000] hover:underline">Richiedi un nuovo codice</a></p>
    </div>
@endsection
