# Changelog

Elenco delle modifiche di ogni versione, dalla più recente. Le versioni seguono lo schema `MAJOR.MINOR.PATCH`: le funzioni nuove
incrementano la seconda cifra, le correzioni e i miglioramenti la terza. Le release complete, con i pacchetti scaricabili, sono su
[GitHub](https://github.com/luigidacunto/niles-cms/releases). Le direzioni future sono nella [roadmap](ROADMAP.md).

## 1.2.2 — 2026-11-06
- **Pagamenti corsi: incasso in eccesso.** Un importo incassato superiore al totale è segnalato come «In eccesso» (badge, filtro «In
  eccesso (da verificare)» e conteggio nell'elenco dei corsi), con la nota obbligatoria come per l'incasso parziale: si indica se è una
  donazione o una quota da rimborsare. Succede da solo anche quando un iscritto si ritira dopo aver pagato.
- **Indirizzi e luoghi di nascita**: preposizioni e articoli dopo la prima parola restano minuscoli («Via delle Imprese», «Reggio
  nell'Emilia») e i numeri romani maiuscoli («Via XX Settembre»). Vale per i nuovi inserimenti; nomi e cognomi invariati.
- Elenco iscritti: con il filtro «Dati mancanti» senza risultati il messaggio lo dice esplicitamente.
- **Guida, sezione Formazione**: Corsi, Persone e Tipologie aggiornati alla 1.2; nuovi documenti «Pagamenti corsi» e «Iscrizione dal sito»
  (il modulo pubblico visto da chi si iscrive, con i messaggi di errore e le email, per dare indicazioni a chi chiede aiuto).
- **Guida, sezione Area soci**: Membri e Comunicazioni aggiornati (categorie riservate, copertina e galleria private, allegati scelti tra i
  documenti riservati); nuovi documenti «Documenti riservati», «Categorie riservate», «L'area soci vista dal socio» (accesso, pagine e
  domande frequenti dei soci) e «Registro download». In «Immagini e file» corretti i formati dei documenti riservati (solo PDF e immagini).
- **Guida, sezioni Comitato e sito, Configurazione, Classificazioni, Utenti e controllo**: «Dati del comitato» diviso come le pagine del
  pannello (Dati del comitato, Loghi e immagini, Coordinate e pagamenti); nuovi «Link del footer» e «Funzioni attive»; in «Posta in
  uscita» il «Rispondi a» e l'avviso di nuova iscrizione; in «Template email» l'avviso alla segreteria; in «Utenti» i permessi della 1.2
  (un solo interruttore per sezione, Corsi con lettura e scrittura, Pagamenti corsi); menu e schermate aggiornati.
- **Guida, aggiornamento dal pannello**: procedura passo per passo con le schermate; schermate rifatte senza le indicazioni della versione
  dimostrativa.

## 1.2.1 — 2026-11-06
- **Correzione importante: modulo pubblico di iscrizione ai corsi.** Nella 1.2.0 un errore nello script della pagina bloccava il modulo
  (scelta della modalità, nominativi e invio non funzionavano). Chi usa la 1.2.0 deve aggiornare.
- **Guida nel pannello** (voce «Guida» del menu, per tutti gli utenti): mostra i documenti della cartella `docs/`, gli stessi pubblicati sul
  repository, con indice, immagini e collegamenti tra le pagine; ogni pagina si scarica in PDF. I collegamenti a script e file fuori dalla
  guida aprono il repository alla versione installata.
- **Documentazione riorganizzata**: un livello di cartelle numerate, da `00-installazione` (con il primo accesso) ai gruppi del menu del
  pannello, un documento per voce. La cartella `docs/` è ora inclusa nel pacchetto della release e viene sostituita dall'aggiornamento dal
  pannello.
- **Installazione su hosting condiviso**: la guida ha le schermate di ogni passaggio e due casi in più tra i problemi (pannelli con nginx
  davanti ad Apache, pagina «504 Gateway Timeout» durante l'estrazione); la pagina finale dell'installer indica la posta in uscita nel menu
  Configurazione.
- **Guida, sezione Primi passi**: nuovi documenti «Accesso al pannello» e «Il pannello in breve», con le schermate; «Immagini e file»
  completato con le comunicazioni ai soci e i documenti riservati.
- **Guida, sezione Contenuti**: schermate rifatte sulla versione attuale; nel documento dei post una sezione sui campi utili alla SEO;
  tolti i riferimenti al permesso di sola lettura, che per queste sezioni non esiste più.
- **Pannello**: il menu utente in alto a destra dice «Esci» invece di «Logout»; la ricerca nel menu mostra una sola × per svuotarla.
- **Backup automatico del database a ogni aggiornamento**: `scripts/deploy.sh` esegue `backup.sh` prima di scaricare il codice e di
  aggiornare il database; se il backup non riesce, l'aggiornamento si ferma. `--quick` resta senza backup.
- **`scripts/backup.sh` salva di default solo il database**; con `--all` aggiunge l'archivio dei file (`.env` e file caricati dal
  pannello). Il vecchio `--only-db` è ancora accettato e non cambia nulla.

## 1.2.0 — 2026-11-05
- **Gestione dei pagamenti dei corsi** (funzione facoltativa, da attivare in Funzioni attive): per ogni corso a pagamento una pagina elenca i
  blocchi di fatturazione, cioè una riga per ogni fattura da emettere, con intestatario, metodo di pagamento, iscritti coinvolti e importo
  totale. Con un clic sulla riga si apre una finestra con i **dati di fatturazione pronti da copiare** per la fattura elettronica (un pulsante per
  ogni campo e uno per copiarli tutti; i dati necessari alla fattura ancora vuoti sono segnalati come «mancante»), una **guida alle righe da inserire in fattura**
  (generata dal sistema, copiabile) e i campi per registrare numero e data di **fattura** e, facoltativamente, di **ricevuta** (due documenti
  distinti, che possono coesistere) e l'**incasso**. L'importo incassato è proposto in automatico e, se diverso dal totale, richiede una nota
  che ne spieghi il motivo. Un numero di fattura già registrato nello stesso anno (anche su un altro corso) produce un avviso, ma il
  salvataggio avviene comunque; i numeri di ricevuta non sono controllati. Il permesso **Pagamenti corsi** è separato da quello dei Corsi, così l'amministrazione può accedere solo a questa
  parte.
- **IVA dei corsi** (facoltativa): le tipologie e i singoli corsi possono indicare che il costo è comprensivo di IVA e l'aliquota. Il costo
  resta il prezzo finale mostrato agli iscritti; l'IVA serve a scorporare imponibile e imposta nella guida alla fattura. Con costo zero non è
  attivabile. Gli elenchi di tipologie e corsi mostrano a colpo d'occhio se l'IVA è impostata (con l'aliquota). Se si modifica l'IVA di un corso
  con fatture già registrate compare un avviso informativo.
- **Corsi gratuiti**: non richiedono più i dati di fatturazione né il metodo di pagamento, nel modulo pubblico come nell'inserimento manuale.
- **Anagrafica completa** (per tipologia, pensata per il rilascio degli attestati): data e luogo di nascita richiesti agli iscritti, con la
  comunicazione che servono per l'attestato. Il luogo è Italia (comune e provincia) o uno Stato estero scelto da un elenco aggiornabile, con
  l'opzione «Altro» per gli Stati non presenti; per l'estero la provincia è sempre «EE». Per queste tipologie il controllo del codice fiscale
  è più rigoroso: si verificano anche il carattere di controllo, la corrispondenza con la data di nascita indicata e la coerenza tra Italia e
  estero. Nell'elenco iscritti un filtro «Dati mancanti» (con icona di avviso accanto al nome) segnala chi si è iscritto prima dell'attivazione e non ha data o luogo di nascita. Data e luogo di nascita compaiono nell'esportazione degli iscritti e l'informativa privacy predefinita dei corsi li elenca.
- **Elenco degli Stati esteri** (tabella riutilizzabile, con circa 200 Stati) e relativo aggiornamento automatico dell'informativa privacy.
- **Elenco ed esportazioni degli iscritti** (elenco nel pannello, PDF, Excel, CSV ed elenco da stampare): non contengono più intestatario
  della fatturazione e metodo di pagamento, che si consultano nella gestione dei pagamenti. Nell'elenco da stampare resta l'indicazione
  «pagamento di gruppo» sotto il nome.
- **Modulo pubblico di iscrizione**: dopo un errore di validazione tutti i dati inseriti restano compilati (nominativi, residenza, dati di
  nascita, fatturazione e metodo di pagamento), così si corregge solo il campo segnalato. Se Italia o estero non corrispondono al codice
  fiscale, il messaggio indica lo Stato scelto.
- **Persone** (anagrafica e consensi dei corsi): visibile solo a chi ha il permesso Corsi in scrittura; la sola lettura dà accesso a corsi
  e iscritti.
- **Menu del pannello riorganizzato**: due titoli fissi, «Lavoro quotidiano» (Contenuti, Formazione, Area soci) e «Amministrazione»
  (Comitato e sito, Configurazione, Classificazioni, Utenti e controllo), con gruppi richiudibili che ricordano se sono aperti o chiusi.
  «Membri» passa nel gruppo Area soci. Barra laterale fissa con scorrimento proprio e piè di pagina
  del pannello con versione e link al progetto. Nessuna funzione cambiata, solo la posizione delle voci.
- **«Dati del comitato» diviso in quattro pagine**: Dati del comitato (anagrafica, contatti e social), Loghi e immagini, Coordinate e
  pagamenti (IBAN e metodi accettati) nel gruppo Comitato e sito; **Funzioni attive** (Area soci, Pagamenti dei corsi, Statistiche) nel
  gruppo Configurazione, con interruttori a scorrimento. Ogni pagina salva solo i propri campi; i dati già inseriti restano invariati.
- **Interfaccia del pannello**: le note esplicative dei moduli sono nell'icona «i» accanto al campo, con un suggerimento di lettura più
  grande; le liste a scelta con più di tre voci hanno il campo di ricerca (pannello e moduli pubblici); gli interruttori della tipologia di
  corso sono a scorrimento.

## 1.1.12 — 2026-11-04
- **Permessi degli utenti semplificati**: tutte le sezioni (Post, Pagine, Trasparenza, Documenti, Immagini, File, Comunicazioni soci, Membri)
  hanno un solo interruttore «Lettura e scrittura»; solo i Corsi mantengono lettura e scrittura separate. Nel modulo utenti le sezioni a
  permesso unico sono in una tabella, i Corsi in una seconda. Per i Corsi, attivare la Scrittura attiva anche la Lettura e disattivare la
  Lettura disattiva anche la Scrittura.
- Alla migrazione, chi aveva già un permesso (anche di sola lettura) su una delle sezioni a permesso unico passa a lettura e scrittura.
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
