# Configurazione del file .env

Il file `.env`, nella cartella principale del progetto, contiene le impostazioni che dipendono dal server e dall'installazione:
indirizzi, database, posta, statistiche. Si crea copiando il modello `.env.example` (`cp .env.example .env`), che contiene tutte le
variabili con commenti. I dati del comitato (denominazione, contatti, IBAN, loghi) **non** si impostano qui ma dal pannello, in
[Dati del comitato](../configurazione-iniziale/dati-del-comitato.md).

> **Dopo ogni modifica al `.env` esegui `php artisan config:cache`**: senza, il sito continua a usare i valori vecchi.

Regole di scrittura: un valore per riga, nessuno spazio attorno al `=`; i valori con spazi o con caratteri speciali (`#`, `$`,
`=`, `?`) vanno **tra virgolette doppie**.

## Da impostare sempre

| Variabile | Esempio | Significato |
|---|---|---|
| `APP_PUBLIC_NAME` | `"Croce Rossa Italiana – Comitato di Esempio"` | nome del sito, usato nel titolo delle pagine pubbliche |
| `APP_ENV` | `production` | in produzione deve essere `production` |
| `APP_DEBUG` | `false` | in produzione deve essere `false`: con il debug acceso gli errori mostrano dettagli interni |
| `APP_URL` | `https://esempio.it` | indirizzo pubblico completo, con `https://` |
| `APP_KEY` | _(generata)_ | chiave di cifratura: non scriverla a mano, si crea con `php artisan key:generate` e non va più cambiata |
| `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | `niles`, `niles`, `"…"` | database e utente creati per NILES; l'utente non deve essere quello amministratore |
| `INITIAL_ADMIN_EMAIL`, `INITIAL_ADMIN_PASSWORD` | `nome@esempio.it`, `"…"` | credenziali del primo amministratore, usate solo da `php artisan db:seed` se non esiste ancora nessun amministratore |

Se `INITIAL_ADMIN_EMAIL` non è impostata si usa `administrator@example.it`; se manca la password ne viene generata una casuale,
mostrata una sola volta al termine di `db:seed`. Le altre voci del database (`DB_HOST`, `DB_PORT`) restano `127.0.0.1` e
`3306` se il database è sullo stesso server; cambiale solo se è altrove.

## Posta in uscita

NILES invia email per il codice di accesso al pannello e all'area soci e per le conferme di iscrizione ai corsi (con il
promemoria per il calendario). Con `MAIL_MAILER=log` le email **non vengono spedite**: finiscono in `storage/logs/laravel.log`, e solo se
`LOG_LEVEL=debug`. Va bene per provare, non per un sito in uso. Per spedirle davvero serve un server SMTP (quello del
provider del dominio o un servizio di invio):

```ini
MAIL_MAILER=smtp
MAIL_HOST=smtp.esempio.it
MAIL_PORT=587
MAIL_USERNAME="utente-smtp"
MAIL_PASSWORD="password-smtp"
MAIL_FROM_ADDRESS="noreply@esempio.it"
MAIL_FROM_NAME="${APP_PUBLIC_NAME}"
```

- Porta 587: lascia `MAIL_SCHEME` vuoto (o `null`). Porta 465: imposta `MAIL_SCHEME=smtps`.
- L'indirizzo `MAIL_FROM_ADDRESS` deve appartenere a un dominio autorizzato dal server SMTP, altrimenti molti provider scartano
  i messaggi.
- I testi delle email e le chiavi CAPTCHA non stanno nel `.env` ma nel pannello:
  [Informative privacy ed email](../configurazione-iniziale/informative-privacy-ed-email.md) e
  [Sicurezza dei moduli pubblici](../configurazione-iniziale/sicurezza-moduli.md).

Dopo aver configurato la posta, l'accesso con il codice via email (`/admin/login`) funziona; l'accesso con la password
(`/admin/login/password`) resta sempre disponibile.

## Log

| Variabile | Valore consigliato | Note |
|---|---|---|
| `LOG_LEVEL` | `warning` in produzione | con `debug` il file `storage/logs/laravel.log` cresce molto; serve `debug` solo per leggere le email quando `MAIL_MAILER=log` |

## Statistiche (facoltative)

Senza queste righe non viene caricata nessuna statistica. Il `.env` indica **quale strumento** usare; dal pannello, in
[Dati del comitato](../configurazione-iniziale/dati-del-comitato.md), si possono poi solo accendere o spegnere i due
strumenti, e solo se sono definiti qui.

```ini
# Statistiche senza cookie (GoatCounter), nessun consenso richiesto. Servizio ospitato o proprio:
ANALYTICS_COOKIELESS_SRC=//gc.zgo.at/count.js
ANALYTICS_COOKIELESS_ENDPOINT=https://codice.goatcounter.com/count

# Google Analytics 4: attivo solo dopo il consenso dei visitatori dal banner cookie
GA4_ID=G-XXXXXXXXXX
```

Con un GoatCounter proprio, `ANALYTICS_COOKIELESS_SRC` e `ANALYTICS_COOKIELESS_ENDPOINT` puntano allo stesso sottodominio
(per esempio `//stats.esempio.it/count.js` e `https://stats.esempio.it/count`). Google Analytics comporta il trasferimento di
dati verso fornitori fuori dall'Unione Europea: valutalo con chi cura la privacy del comitato prima di attivarlo.

## Altre impostazioni facoltative

| Variabile | Predefinito | Significato |
|---|---|---|
| `COMMITTEE_TIMEZONE` | `Europe/Rome` | fuso orario con cui si leggono e si mostrano date e orari dei corsi |
| `NILES_UPDATE_CHECK` | attivo | la dashboard del pannello, solo per gli amministratori, controlla ogni 24 ore se esiste una release più recente su GitHub; `false` lo disattiva e non fa alcuna richiesta esterna |
| `BCRYPT_ROUNDS` | `12` | costo dell'hash delle password: più alto è più sicuro e più lento |
| `SESSION_LIFETIME` | `120` | minuti di inattività prima che la sessione scada |

## Da lasciare come sono

`APP_NAME`, `APP_LOCALE`, `APP_FALLBACK_LOCALE`, `APP_FAKER_LOCALE`, `APP_MAINTENANCE_DRIVER`, `LOG_CHANNEL`, `LOG_STACK`,
`LOG_DEPRECATIONS_CHANNEL`, `DB_CONNECTION`, `SESSION_DRIVER`, `SESSION_ENCRYPT`, `SESSION_PATH`, `SESSION_DOMAIN`,
`FILESYSTEM_DISK`, `QUEUE_CONNECTION`, `CACHE_STORE`: sono già impostate nel modello per il funzionamento di NILES (sessioni, cache e
code usano il database, quindi non servono Redis né servizi aggiuntivi).

## Installazioni particolari

- **Sito di prova pubblico** (`NILES_DEMO=true`): il pannello e il sito funzionano, ma nessuna modifica viene salvata; in alto
  compare un avviso e le credenziali di prova sono mostrate nelle pagine di accesso, con l'elenco facoltativo in
  `NILES_DEMO_ACCOUNTS` (account separati da `|`). Richiede anche `MAIL_MAILER=log` e `NILES_UPDATE_CHECK=false`.
- **Sito di anteprima** (`STAGING_GATE_PUBLIC=true`): il sito pubblico è visibile solo a chi ha già fatto l'accesso al pannello
  nello stesso browser. Serve per verificare un aggiornamento prima di pubblicarlo. Ha effetto solo se `APP_ENV` non è
  `production` (per esempio `staging`): su un sito in produzione viene ignorata.
