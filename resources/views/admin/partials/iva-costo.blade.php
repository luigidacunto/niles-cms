{{-- IVA del corso/tipologia: $ivaPrevista (bool), $aliquota (valore), $costo (valore attuale), $costoId (id del campo costo), $avviso (facoltativo). Il costo è sempre il prezzo finale.
     Con costo 0 l'interruttore resta visibile (la riga non cambia) ma disabilitato: aggiornaIva() lo riallinea a ogni modifica del costo. --}}
@php($costoZero = (float) ($costo ?? 0) <= 0)
@php($ivaPrevista = $ivaPrevista && ! $costoZero)
<div class="form-group col-md-{{ $colonne ?? 6 }}">
    <label>IVA
        <x-admin.aiuto>Non cambia il prezzo mostrato agli iscritti: serve a scorporare l'IVA nella guida alla fattura. Con costo 0 non è attivabile.</x-admin.aiuto>
    </label>
    <div class="d-flex align-items-center" style="min-height: calc(2.25rem + 2px)">
        <div class="custom-control custom-switch mr-3">
            <input type="hidden" name="iva_prevista" id="iva_prevista_zero" value="0" @disabled($costoZero)>
            <input type="checkbox" class="custom-control-input" id="iva_prevista" name="iva_prevista" value="1" @checked($ivaPrevista) @disabled($costoZero)
                onchange="aggiornaIva()">
            <label class="custom-control-label" for="iva_prevista">Il costo è comprensivo di IVA</label>
        </div>
        <div id="aliquota-iva-gruppo" class="input-group" style="width: 130px" @unless($ivaPrevista) hidden @endunless>
            <input name="aliquota_iva" id="aliquota_iva" type="number" step="0.01" min="0.01" max="99.99" aria-label="Aliquota IVA"
                value="{{ $aliquota ?? \App\Support\IvaCorso::ALIQUOTA_PREDEFINITA }}" class="form-control">
            <div class="input-group-append"><span class="input-group-text">%</span></div>
        </div>
    </div>
    @if (! empty($avviso))
        <div class="alert alert-info mt-2 mb-0 py-2">{{ $avviso }}</div>
    @endif
</div>

@push('js')
<script>
    // Costo 0 → IVA non attivabile (interruttore disabilitato e spento); altrimenti si riabilita.
    function aggiornaIva() {
        var attivo = (parseFloat(document.getElementById('{{ $costoId }}').value) || 0) > 0;
        var sw = document.getElementById('iva_prevista');
        sw.disabled = !attivo;
        document.getElementById('iva_prevista_zero').disabled = !attivo;
        if (!attivo) { sw.checked = false; }
        document.getElementById('aliquota-iva-gruppo').hidden = !sw.checked;
    }
    document.getElementById('{{ $costoId }}').addEventListener('input', aggiornaIva);
</script>
@endpush
