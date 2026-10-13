# Immagini e file

Dove si caricano immagini e documenti dal pannello, in che formato, con quali dimensioni e come vengono mostrati.

## Regole generali

- Le immagini vengono solo **ridotte** al caricamento (mai ingrandite) e salvate in formato leggibile dal browser. Il file
  non viene mai ritagliato: dove l'immagine è mostrata in un riquadro, il ritaglio è solo visivo e il file resta intero.
- Un ritaglio visivo è sempre **centrato**: il soggetto principale va tenuto al centro dell'immagine.
- Oltre ai limiti dell'applicazione valgono quelli di PHP: `upload_max_filesize` e `post_max_size` devono essere almeno pari
  al file più grande che si vuole caricare (20 MB per i documenti). Se sono più bassi il caricamento fallisce senza un
  messaggio chiaro.
- Per i loghi dell'ente non si tolgono mai pixel: si aggiunge, se serve, margine vuoto attorno.

## Immagini

| Dove | Formato | Peso massimo | Dimensione consigliata | Elaborazione e visualizzazione |
|---|---|---|---|---|
| Logo orizzontale (Dati del comitato) | immagine, meglio PNG con sfondo trasparente | 4 MB | 700×220 px, con margini liberi del 30% sopra, 20% sotto e 8% per lato | ridotto a 1200 px sul lato lungo; in intestazione viene ingrandito e ritagliato nel riquadro 300×56 px, quindi il margine vuoto sparisce |
| Logo verticale | immagine, meglio PNG con sfondo trasparente | 4 MB | 500×500 px, con margini del 17% per lato, 24% sopra e 9% sotto | non ancora mostrato sul sito pubblico |
| Favicon | solo PNG | 1 MB | quadrata, ad esempio 256×256 px | ridotta a 256 px; icona della scheda del browser |
| Loghi IFRC e «Un'Italia che aiuta» | immagine, meglio PNG con sfondo trasparente | 4 MB | orizzontali, versione chiara adatta a sfondo scuro | alti 36 px nel piè di pagina (IFRC anche 20 px nella barra in alto); senza file resta un link rosso |
| Copertina di un post | immagine | 8 MB | 1600×900 px (16:9) | ridotta a 1600 px; negli elenchi in 16:9 ritagliata al centro; in home occupa tutta la larghezza, alta 420–520 px, ritagliata al centro; senza copertina compare un segnaposto grafico |
| Galleria di un post | immagine (JPG o PNG), più file insieme | 8 MB per file | libera, soggetto al centro | ridotta a 1920 px; miniature quadrate 1:1 ritagliate al centro; intera nella visualizzazione a tutto schermo |
| Immagine di una tipologia di corso | immagine | 5 MB | 1200×630 px, orizzontale | ridotta a 1600 px; mostrata in rapporto 1200:630 ritagliata al centro; usata anche come anteprima sui social |
| Foto della Struttura Organizzativa (presidente, vice, membri) | immagine | 8 MB | verticale 3:4, ad esempio 600×800 px | ridotta a 800 px; mostrata in rapporto 3:4 ritagliata al centro |

Le indicazioni dettagliate sui margini dei loghi sono nel pannello, negli accordion sopra ogni campo logo, in Dati del comitato.

## Documenti

| Dove | Formati accettati | Peso massimo | Note |
|---|---|---|---|
| Organigramma (Struttura Organizzativa) | pdf, doc, docx, ppt, pptx, xls, xlsx | 20 MB | scaricabile dalla pagina |
| Libreria documenti | pdf, doc, docx, ppt, pptx, xls, xlsx, odt, ods, odp, txt, csv | 20 MB | allegabili a post e pagine |
| Documenti di Trasparenza | solo PDF | 20 MB | elencati nella sezione Trasparenza |
| Allegati delle comunicazioni ai soci | gli stessi della libreria documenti | 20 MB per file | scaricabili solo dall'area soci |

## Elenco soci

| Dove | Formato | Peso massimo | Note |
|---|---|---|---|
| Importazione membri | solo `.xlsx` | 5 MB | file temporaneo usato per l'anteprima e la conferma dell'importazione |
