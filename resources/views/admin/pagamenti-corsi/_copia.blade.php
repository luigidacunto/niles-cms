{{-- Pulsante «copia» in riga (prima del valore): $testo = valore copiato, $titolo = nome per il tooltip. --}}
<button type="button" class="btn btn-xs btn-link copia py-0" data-testo="{{ $testo }}" title="Copia {{ $titolo }}"><i class="fas fa-copy"></i><span class="sr-only">Copia {{ $titolo }}</span></button>
