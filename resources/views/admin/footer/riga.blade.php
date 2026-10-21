{{-- Una riga del modulo «Link del footer». Variabili: $i (indice), $riga (id, label, url, order), $modello (true = riga del modello JS). --}}
<tr>
    <td>
        <input type="hidden" name="links[{{ $i }}][id]" value="{{ $riga['id'] ?? '' }}">
        <input type="number" min="0" max="9999" name="links[{{ $i }}][order]" value="{{ $riga['order'] ?? 0 }}" class="form-control form-control-sm">
    </td>
    <td><input type="text" maxlength="100" name="links[{{ $i }}][label]" value="{{ $riga['label'] ?? '' }}" class="form-control form-control-sm" placeholder="Es. Bandi di gara (cri.it)"></td>
    <td><input type="text" maxlength="2000" name="links[{{ $i }}][url]" value="{{ $riga['url'] ?? '' }}" class="form-control form-control-sm" placeholder="https://www.cri.it/…"></td>
    <td class="text-center">
        <button type="button" class="btn btn-sm btn-outline-secondary rimuovi-riga" title="Rimuovi questa riga (si applica con Salva)"><i class="fas fa-times"></i></button>
    </td>
</tr>
