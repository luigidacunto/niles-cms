{{--
    Dati di fatturazione unica (vedi corsi/iscrizione.blade.php): $base = "'fatturazione'", espressione JS del nome del blocco
    (:name valutato da Alpine). Dopo un errore di validazione i campi ripartono dai valori inviati (old('fatturazione.*')).
    `tipo` è scoped localmente (x-data proprio).
--}}
<div x-data="{ tipo: @js(old('fatturazione.tipo') === 'azienda' ? 'azienda' : 'privato') }">
    <div class="flex gap-4 mb-3 text-sm">
        <label class="flex items-center gap-1">
            <input type="radio" value="privato" x-model="tipo" :name="{{ $base }} + '[tipo]'">
            Privato
        </label>
        <label class="flex items-center gap-1">
            <input type="radio" value="azienda" x-model="tipo" :name="{{ $base }} + '[tipo]'">
            Azienda / associazione / libero professionista
        </label>
    </div>

    <template x-if="tipo === 'privato'">
        <div class="grid sm:grid-cols-2 gap-4 mb-3">
            <div>
                <label class="block text-xs text-gray-500 mb-1">Nome</label>
                <input :name="{{ $base }} + '[nome]'" value="{{ old('fatturazione.nome') }}" class="form-input">
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Cognome</label>
                <input :name="{{ $base }} + '[cognome]'" value="{{ old('fatturazione.cognome') }}" class="form-input">
            </div>
            <div class="sm:col-span-2">
                <label class="block text-xs text-gray-500 mb-1">Codice fiscale</label>
                <input :name="{{ $base }} + '[codice_fiscale]'" value="{{ old('fatturazione.codice_fiscale') }}" maxlength="16" class="form-input uppercase">
            </div>
        </div>
    </template>

    <template x-if="tipo === 'azienda'">
        <div class="grid sm:grid-cols-2 gap-4 mb-3">
            <div class="sm:col-span-2">
                <label class="block text-xs text-gray-500 mb-1">Ragione sociale (o nome, se libero professionista)</label>
                <input :name="{{ $base }} + '[ragione_sociale]'" value="{{ old('fatturazione.ragione_sociale') }}" class="form-input">
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Partita IVA</label>
                <input :name="{{ $base }} + '[partita_iva]'" value="{{ old('fatturazione.partita_iva') }}" maxlength="11" class="form-input">
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Codice fiscale (se diverso, es. associazioni)</label>
                <input :name="{{ $base }} + '[codice_fiscale]'" value="{{ old('fatturazione.codice_fiscale') }}" maxlength="16" class="form-input uppercase">
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Codice destinatario (fattura elettronica)</label>
                <input :name="{{ $base }} + '[codice_destinatario]'" value="{{ old('fatturazione.codice_destinatario') }}" maxlength="7" class="form-input uppercase">
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">PEC</label>
                <input type="email" :name="{{ $base }} + '[pec]'" value="{{ old('fatturazione.pec') }}" class="form-input">
            </div>
            <p class="sm:col-span-2 text-xs text-gray-500">Codice destinatario e PEC sono facoltativi: se non li hai, lasciali vuoti e la fattura sarà disponibile nel tuo cassetto fiscale.</p>
        </div>
    </template>

    <p class="text-xs text-gray-500 mb-1">Indirizzo di fatturazione</p>
    <div class="grid sm:grid-cols-4 gap-4 mb-3">
        <div class="sm:col-span-2">
            <label class="block text-xs text-gray-500 mb-1">Via / largo / piazza / località</label>
            <input :name="{{ $base }} + '[via]'" value="{{ old('fatturazione.via') }}" class="form-input">
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">Comune</label>
            <input :name="{{ $base }} + '[comune]'" value="{{ old('fatturazione.comune') }}" class="form-input">
        </div>
        <div class="grid grid-cols-2 gap-2">
            <div>
                <label class="block text-xs text-gray-500 mb-1">Prov.</label>
                <input :name="{{ $base }} + '[provincia]'" value="{{ old('fatturazione.provincia') }}" maxlength="2" class="form-input">
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">CAP</label>
                <input :name="{{ $base }} + '[cap]'" value="{{ old('fatturazione.cap') }}" maxlength="5" class="form-input">
            </div>
        </div>
    </div>

    @include('corsi.partials.metodo-pagamento', ['base' => $base, 'iniziale' => \Illuminate\Support\Js::from(old('fatturazione.metodo_pagamento'))])
</div>
