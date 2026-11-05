@props(['options', 'model', 'name'])
{{-- Select con ricerca per il sito pubblico (Alpine, nessuna libreria in più). Convenzione: ogni select con molte voci ha il campo di
     ricerca. $options = [['v' => valore, 'l' => etichetta], …]; $model = percorso Alpine del valore (es. n.nascita_luogo);
     $name = espressione Alpine del nome del campo inviato. Emette l'evento `scelto` quando l'utente sceglie una voce. --}}
<div {{ $attributes->merge(['class' => 'relative']) }}
    x-data="{
        aperto: false, q: '', opts: @js($options),
        get filtrate() { const q = this.q.trim().toLowerCase(); return q ? this.opts.filter(o => o.l.toLowerCase().includes(q)) : this.opts },
        etichetta() { const o = this.opts.find(o => String(o.v) === String({{ $model }})); return o ? o.l : '' },
        scegli(o) { {{ $model }} = String(o.v); this.aperto = false; this.q = ''; $dispatch('scelto') },
    }"
    @click.outside="aperto = false" @keydown.escape="aperto = false">
    <input type="hidden" :name="{{ $name }}" :value="{{ $model }}">
    <button type="button" class="form-input text-left flex items-center justify-between" :aria-expanded="aperto" aria-haspopup="listbox"
        @click="aperto = !aperto; if (aperto) $nextTick(() => $refs.cerca.focus())">
        <span x-text="etichetta()"></span><span aria-hidden="true" class="text-gray-400 text-xs">▾</span>
    </button>
    <div x-show="aperto" x-cloak class="absolute z-20 mt-1 w-full bg-white border border-gray-300 rounded shadow-lg">
        <input x-ref="cerca" x-model="q" type="search" placeholder="Cerca…" autocomplete="off" aria-label="Cerca nell'elenco"
            class="w-full border-0 border-b border-gray-200 px-3 py-2 text-sm focus:outline-none"
            @keydown.enter.prevent="if (filtrate.length) scegli(filtrate[0])">
        <ul role="listbox" class="max-h-60 overflow-auto">
            <template x-for="o in filtrate" :key="o.v">
                <li role="option" :aria-selected="String(o.v) === String({{ $model }})" @click="scegli(o)"
                    class="px-3 py-2 text-sm cursor-pointer hover:bg-gray-100" :class="String(o.v) === String({{ $model }}) ? 'font-semibold' : ''" x-text="o.l"></li>
            </template>
            <li x-show="!filtrate.length" class="px-3 py-2 text-sm text-gray-500">Nessun risultato</li>
        </ul>
    </div>
</div>
