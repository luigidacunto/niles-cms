@extends('admin.comitato.layout', ['titolo' => 'Loghi e immagini', 'azione' => route('admin.comitato.loghi.update')])

@section('campi')
    <div class="card">
        <div class="card-header"><strong>Logo</strong></div>
        <div class="card-body">
            <p class="text-muted">
                L'orizzontale è quello mostrato nell'intestazione del sito, a tutte le risoluzioni.
                Il verticale è un campo pronto per usi futuri (non ancora mostrato da nessuna parte
                del sito pubblico) — puoi caricarlo comunque, resta salvato e riutilizzabile.
            </p>
            <div class="form-row">
                <div class="form-group col-md-6">
                    <details class="mb-2">
                        <summary class="text-info font-weight-bold" style="cursor:pointer">Come preparare il logo orizzontale</summary>
                        <div class="callout callout-info mt-2 mb-2">
                            <p class="mb-2">
                                L'intestazione ingrandisce l'immagine al doppio dell'altezza e ne mostra solo la parte
                                centrale: il margine vuoto attorno al logo viene ritagliato via. Per questo un logo
                                disegnato fino ai bordi risulterebbe tagliato.
                            </p>
                            <ul class="mb-0">
                                <li>Formato: PNG con sfondo trasparente, circa 700&times;220 px (rapporto 3:1&ndash;3,2:1).</li>
                                <li>
                                    Si può caricare anche un file <strong>SVG</strong>: il browser lo converte in PNG (fino a 1200 px sul lato lungo,
                                    sfondo trasparente) prima dell'invio, quindi sul sito non arriva mai un SVG. Valgono gli stessi margini:
                                    con i loghi ufficiali «con area di rispetto» sono già corretti.
                                </li>
                                <li>
                                    Simbolo e scritte nell'area centrale, lasciando libero almeno il
                                    <strong>30% in alto, il 20% in basso e l'8% a destra e a sinistra</strong>
                                    (su 700&times;220 px: 66 px sopra, 44 px sotto, 56 px per lato; area utile circa 588&times;110 px).
                                    Per il logo di riferimento sono circa il 33% sopra, il 22% sotto e il 10% per lato.
                                </li>
                                <li>Un logo molto più largo che alto (oltre 5:1) va ridotto in larghezza, non allungato.</li>
                                <li>Non togliere mai pixel al logo dell'ente: aggiungi il margine vuoto attorno, non ritagliarlo.</li>
                            </ul>
                        </div>
                    </details>
                    <label>Orizzontale <small class="text-muted">PNG trasparente, circa 700&times;220 px</small></label>
                    @if ($info->logo_orizzontale_url)
                        <div class="mb-2">
                            <img src="{{ $info->logo_orizzontale_url }}" alt="" style="max-height:70px" class="d-block mb-1">
                            <label class="mb-0"><input type="checkbox" name="remove_logo_orizzontale" value="1"> rimuovi</label>
                        </div>
                    @endif
                    <input type="file" name="logo_orizzontale" accept="image/*,.svg" data-svg-png class="form-control-file">
                </div>
                <div class="form-group col-md-6">
                    <details class="mb-2">
                        <summary class="text-info font-weight-bold" style="cursor:pointer">Come preparare il logo verticale</summary>
                        <div class="callout callout-info mt-2 mb-2">
                            <p class="mb-2">
                                Non è ancora mostrato sul sito pubblico: caricalo comunque, nel formato giusto, per averlo
                                pronto. L'immagine viene solo ridimensionata, mai ritagliata.
                            </p>
                            <ul class="mb-0">
                                <li>Formato: PNG con sfondo trasparente, quadrato, circa 500&times;500 px.</li>
                                <li>
                                    Logo (simbolo e scritte) centrato, con un margine vuoto di circa
                                    <strong>17% per lato, 24% in alto e 9% in basso</strong>
                                    (su 500&times;500 px: 85 px per lato, 120 px sopra, 45 px sotto; area utile circa 330&times;335 px).
                                </li>
                                <li>Non togliere mai pixel al logo dell'ente: aggiungi il margine vuoto attorno, non ritagliarlo.</li>
                            </ul>
                        </div>
                    </details>
                    <label>Verticale <small class="text-muted">PNG trasparente, circa 500&times;500 px, non ancora mostrato sul sito</small></label>
                    @if ($info->logo_verticale_url)
                        <div class="mb-2">
                            <img src="{{ $info->logo_verticale_url }}" alt="" style="max-height:70px" class="d-block mb-1">
                            <label class="mb-0"><input type="checkbox" name="remove_logo_verticale" value="1"> rimuovi</label>
                        </div>
                    @endif
                    <input type="file" name="logo_verticale" accept="image/*,.svg" data-svg-png class="form-control-file">
                </div>
                <div class="form-group col-md-6">
                    <label>Favicon <small class="text-muted">icona nella scheda del browser; PNG quadrato, consigliato 256&times;256px. Senza file vale l'icona generica</small></label>
                    @if ($info->favicon_url)
                        <div class="mb-2">
                            <img src="{{ $info->favicon_url }}" alt="" style="max-height:32px" class="d-block mb-1">
                            <label class="mb-0"><input type="checkbox" name="remove_favicon" value="1"> rimuovi</label>
                        </div>
                    @endif
                    <input type="file" name="favicon" accept="image/png" class="form-control-file">
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><strong>Loghi istituzionali</strong></div>
        <div class="card-body">
            <p class="text-muted">
                Marchi di terzi, non inclusi nel software: carica i file solo se hai diritto di utilizzarli.
                Senza file, nell'intestazione e nel piè di pagina compaiono due semplici link testuali
                (ifrc.org, cri.it). Le immagini vengono ridimensionate ma mai ritagliate.
            </p>
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>I.F.R.C. <small class="text-muted">mostrato nell'intestazione e nel piè di pagina</small></label>
                    @if ($info->logo_ifrc_url)
                        <div class="mb-2">
                            <img src="{{ $info->logo_ifrc_url }}" alt="" style="max-height:40px" class="d-block mb-1">
                            <label class="mb-0"><input type="checkbox" name="remove_logo_ifrc" value="1"> rimuovi</label>
                        </div>
                    @endif
                    <input type="file" name="logo_ifrc" accept="image/*,.svg" data-svg-png class="form-control-file">
                </div>
                <div class="form-group col-md-6">
                    <label>Un'Italia che aiuta <small class="text-muted">mostrato nel piè di pagina</small></label>
                    @if ($info->logo_un_italia_url)
                        <div class="mb-2">
                            <img src="{{ $info->logo_un_italia_url }}" alt="" style="max-height:40px" class="d-block mb-1">
                            <label class="mb-0"><input type="checkbox" name="remove_logo_un_italia" value="1"> rimuovi</label>
                        </div>
                    @endif
                    <input type="file" name="logo_un_italia" accept="image/*,.svg" data-svg-png class="form-control-file">
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
<script>
// Un SVG scelto in un campo logo viene disegnato su un canvas e sostituito da un PNG prima dell'invio:
// il server riceve solo PNG, mai un SVG. Nel browser l'SVG è un'immagine inerte (nessuno script viene eseguito).
(function () {
    var MAX = 1200;

    function messaggio(input, testo, errore) {
        var box = input.parentNode.querySelector('.svg-png-msg');
        if (!box) {
            box = document.createElement('div');
            box.className = 'svg-png-msg small mt-1';
            input.parentNode.appendChild(box);
        }
        box.className = 'svg-png-msg small mt-1 ' + (errore ? 'text-danger' : 'text-success');
        box.innerHTML = '';
        box.appendChild(document.createTextNode(testo));
        return box;
    }

    function dimensioni(svg) {
        var vb = (svg.getAttribute('viewBox') || '').trim().split(/[\s,]+/).map(parseFloat);
        if (vb.length === 4 && vb[2] > 0 && vb[3] > 0) return { w: vb[2], h: vb[3] };
        var w = parseFloat(svg.getAttribute('width')), h = parseFloat(svg.getAttribute('height'));
        return (w > 0 && h > 0) ? { w: w, h: h } : null;
    }

    function converti(input, file) {
        var lettore = new FileReader();
        lettore.onerror = function () { messaggio(input, 'File non leggibile.', true); input.value = ''; };
        lettore.onload = function () {
            var doc = new DOMParser().parseFromString(lettore.result, 'image/svg+xml');
            var svg = doc.documentElement;
            var d = (svg && svg.nodeName.toLowerCase() === 'svg' && !doc.querySelector('parsererror')) ? dimensioni(svg) : null;
            if (!d) {
                messaggio(input, 'SVG non valido: servono un viewBox oppure width e height.', true);
                input.value = '';
                return;
            }
            var scala = MAX / Math.max(d.w, d.h);
            var w = Math.round(d.w * scala), h = Math.round(d.h * scala);
            svg.setAttribute('width', w);
            svg.setAttribute('height', h);
            var url = URL.createObjectURL(new Blob([new XMLSerializer().serializeToString(svg)], { type: 'image/svg+xml' }));
            var img = new Image();
            img.onerror = function () { URL.revokeObjectURL(url); messaggio(input, 'Impossibile disegnare l\'SVG.', true); input.value = ''; };
            img.onload = function () {
                var canvas = document.createElement('canvas');
                canvas.width = w;
                canvas.height = h;
                canvas.getContext('2d').drawImage(img, 0, 0, w, h);
                URL.revokeObjectURL(url);
                canvas.toBlob(function (blob) {
                    if (!blob) { messaggio(input, 'Conversione non riuscita.', true); input.value = ''; return; }
                    var nome = file.name.replace(/\.svg$/i, '') + '.png';
                    var dt = new DataTransfer();
                    dt.items.add(new File([blob], nome, { type: 'image/png' }));
                    input.files = dt.files;
                    var box = messaggio(input, 'Convertito in PNG (' + w + '×' + h + ' px): ', false);
                    var anteprima = document.createElement('img');
                    anteprima.src = URL.createObjectURL(blob);
                    anteprima.alt = '';
                    anteprima.style.cssText = 'max-height:70px;display:block;margin-top:.25rem;background:#eee';
                    box.appendChild(anteprima);
                }, 'image/png');
            };
            img.src = url;
        };
        lettore.readAsText(file);
    }

    document.querySelectorAll('input[data-svg-png]').forEach(function (input) {
        input.addEventListener('change', function () {
            var vecchio = input.parentNode.querySelector('.svg-png-msg');
            if (vecchio) vecchio.remove();
            var file = input.files && input.files[0];
            if (file && (file.type === 'image/svg+xml' || /\.svg$/i.test(file.name))) converti(input, file);
        });
    });
})();
</script>
@endsection
