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
                    <input type="file" name="logo_orizzontale" accept="image/*" class="form-control-file">
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
                    <input type="file" name="logo_verticale" accept="image/*" class="form-control-file">
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
                    <input type="file" name="logo_ifrc" accept="image/*" class="form-control-file">
                </div>
                <div class="form-group col-md-6">
                    <label>Un'Italia che aiuta <small class="text-muted">mostrato nel piè di pagina</small></label>
                    @if ($info->logo_un_italia_url)
                        <div class="mb-2">
                            <img src="{{ $info->logo_un_italia_url }}" alt="" style="max-height:40px" class="d-block mb-1">
                            <label class="mb-0"><input type="checkbox" name="remove_logo_un_italia" value="1"> rimuovi</label>
                        </div>
                    @endif
                    <input type="file" name="logo_un_italia" accept="image/*" class="form-control-file">
                </div>
            </div>
        </div>
    </div>
@endsection
