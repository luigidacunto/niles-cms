# Documentazione di NILES

Guide per installare, configurare e usare NILES, il CMS per i comitati locali della Croce Rossa Italiana. Per una panoramica del
progetto e per l'installazione in breve vedi il [README](README.md).

## Installazione

Per mettere NILES online su un server.

1. [Requisiti](01-installazione/01-requisiti.md): versioni di PHP, database, Node e Composer, estensioni e limiti di caricamento.
2. [Installazione su un server](01-installazione/02-installazione-su-server.md): la procedura passo per passo.
3. [Configurazione del file .env](01-installazione/03-configurazione-env.md): tutte le variabili, spiegate.
4. [Aggiornamento e backup](01-installazione/04-aggiornamento.md): nuove versioni e copie di sicurezza.
5. [Installazione su un hosting condiviso](01-installazione/05-installazione-su-hosting-condiviso.md): senza riga di comando, solo FTP e un
   database, con il programma di installazione.

## Configurazione iniziale

Cosa fare dopo l'installazione.

- [Primo accesso al pannello](02-configurazione-iniziale/01-primo-accesso.md): come entrare, cosa trovi già pronto e l'ordine consigliato
  della configurazione.
- [Immagini e file](02-configurazione-iniziale/02-immagini-e-file.md): formati, dimensioni, pesi e margini di loghi, copertine, foto e
  documenti.

## Guida al pannello

Un documento per ogni voce del menu laterale del pannello, nello stesso ordine e con gli stessi gruppi.

### Contenuti

- [Post](03-pannello/01-contenuti/01-post.md): Scrivere e pubblicare le notizie: titolo, testo, copertina, galleria, allegati e categorie.
- [Pagine](03-pannello/01-contenuti/02-pagine.md): Pagine del sito e menu di navigazione: struttura, ordine, bozze, pagine di sistema e pagine padre.
- [Struttura organizzativa](03-pannello/01-contenuti/03-struttura-organizzativa.md): Presidente, vice presidente, consiglio e organigramma, con le foto.
- [Dati del comitato](03-pannello/01-contenuti/04-dati-del-comitato.md): Denominazione, contatti, loghi e favicon, coordinate bancarie, social, statistiche.
- [Trasparenza](03-pannello/01-contenuti/05-trasparenza.md): Pubblicazione dei documenti della sezione Trasparenza.
- [Libreria documenti](03-pannello/01-contenuti/06-libreria-documenti.md): Caricare i documenti e allegarli a post e pagine.
- [Comunicazioni soci](03-pannello/01-contenuti/07-comunicazioni-soci.md): Comunicazioni interne visibili solo ai soci, con allegati riservati.

### Formazione

- [Corsi](03-pannello/02-formazione/01-corsi.md): Creare i corsi, gestire le iscrizioni, esportare e stampare gli elenchi.
- [Persone](03-pannello/02-formazione/02-persone.md): Anagrafica di chi si è iscritto, consensi e richieste di cancellazione.
- [Tipologie corso](03-pannello/02-formazione/03-tipologie-corso.md): Il catalogo dei tipi di corso: sigla, costo, validità, attestato e immagine.

### Organizzazione

- [Categorie](03-pannello/03-organizzazione/01-categorie.md): Le categorie delle notizie.
- [Aree Trasparenza](03-pannello/03-organizzazione/02-aree-trasparenza.md): Le aree in cui si organizzano i documenti della Trasparenza.
- [Categorie documenti](03-pannello/03-organizzazione/03-categorie-documenti.md): Le categorie della libreria documenti.

### Amministrazione

- [Utenti](03-pannello/04-amministrazione/01-utenti.md): Amministratori ed editor del pannello, permessi e modalità di accesso.
- [Membri](03-pannello/04-amministrazione/02-membri.md): Elenco dei soci, importazione da Excel e accesso all'area soci.
- [Registro accessi](03-pannello/04-amministrazione/03-registro-accessi.md): Chi è entrato nel pannello e nell'area soci, e quando.
- [Sicurezza moduli](03-pannello/04-amministrazione/04-sicurezza-moduli.md): Limiti di invio e protezione anti-bot (CAPTCHA) dei moduli pubblici.
- [Informative privacy](03-pannello/04-amministrazione/05-informative-privacy.md): Le informative per i soci e per i corsi e come personalizzarle.
- [Template email](03-pannello/04-amministrazione/06-template-email.md): I testi delle email di sistema e come ripristinarli.

## Organizzazione della cartella

Cartelle e documenti sono numerati nell'ordine di lettura; per il pannello è lo stesso ordine del menu laterale.

```
docs/
  README.md                       questo indice
  01-installazione/               requisiti, installazione, .env, aggiornamento
  02-configurazione-iniziale/     primo accesso e formati di immagini e file
  03-pannello/                    una cartella per gruppo del menu laterale, un documento per voce
    01-contenuti/
    02-formazione/
    03-organizzazione/
    04-amministrazione/
  immagini/                       schermate usate dalle guide, una cartella per documento
```

Ogni argomento sta in un solo documento: gli altri rimandano con un collegamento.
