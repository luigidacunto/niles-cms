@extends('layouts.public')

@section('title', 'Accesso soci — '.config('app.public_name'))

@section('noindex', '1')

@section('content')
    <div class="max-w-md mx-auto px-4 py-10">
        <h1 class="text-2xl font-bold text-gray-900 mb-2">Accesso area soci</h1>
        <p class="text-sm font-semibold text-gray-800 mb-2">L'accesso è consentito solo ai soci del comitato.</p>
        <p class="text-sm text-gray-600 mb-6">Inserisci l'email con cui sei registrato: ti mandiamo un codice, valido 10 minuti.</p>

        <form method="POST" action="{{ route('soci.login.send') }}" class="space-y-4">
            @csrf
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
                       class="w-full rounded border border-gray-300 px-3 py-2 focus:border-[#cc0000] focus:outline-none focus:ring-1 focus:ring-[#cc0000]">
                @error('email')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit" class="w-full rounded bg-[#cc0000] px-4 py-2 font-semibold text-white hover:bg-[#a30000]">
                Invia il codice
            </button>
        </form>

        <p class="mt-4 text-xs text-gray-500">
            Registriamo data, ora e indirizzo IP degli accessi per motivi di sicurezza (12 mesi).
            <a href="{{ route('privacy-policy.show', 'soci') }}" class="underline">Informativa privacy</a>
        </p>
    </div>
@endsection
