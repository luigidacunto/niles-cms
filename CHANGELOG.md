# Changelog

Elenco delle modifiche di ogni versione, dalla più recente. Le versioni seguono lo schema `MAJOR.MINOR.PATCH`: le funzioni nuove
incrementano la seconda cifra, le correzioni e i miglioramenti la terza. Le release complete, con i pacchetti scaricabili, sono su
[GitHub](https://github.com/luigidacunto/niles-cms/releases). Le direzioni future sono nella [roadmap](ROADMAP.md).

## 1.1.12 — 2026-11-04
- **Correzione degli errori di inserimento** (solo amministratori), per sistemare iscrizioni e anagrafiche create con dati sbagliati:
  - **Correggi iscritto** (Corsi → Iscritti): modifica i dati del partecipante e quelli di fatturazione e pagamento. Se la fatturazione è
    condivisa con altri iscritti, la modifica vale per tutti e la pagina lo segnala. Cambiando il codice fiscale l'iscrizione viene
    ricollegata alla persona corretta e i consensi della persona sbagliata passano a quella giusta.
  - **Elimina persona** (scheda Persone): consentita solo se la persona non ha iscrizioni né è referente di altre iscrizioni.
  - **Sostituisci referente**: sposta tutte le iscrizioni di un referente sbagliato su un'altra persona esistente, indicata dal codice fiscale.
  - Ogni correzione viene registrata nel log dell'applicazione.
- **Controllo più accurato del codice fiscale** nei moduli dei corsi: oltre al formato si verificano data di nascita (anche con omocodia),
  lettere di cognome e nome, con un messaggio che indica quale parte non torna e se nome e cognome sembrano invertiti. Le ultime cinque
  posizioni (comune e carattere di controllo) non vengono verificate. Nell'inserimento manuale della segreteria non si controllano i nomi.
  L'anagrafica dei soci resta invariata; nell'anteprima dell'importazione dei soci un codice fiscale incoerente produce un avviso, ma la
  riga viene importata comunque.

## 1.1.11 — 2026-11-02
- **Risposte ai corsisti**: le email inviate a chi si iscrive a un corso (conferma di iscrizione e riepilogo al referente) hanno come «Rispondi a»
  il primo indirizzo dell'elenco dell'avviso di iscrizione (Posta in uscita, sezione Formazione), se presente; altrimenti vale il «Rispondi a»
  generale.
- L'email di prova di Posta in uscita riporta ora anche il «Rispondi a».

## 1.1.10 — 2026-11-02
- **«Rispondi a» generale** (Amministrazione → Posta in uscita): indirizzo reale a cui arrivano le risposte a qualsiasi email del sito, anche al
  codice di accesso, utile quando l'indirizzo mittente è tecnico o inesistente. Se vuoto si usa l'email del comitato. Vale anche con la posta
  impostata nel file `.env`.
- **Avviso di nuova iscrizione ai corsi** (stessa pagina, sezione Formazione): spuntando l'opzione e indicando uno o più indirizzi, la segreteria
  riceve una email per ogni iscrizione dal modulo pubblico, con corso, iscritti e link all'elenco nel pannello (senza codice fiscale). Non parte
  per gli iscritti inseriti a mano. Il testo è modificabile da Template email.

## 1.1.9 — 2026-10-22
- Aggiornamento di sicurezza degli strumenti di build: Vite 8.3.4, che corregge tre vulnerabilità di `launch-editor` presenti nel server di
  sviluppo (`npm run dev`). Nessun effetto sul sito in produzione.

## 1.1.8 — 2026-10-21
- **Footer riordinato**: le tre colonne hanno tutte un titolo («Contatti», «Informazioni», «Link utili») e i link sono in carattere normale, senza
  maiuscolo e grassetto.
- **Link del footer: aggiunta dinamica.** Nella pagina Amministrazione → Link del footer il pulsante «Aggiungi link» inserisce una riga con
  ordine, testo e indirizzo, tutte quelle che servono; la X toglie una riga. Le modifiche, le righe nuove e quelle rimosse si applicano
  al salvataggio.

## 1.1.7 — 2026-10-21
- **Link del footer personalizzabili** (Amministrazione → Link del footer): la terza colonna del footer, «Link utili», si compone da pannello
  con testo, indirizzo e ordine di ciascun link (siti esterni con `https://…` oppure pagine del sito con `/…`). Senza link la colonna non
  compare. I tre link a cri.it scritti nel codice sono stati rimossi: chi li usava li reinserisce dal pannello dopo l'aggiornamento.
- «Struttura Organizzativa» passa nella seconda colonna del footer, con gli altri link interni.
- Nuova voce **Aggiornamento** nel menu Amministrazione, sulle installazioni con aggiornamento dal pannello.

## 1.1.6 — 2026-10-19
- Pagina Aggiornamento: pulsante **Controlla ora**, che ripete subito la verifica della versione disponibile senza aspettare il
  controllo giornaliero.

## 1.1.5 — 2026-10-18
- Correzione dell'aggiornamento dal pannello: la pagina del programma di aggiornamento restava ferma su «Avvio…» senza far partire
  l'operazione.

## 1.1.4 — 2026-10-18
- **Posta in uscita configurabile dal pannello** (Amministrazione → Posta in uscita): server SMTP, credenziali con password cifrata e
  mittente salvati nel database, con invio di un'email di prova. Le variabili `MAIL_*` del `.env`, se presenti, hanno la precedenza.
- L'installer su hosting condiviso salva la posta nel database invece che nel file `.env`.

## 1.1.3 — 2026-10-16
- Aggiornamento dal pannello per le installazioni su hosting condiviso (pulsante «Aggiorna», copia di sicurezza del database, ripristino
  del codice in caso di errore).
- Registro dei seeder di aggiornamento (`niles:seeders`).

## 1.1.2 — 2026-10-15
- Velo con rotella e contatore durante le operazioni lunghe dell'installer.

## 1.1.1 — 2026-10-15
- Installer a file singolo per hosting condiviso senza accesso SSH.

## 1.1.0 — 2026-10-15
- Area soci: documenti riservati per categoria, con anteprima e registro dei download.
- La categoria «Senza categoria» dei documenti riservati nasce al primo uso e non si può eliminare.

## 1.0.5 — 2026-10-15
- Comando `dev:anonymize-personal-data` per anonimizzare i dati personali nei database di sviluppo.

## 1.0.4 — 2026-10-12
- Slug di pagine e post: anteprima in tempo reale dal titolo.

## 1.0.3 — 2026-10-09
- Loghi: indicazioni su formato e margini, in un riquadro espandibile.

## 1.0.2 — 2026-10-09
- Pagine padre: elenco automatico delle pagine figlie e breadcrumb condiviso.

## 1.0.1 — 2026-10-09
- Modalità demo (`NILES_DEMO`), con avviso visibile dopo una modifica rifiutata.
- Menu iniziale visibile dopo la prima installazione.
- Guida di installazione.

## 1.0.0 — 2026-10-08
- Prima versione pubblica.
