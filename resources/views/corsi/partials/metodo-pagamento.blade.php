{{-- Select del metodo di pagamento (pubblico). Stesso $base di blocco-fatturazione: espressione JS del nome del blocco. --}}
<div>
    <label class="block text-xs text-gray-500 mb-1">Metodo di pagamento</label>
    <select :name="{{ $base }} + '[metodo_pagamento]'" class="form-input">
        @foreach (\App\Models\CommitteeInfo::current()->metodi_pagamento as $metodo)
            <option value="{{ $metodo }}">{{ $metodo }}</option>
        @endforeach
    </select>
</div>
