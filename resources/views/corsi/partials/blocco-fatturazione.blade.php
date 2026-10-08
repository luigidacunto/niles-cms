{{--
    Blocco riusato in due contesti (vedi corsi/iscrizione.blade.php):
    - fatturazione unica: $base = "'fatturazione'" (stringa JS statica)
    - fatturazione per nominativo, dentro il template x-for: $base = "`nominativi[${index}][fatturazione]`"
      (template literal JS che referenzia `index` dello scope x-for circostante)
    In entrambi i casi il nome del campo finale è un'espressione JS valutata da Alpine (:name), non un
    attributo name statico — necessario perché l'indice del nominativo esiste solo a runtime.
    `tipo` è scoped localmente (x-data proprio), così più blocchi nella stessa pagina non si accavallano.
--}}
<div x-data="{ tipo: 'privato' }">
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
                <input :name="{{ $base }} + '[nome]'" class="form-input">
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Cognome</label>
                <input :name="{{ $base }} + '[cognome]'" class="form-input">
            </div>
            <div class="sm:col-span-2">
                <label class="block text-xs text-gray-500 mb-1">Codice fiscale</label>
                <input :name="{{ $base }} + '[codice_fiscale]'" maxlength="16" class="form-input uppercase">
            </div>
        </div>
    </template>

    <template x-if="tipo === 'azienda'">
        <div class="grid sm:grid-cols-2 gap-4 mb-3">
            <div class="sm:col-span-2">
                <label class="block text-xs text-gray-500 mb-1">Ragione sociale (o nome, se libero professionista)</label>
                <input :name="{{ $base }} + '[ragione_sociale]'" class="form-input">
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Partita IVA</label>
                <input :name="{{ $base }} + '[partita_iva]'" maxlength="11" class="form-input">
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Codice fiscale (se diverso, es. associazioni)</label>
                <input :name="{{ $base }} + '[codice_fiscale]'" maxlength="16" class="form-input uppercase">
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Codice destinatario (fattura elettronica)</label>
                <input :name="{{ $base }} + '[codice_destinatario]'" maxlength="7" class="form-input uppercase">
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">PEC</label>
                <input type="email" :name="{{ $base }} + '[pec]'" class="form-input">
            </div>
            <p class="sm:col-span-2 text-xs text-gray-500">Codice destinatario e PEC sono facoltativi: se non li hai, lasciali vuoti e la fattura sarà disponibile nel tuo cassetto fiscale.</p>
        </div>
    </template>

    <p class="text-xs text-gray-500 mb-1">Indirizzo di fatturazione</p>
    <div class="grid sm:grid-cols-4 gap-4 mb-3">
        <div class="sm:col-span-2">
            <label class="block text-xs text-gray-500 mb-1">Via / largo / piazza / località</label>
            <input :name="{{ $base }} + '[via]'" class="form-input">
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">Comune</label>
            <input :name="{{ $base }} + '[comune]'" class="form-input">
        </div>
        <div class="grid grid-cols-2 gap-2">
            <div>
                <label class="block text-xs text-gray-500 mb-1">Prov.</label>
                <input :name="{{ $base }} + '[provincia]'" maxlength="2" class="form-input">
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">CAP</label>
                <input :name="{{ $base }} + '[cap]'" maxlength="5" class="form-input">
            </div>
        </div>
    </div>

    @include('corsi.partials.metodo-pagamento', ['base' => $base])
</div>
