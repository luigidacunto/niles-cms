# Primo accesso al pannello

Dopo l'installazione ([su un server](02-installazione-su-server.md) o [su un hosting condiviso](05-installazione-su-hosting-condiviso.md))
il sito è già online con un menu completo, i testi di base e un primo amministratore. Questa pagina spiega come entrare, cosa si trova nel pannello e in che ordine conviene configurarlo.

## Accedere

Apri `https://<dominio>/admin/login/password` e inserisci l'email e la password del primo amministratore:

- con l'installer per hosting condiviso, quelle scelte nel modulo di installazione;
- con l'installazione su server, quelle impostate in `INITIAL_ADMIN_EMAIL` e `INITIAL_ADMIN_PASSWORD` nel file `.env` (oppure
  l'indirizzo `administrator@example.it` con la password mostrata al termine di `db:seed`).

![Pagina di accesso con password](../immagini/primo-accesso/accesso-con-password.png)

- La pagina `/admin/login` propone invece l'accesso con il **codice via email**: funziona solo dopo aver configurato la posta in
  uscita, dal pannello ([Posta in uscita](../06-configurazione/02-posta-in-uscita.md)) o nel file `.env`. L'accesso con la password
  resta sempre disponibile per gli account in cui è abilitato.
- «Ricordami» mantiene l'accesso su quel browser più a lungo.

## Cambiare la password iniziale

Se hai scelto una password provvisoria, cambiala subito: apri **Utenti** (menu *Amministrazione → Utenti e controllo*), modifica il tuo
utente, scrivi la nuova password nel campo **Password** e salva. Lasciando il campo vuoto la password non cambia.

Da qui si creano anche gli altri utenti del pannello: vedi [Utenti](../08-utenti-e-controllo/01-utenti.md).

## La dashboard

All'ingresso compare la dashboard, un riepilogo dei contenuti del sito.

![Dashboard del pannello](../immagini/primo-accesso/dashboard.png)

- **Riquadri in alto**: numero di post, pagine, documenti e foto nelle gallerie, ciascuno con il collegamento a «Gestisci».
- **Indicatori**: post pubblicati, bozze, post senza copertina e pagine fuori dal menu.
- **Ultimi post** e **Post per categoria**: cosa è stato pubblicato di recente e come sono distribuiti i contenuti.
- **Riepilogo**: versione di NILES installata e, se il controllo è attivo, se ne esiste una più recente
  (vedi `NILES_UPDATE_CHECK` in [Configurazione del file .env](03-configurazione-env.md)).
## Il menu laterale

In alto **Dashboard** e **Guida** (questa documentazione, consultabile dal pannello da tutti gli utenti). Sotto, le sezioni sono
divise in due parti, ciascuna con gruppi che si aprono e si chiudono con un clic; il pannello ricorda quali gruppi sono aperti.

| Parte | Gruppo | Voci |
|---|---|---|
| Lavoro quotidiano | Contenuti | Post, Pagine, Struttura organizzativa, Trasparenza, Libreria documenti |
| | Formazione | Corsi, Persone, Pagamenti corsi, Tipologie corso |
| | Area soci | Membri, Comunicazioni soci, Documenti riservati, Categorie riservate |
| Amministrazione | Comitato e sito | Dati del comitato, Loghi e immagini, Coordinate e pagamenti, Link del footer, Informative privacy, Template email |
| | Configurazione | Funzioni attive, Posta in uscita, Sicurezza moduli, Aggiornamento |
| | Classificazioni | Categorie, Aree Trasparenza, Categorie documenti |
| | Utenti e controllo | Utenti, Registro accessi, Registro download |

- La parte **Amministrazione** è visibile solo agli amministratori; un editor vede soltanto le sezioni per cui ha il permesso.
- **Area soci** e **Pagamenti corsi** compaiono solo dopo averle attivate in *Configurazione → Funzioni attive*.
- **Aggiornamento** compare solo nelle installazioni fatte con l'installer per hosting condiviso (vedi
  [Aggiornamento e backup](04-aggiornamento.md)).

## Cosa trovi già pronto

L'installazione crea questi contenuti, da adattare:

- il **menu del sito** con sei voci (Chi Siamo, Cosa Facciamo, Volontariato, Sostienici, Servizi, Contatti) e le relative pagine, con
  testi standard validi per tutti i comitati; le pagine che non tutti hanno (Corpo Infermiere Volontarie, Diventa Infermiera
  Volontaria, Donazioni, Innovazione) sono create in **bozza** e vanno attivate da chi le usa;
- le **categorie** delle notizie, le **aree** della sezione Trasparenza e le **tipologie di corso** più comuni;
- la Privacy Policy e la Cookie Policy, le informative privacy per i soci e per i corsi, i testi delle email di sistema;
- una comunicazione di benvenuto nell'area soci.

Le pagine che richiedono dati del comitato (Contattaci, Dove Trovarci, il codice fiscale per il 5x1000) contengono un
segnaposto **«Da completare»**: sostituiscilo con i dati reali.

## Ordine consigliato della configurazione

1. [Dati del comitato](../05-comitato-e-sito/01-dati-del-comitato.md): denominazione, contatti, loghi e favicon, coordinate. Compaiono
   subito nel sito.
2. [Posta in uscita](../06-configurazione/02-posta-in-uscita.md): il server delle email, necessario per il codice di accesso e per le
   conferme di iscrizione ai corsi.
3. [Utenti](../08-utenti-e-controllo/01-utenti.md): cambia la password iniziale e crea gli utenti che servono.
4. *Funzioni attive* (menu *Configurazione*): accendi le funzioni facoltative che usi, come l'area soci e la gestione dei pagamenti
   dei corsi.
5. [Pagine](../02-contenuti/02-pagine.md): sostituisci i segnaposto, attiva le pagine che usi.
6. [Struttura organizzativa](../02-contenuti/03-struttura-organizzativa.md) e [Trasparenza](../02-contenuti/04-trasparenza.md).
7. [Post](../02-contenuti/01-post.md) e [Categorie](../07-classificazioni/01-categorie.md): la prima notizia.
8. [Corsi](../03-formazione/01-corsi.md), [Tipologie corso](../03-formazione/04-tipologie-corso.md) e [Membri](../04-area-soci/01-membri.md), se li usi.
9. [Informative privacy](../05-comitato-e-sito/05-informative-privacy.md), [Template email](../05-comitato-e-sito/06-template-email.md) e
   [Sicurezza moduli](../06-configurazione/03-sicurezza-moduli.md), prima di aprire le iscrizioni al pubblico.

Per formati e dimensioni di loghi, copertine e foto vedi [Immagini e file](../01-primi-passi/03-immagini-e-file.md).
