# Roadmap

Direzione di sviluppo di NILES: cosa è in lavorazione, cosa è in programma e cosa è stato escluso. Non è un calendario: le
priorità possono cambiare e le voci «da valutare» non sono impegni. Per proporre una funzione vedi [CONTRIBUTING.md](CONTRIBUTING.md).

Versione attuale: vedi le [release](https://github.com/luigidacunto/niles-cms/releases).

## In lavorazione

- **Installazione e aggiornamento su hosting condiviso: verifica sul campo.** L'installer a file singolo e l'aggiornamento dal
  pannello esistono già (vedi [Installazione su hosting condiviso](docs/01-installazione/05-installazione-su-hosting-condiviso.md)).
  Restano le prove su hosting reali: scaricamento diretto dell'installer dalle release, invio di posta dal wizard, primo
  aggiornamento completo dal pulsante «Aggiorna».

## In programma

### Area soci
- **App installabile (PWA)** per l'area soci: icona sullo schermo del telefono e apertura a tutto schermo; consultazione solo online,
  senza copia locale dei documenti.
- **Pagine riservate** con contenuto strutturato (materiali, raccolte di PDF), sul modello della sezione Trasparenza ma visibili solo ai soci.
- **Modifica dei propri dati** dalla pagina «I miei dati» (oggi in sola lettura: le correzioni passano dalla segreteria).

### Privacy
- **Cancellazione definitiva dei soci rimossi** dopo un periodo di conservazione configurabile. Oggi la rimozione è sempre
  reversibile (i dati restano nel database).

### Documentazione
Completamento della [guida](docs/README.md), un argomento per volta. Parti ancora mancanti:
- area soci: documenti riservati, categorie riservate, registro dei download e uso dell'area dal lato del socio;
- installazione di un server di sviluppo, con le differenze rispetto alla produzione;
- migrazione da un CMS esistente, insieme allo strumento di migrazione.

### Progetto
- **Test automatici a ogni modifica** (GitHub Actions) e modelli per segnalazioni e pull request.
- **Strumento di migrazione da CPWBS/OSWBS**: pacchetto separato, installabile solo sull'installazione che migra, con la procedura
  documentata.

## Da valutare

Idee raccolte, senza una decisione su se e come realizzarle.

- **Comunicazioni ai soci per categoria**: oggi i documenti riservati sono visibili per categoria assegnata al socio, mentre le
  comunicazioni sono visibili a tutti i soci attivi.
- **Pagina iniziale dell'area soci.**
- **Attestato di partecipazione in PDF** per i corsi che lo prevedono. Gli attestati ufficiali (per esempio BLSD) sono rilasciati da
  portali esterni e restano fuori da NILES.
- **Promemoria di scadenza degli attestati e newsletter.** I dati sono già pronti (consensi separati, validità per tipologia di
  corso, esportazione CSV dei contatti); resta da decidere se inviare dal pannello o tramite un servizio esterno. I promemoria vanno
  solo alla persona interessata.
- **Altre sezioni strutturate** di sistema, come Trasparenza e Struttura organizzativa.
- **Disattivazione di intere sezioni** (non solo pagine) per i comitati che non le usano.
- **Moduli aggiuntivi**: gallerie, moduli di contatto, ricerca interna.

## Non previsto

- **Pagamento online delle iscrizioni ai corsi.** Il comitato contatta direttamente gli iscritti; eventualmente come modulo
  facoltativo, se richiesto da un comitato.
- **Limite di posti vincolante nei corsi**: il numero di posti resta indicativo, perché non c'è un pagamento anticipato.
- **Più comitati in un'unica installazione (multi-tenant)**: ogni comitato ha la propria installazione.
- **Frontend separato (SPA) o API pubblica**: le pagine sono generate dal server. Un'API potrà servire solo per l'area soci, se la PWA
  la richiederà.
