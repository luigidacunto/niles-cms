# Posta in uscita

In **Posta in uscita** (menu *Amministrazione*) si configura il server con cui NILES spedisce le email: codice di accesso al pannello e all'area
soci, conferme di iscrizione ai corsi, comunicazioni. La sezione è riservata agli amministratori. Le impostazioni sono salvate nel database e la
password è cifrata: non viene mai mostrata di nuovo.

## Impostazioni

| Campo | Significato |
|---|---|
| Tipo di invio | **SMTP** spedisce davvero le email; **Disattivata** non spedisce nulla (le email finiscono solo nel file di log) |
| Server, porta, cifratura | dati forniti dal provider della posta; SSL di solito con la porta 465, TLS/STARTTLS con la 587 |
| Utente e password | credenziali della casella usata per l'invio. Con la password vuota si mantiene quella già salvata |
| Indirizzo e nome mittente | indirizzo da cui partono le email; se vuoto si usa l'utente SMTP. Deve appartenere a un dominio autorizzato dal server |

La pagina mostra data e utente dell'**ultima modifica** e riassume le **impostazioni attive**.

## Email di prova

Il pulsante **Invia email di prova** usa i valori scritti nel modulo, anche se non ancora salvati, e spedisce un messaggio **solo all'indirizzo
email dell'amministratore che sta operando**. Durante l'invio la pagina mostra un'indicazione di attesa. L'esito (riuscito oppure il messaggio di
errore del server) resta visibile come **ultima prova**, con i parametri usati (mai la password). Conviene provare prima di salvare: un server
sbagliato impedisce l'arrivo dei codici di accesso via email.

## Precedenza del file .env

Se il file `.env` contiene anche **una sola** variabile `MAIL_*`, valgono soltanto le impostazioni del `.env` e quelle salvate nel pannello
vengono ignorate; la pagina lo segnala. Se le variabili del `.env` sono incomplete compare un avviso con l'elenco di quelle mancanti. Per
usare il pannello basta togliere o commentare tutte le righe `MAIL_*` dal `.env`. Dettagli sulle variabili in
[Configurazione del file .env](../../01-installazione/03-configurazione-env.md).

## Se la posta non funziona

- **Accesso al pannello:** con una posta guasta il codice via email non arriva. Si può entrare con la password se l'accesso con password è
  attivo per l'utente (in [Utenti](01-utenti.md)).
- **Rimedio dal server:** aggiungere `MAIL_MAILER=log` al `.env` (anche via FTP): le email non vengono spedite e il codice di accesso si legge nel file
  `storage/logs/laravel.log` (con `LOG_LEVEL=debug`).
- **Password non leggibile:** se cambia la chiave dell'applicazione (`APP_KEY`) la password salvata non si può più decifrare: la pagina lo segnala
  e va riscritta.
