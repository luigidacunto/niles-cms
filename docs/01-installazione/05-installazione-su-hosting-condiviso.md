# Installazione su un hosting condiviso

Procedura per mettere NILES online su un **hosting condiviso** che offre solo l'accesso ai file (FTP o file manager) e un database MySQL,
senza accesso da riga di comando (SSH). Non servono Composer, Node né comandi: un programma di installazione, caricato sul sito e aperto
dal browser, scarica NILES, lo prepara e guida la configurazione. Tempo previsto: circa un quarto d'ora.

Se si ha accesso da riga di comando (SSH) conviene la procedura di [Installazione su un server](02-installazione-su-server.md).

## Cosa serve

- Un dominio o un sottodominio **dedicato a NILES**, con HTTPS. L'installazione in una sottocartella di un altro sito (`esempio.it/comitato`)
  non è supportata.
- Un hosting Linux con **Apache** e **PHP 8.4**, selezionabile dal pannello del provider, con le estensioni indicate nei
  [requisiti](01-requisiti.md) (sui piani comuni sono già tutte presenti). L'installer controlla il tutto e segnala cosa manca.
- Un **database MySQL vuoto**, con relativo utente. Si crea dal pannello del provider: servono host, nome del database, utente e password.
- Un programma per caricare file sul sito: client FTP (WinSCP, FileZilla), oppure il file manager del provider.
- Facoltativo: i dati di una casella di posta per l'invio delle email (server SMTP, utente, password). Si possono dare anche in un secondo
  momento.

Limiti di PHP consigliati: almeno 128 MB di `memory_limit` e 60 secondi di `max_execution_time`, perché l'estrazione del pacchetto richiede
alcune decine di secondi (l'installer prova comunque a togliere il limite di tempo, se il provider lo consente). Quasi tutti i provider
permettono di modificarli da un pannello di configurazione di PHP.

## 1. Preparare il sito

1. Nel pannello del provider scegli **PHP 8.4** per il dominio.
2. Crea il **database** e annota host, nome, utente e password. Molti provider usano un host diverso da `localhost`: va indicato quello
   mostrato nel pannello.
3. Controlla la cartella del dominio (la cartella servita dal sito, spesso `public_html` o `htdocs`):
   - se contiene un **vecchio sito** da conservare, scaricane una copia e spostalo in una cartella di archivio, ad esempio `vecchio-sito`.
     Una volta installato NILES, quella cartella **non è più raggiungibile dal browser**: serve solo come copia di sicurezza;
   - se contiene una pagina segnaposto del provider, puoi lasciarla: i file con lo stesso nome del pacchetto verranno sovrascritti, gli
     altri non vengono toccati.

## 2. Caricare l'installer

1. Nella pagina **Releases** del repository di NILES apri l'ultima versione e, tra gli **Assets**, scarica il file `niles-installer.php`.
2. Caricalo nella **radice del sito**, cioè nella cartella servita dal dominio (non in una sottocartella).

**Verifica:** aprendo `https://esempio.it/niles-installer.php` compare la pagina «Installazione di NILES» con il controllo dei requisiti.

> Dal momento in cui il file è sul sito fino alla fine dell'installazione, chiunque ne conosca l'indirizzo potrebbe avviarla. Conviene
> quindi caricare il file e **completare subito** la procedura. Al termine il file si cancella da solo.

## 3. Controllare i requisiti e scaricare NILES

La prima pagina elenca i requisiti, ciascuno con il suo esito (**OK**, **ATTENZIONE** o **ERRORE**). Con un **ERRORE** il pulsante resta
disattivato: si risolve il problema (ad esempio la versione di PHP) e si ricarica la pagina. Un **ATTENZIONE** non blocca.

Se la cartella contiene già dei file, la pagina li elenca e chiede di spuntare «Ho controllato: procedi».

Premendo **Scarica e installa** il programma:

1. scarica da GitHub il pacchetto dell'ultima versione (circa 50 MB) e il suo codice di controllo, e verifica che il pacchetto sia integro;
2. lo estrae nella cartella del sito.

Durante l'operazione compare una schermata di attesa con il tempo trascorso: **non chiudere né ricaricare la pagina**. Richiede in genere uno o
due minuti.

> Se l'hosting non consente di scaricare file da internet, si scarica dalla stessa pagina Releases il pacchetto `niles.zip` (e `niles.zip.sha256`)
> e lo si carica accanto all'installer: il programma lo usa al posto del download.

## 4. Inserire i dati

Compare il modulo di configurazione:

- **Database MySQL**: host, porta, nome, utente e password annotati prima. L'installer prova la connessione e rifiuta un database
  **non vuoto**, per non toccare dati esistenti.
- **Sito**: il nome del comitato (compare nel titolo delle pagine) e l'indirizzo pubblico del sito, già compilato con quello corrente.
- **Primo amministratore**: email e password (almeno 10 caratteri). Sono le credenziali per entrare nel pannello.
- **Posta in uscita** (facoltativa): server SMTP, porta, cifratura, utente, password e indirizzo mittente. I dati si trovano nel pannello del
  provider di posta; di norma l'indirizzo mittente deve essere la stessa casella con cui ci si autentica. Se la prova di collegamento non
  riesce, il modulo mostra il motivo e permette di proseguire comunque senza posta.

Prima di mostrare il modulo la pagina verifica dal browser che i file riservati (`.env`, `composer.json`, le librerie) **non siano
raggiungibili dal web**. Se compare un riquadro rosso di blocco, non si installa: significa che la protezione fornita dal file `.htaccess`
non è attiva su quell'hosting e i dati di accesso al database sarebbero esposti (vedi «Se qualcosa non va»).

Premendo **Installa NILES** il programma scrive il file `.env` (con una chiave di sicurezza nuova e le impostazioni di produzione), crea le
tabelle del database, carica i contenuti iniziali del sito, crea l'amministratore, collega la cartella dei file caricati e si cancella.
Richiede circa un minuto, con la stessa schermata di attesa.

## 5. Primo accesso

Alla fine compare l'indirizzo di accesso: `https://esempio.it/admin/login/password`. Si entra con l'email e la password scelte al punto 4.

L'accesso di default del pannello usa un codice inviato per email; finché la posta non è configurata e funzionante si usa l'**accesso con
password**, sempre disponibile per il primo amministratore. Il resto della configurazione è descritto in
[Primo accesso al pannello](../02-configurazione-iniziale/01-primo-accesso.md).

**Verifica:**

- il pannello si apre e mostra la dashboard con la versione di NILES;
- il sito pubblico si apre all'indirizzo del dominio;
- nella radice del sito via FTP **non c'è più** `niles-installer.php`. Se per qualche motivo è ancora lì, va cancellato a mano.

Per configurare la posta in un secondo momento si modificano le variabili `MAIL_*` del file `.env`, come descritto in
[Configurazione del file .env](03-configurazione-env.md).

## Aggiornare

Le installazioni fatte con questo programma si aggiornano **dal pannello**: quando esiste una versione più recente, in dashboard compare il
pulsante **Aggiorna**. La procedura è descritta in [Aggiornamento e backup](04-aggiornamento.md).

## Se qualcosa non va

| Cosa si vede | Cosa significa e cosa fare |
|---|---|
| Riquadro rosso «Installazione bloccata: file riservati raggiungibili dal web» | L'hosting non applica il file `.htaccess` (ad esempio server non Apache). Non proseguire: chiedere al provider di abilitare `.htaccess` e `mod_rewrite`, oppure scegliere un altro hosting. |
| «Il database non è vuoto» | Il database contiene già delle tabelle. Usarne uno nuovo e vuoto. Fa eccezione un'installazione interrotta a metà, che l'installer riprende da sola. |
| Un requisito con **ERRORE** | Correggerlo dal pannello del provider (versione di PHP, estensioni) e ricaricare la pagina. |
| La pagina resta ferma o compare un errore dopo un minuto o due | Riaprire `https://esempio.it/niles-installer.php`: l'operazione riprende da dove si era fermata. |
| Dopo l'installazione compare ancora il vecchio sito, o una pagina senza stili | Molti provider tengono una copia in cache della pagina precedente. Provare con un aggiornamento forzato del browser (Ctrl+F5) o con un parametro in coda (`esempio.it/?prova=1`), e svuotare la cache dal pannello del provider se c'è, altrimenti attendere che scada. |
| Compare «NILES risulta già installato» | L'installazione è completa: il file va solo cancellato. |
| «Installazione in una sottocartella non supportata» | Spostare il file nella radice di un dominio o sottodominio dedicato. |

## Cosa non viene toccato dall'installer

- I file già presenti nella cartella con nomi diversi da quelli del pacchetto.
- I dati di altri database del provider.
- Il vecchio sito spostato in una cartella di archivio (non sarà più visibile dal browser).

Nel pannello del provider restano a carico di chi amministra il sito le **copie di sicurezza** dell'hosting: sono la protezione da un guasto
del server e vanno attivate o verificate prima di iniziare a pubblicare contenuti.
