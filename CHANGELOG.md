# Changelog

Elenco delle modifiche di ogni versione, dalla più recente. Le versioni seguono lo schema `MAJOR.MINOR.PATCH`: le funzioni nuove
incrementano la seconda cifra, le correzioni e i miglioramenti la terza. Le release complete, con i pacchetti scaricabili, sono su
[GitHub](https://github.com/luigidacunto/niles-cms/releases). Le direzioni future sono nella [roadmap](ROADMAP.md).

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
