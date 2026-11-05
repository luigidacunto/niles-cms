{{-- Select del metodo di pagamento (pubblico, con ricerca). Stesso $base di blocco-fatturazione: espressione JS del nome del blocco. --}}
@php($metodi = \App\Models\CommitteeInfo::current()->metodi_pagamento)
<div x-data="{ metodo: @js($metodi[0] ?? '') }">
    <label class="block text-xs text-gray-500 mb-1">Metodo di pagamento</label>
    <x-select-ricerca model="metodo" :name="$base.' + \'[metodo_pagamento]\''" :options="collect($metodi)->map(fn ($m) => ['v' => $m, 'l' => $m])->all()" />
</div>
