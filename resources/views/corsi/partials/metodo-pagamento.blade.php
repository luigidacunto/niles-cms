{{-- Select del metodo di pagamento (pubblico, con ricerca). $base: espressione JS del nome del blocco (come in blocco-fatturazione);
     $iniziale: espressione JS del valore inviato prima di un errore di validazione (se non è più tra i metodi attivi, vale il primo). --}}
@php($metodi = \App\Models\CommitteeInfo::current()->metodi_pagamento)
<div x-data="{ metodo: (v => @js($metodi).includes(v) ? v : @js($metodi[0] ?? ''))({{ $iniziale }}) }">
    <label class="block text-xs text-gray-500 mb-1">Metodo di pagamento</label>
    <x-select-ricerca model="metodo" :name="$base.' + \'[metodo_pagamento]\''" :options="collect($metodi)->map(fn ($m) => ['v' => $m, 'l' => $m])->all()" />
</div>
