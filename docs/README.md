# Documentazione di NILES

Guide per installare, configurare e usare NILES, il CMS per i comitati locali della Croce Rossa Italiana. Per una panoramica del
progetto e per l'installazione in breve vedi il [README](../README.md).

## 00 · Installazione

Per mettere NILES online e tenerlo aggiornato.

1. [Requisiti](00-installazione/01-requisiti.md): versioni di PHP, database, Node e Composer, estensioni e limiti di caricamento.
2. [Installazione su un server](00-installazione/02-installazione-su-server.md): la procedura passo per passo.
3. [Configurazione del file .env](00-installazione/03-configurazione-env.md): tutte le variabili, spiegate.
4. [Aggiornamento e backup](00-installazione/04-aggiornamento.md): nuove versioni e copie di sicurezza.
5. [Installazione su un hosting condiviso](00-installazione/05-installazione-su-hosting-condiviso.md): senza riga di comando, solo FTP e un
   database, con il programma di installazione.
6. [Primo accesso al pannello](00-installazione/06-primo-accesso.md): come entrare, cosa trovi già pronto e l'ordine consigliato
   della configurazione.

## Guida al pannello

Dalla cartella `01` in poi, un documento per ogni voce del menu laterale del pannello, nello stesso ordine e con gli stessi gruppi. La
stessa guida si consulta anche dal pannello, alla voce «Guida».

### 01 · Primi passi

- [Accesso al pannello](01-primi-passi/01-accesso-al-pannello.md): codice via email, password, uscita e problemi frequenti.
- [Il pannello in breve](01-primi-passi/02-il-pannello-in-breve.md): dashboard, menu e ricerca, pulsanti degli elenchi, moduli e guida.
- [Immagini e file](01-primi-passi/03-immagini-e-file.md): formati, dimensioni, pesi e margini di loghi, copertine, foto e documenti.

### 02 · Contenuti

- [Post](02-contenuti/01-post.md): scrivere e pubblicare le notizie: titolo, testo, copertina, galleria, allegati e categorie.
- [Pagine](02-contenuti/02-pagine.md): pagine del sito e menu di navigazione: struttura, ordine, bozze, pagine di sistema e pagine padre.
- [Struttura organizzativa](02-contenuti/03-struttura-organizzativa.md): presidente, vice presidente, consiglio e organigramma, con le foto.
- [Trasparenza](02-contenuti/04-trasparenza.md): pubblicazione dei documenti della sezione Trasparenza.
- [Libreria documenti](02-contenuti/05-libreria-documenti.md): caricare i documenti e allegarli a post e pagine.

### 03 · Formazione

- [Corsi](03-formazione/01-corsi.md): creare i corsi, gestire le iscrizioni, esportare e stampare gli elenchi.
- [Persone](03-formazione/02-persone.md): anagrafica di chi si è iscritto, consensi, richieste di cancellazione e correzione degli errori.
- [Pagamenti corsi](03-formazione/03-pagamenti-corsi.md): fatture, ricevute e incassi di ogni corso a pagamento, con i dati da copiare per la fattura.
- [Tipologie corso](03-formazione/04-tipologie-corso.md): il catalogo dei tipi di corso: sigla, costo, validità, attestato e immagine.
- [Iscrizione dal sito](03-formazione/05-iscrizione-dal-sito.md): come si iscrive una persona dal sito, messaggi di errore ed email, per dare indicazioni a chi chiede aiuto.

### 04 · Area soci

- [Membri](04-area-soci/01-membri.md): elenco dei soci, importazione da Excel, accesso all'area soci e categorie riservate assegnate.
- [Comunicazioni soci](04-area-soci/02-comunicazioni-soci.md): comunicazioni interne visibili solo ai soci, con copertina, galleria e allegati riservati.
- [Documenti riservati](04-area-soci/03-documenti-riservati.md): documenti consultabili solo dai soci, divisi per categoria.
- [Categorie riservate](04-area-soci/04-categorie-riservate.md): i gruppi che decidono quali soci vedono quali documenti.
- [L'area soci vista dal socio](04-area-soci/05-area-soci-dal-socio.md): accesso, comunicazioni, documenti e «I miei dati», con le domande frequenti dei soci.

### 05 · Comitato e sito

- [Dati del comitato](05-comitato-e-sito/01-dati-del-comitato.md): denominazione, contatti, loghi e favicon, coordinate bancarie, social, statistiche.
- [Informative privacy](05-comitato-e-sito/05-informative-privacy.md): le informative per i soci e per i corsi e come personalizzarle.
- [Template email](05-comitato-e-sito/06-template-email.md): i testi delle email di sistema e come ripristinarli.

### 06 · Configurazione

- [Posta in uscita](06-configurazione/02-posta-in-uscita.md): server di invio delle email, email di prova e precedenza del file .env.
- [Sicurezza moduli](06-configurazione/03-sicurezza-moduli.md): limiti di invio e protezione anti-bot (CAPTCHA) dei moduli pubblici.

### 07 · Classificazioni

- [Categorie](07-classificazioni/01-categorie.md): le categorie delle notizie.
- [Aree Trasparenza](07-classificazioni/02-aree-trasparenza.md): le aree in cui si organizzano i documenti della Trasparenza.
- [Categorie documenti](07-classificazioni/03-categorie-documenti.md): le categorie della libreria documenti.

### 08 · Utenti e controllo

- [Utenti](08-utenti-e-controllo/01-utenti.md): amministratori ed editor del pannello, permessi e modalità di accesso.
- [Registro accessi](08-utenti-e-controllo/02-registro-accessi.md): chi è entrato nel pannello e nell'area soci, e quando.
- [Registro download](08-utenti-e-controllo/03-registro-download.md): chi ha aperto o scaricato i documenti riservati dell'area soci.

## Organizzazione della cartella

Un solo livello di cartelle numerate; dentro, i documenti numerati nell'ordine di lettura. Dalla `01` in poi ogni cartella è un gruppo del
menu laterale del pannello e ogni documento una sua voce. I numeri mancanti corrispondono a guide non ancora scritte.

```
docs/
  README.md                 questo indice
  00-installazione/         requisiti, installazione, .env, aggiornamento, hosting condiviso, primo accesso
  01-primi-passi/
  02-contenuti/
  03-formazione/
  04-area-soci/
  05-comitato-e-sito/
  06-configurazione/
  07-classificazioni/
  08-utenti-e-controllo/
  immagini/                 schermate usate dalle guide, una cartella per argomento
```

Ogni argomento sta in un solo documento: gli altri rimandano con un collegamento.
