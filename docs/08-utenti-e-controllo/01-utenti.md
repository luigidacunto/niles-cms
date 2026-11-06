# Utenti

In **Utenti** (menu *Utenti e controllo*) si gestiscono le persone che accedono al pannello: **amministratori** ed **editor**. La sezione è
riservata agli amministratori.

## Amministratori ed editor

- **Amministratore**: accesso completo a tutte le sezioni del pannello.
- **Editor**: vede e modifica solo ciò che gli viene assegnato, tramite i **permessi** per sezione e le **categorie di competenza** (vedi
  sotto). Non vede la parte *Amministrazione* del menu (Comitato e sito, Configurazione, Classificazioni, Utenti e controllo) né
  Struttura organizzativa e Tipologie corso, riservate agli amministratori.

Non c'entrano con gli utenti dell'**area soci**, che sono i soci e accedono con il codice via email dopo essere stati inseriti in
[Membri](../04-area-soci/01-membri.md).

## L'elenco

![Elenco degli utenti del pannello](../immagini/utenti/elenco.png)

Per ogni utente l'elenco mostra **Nome**, **Email**, **Ruolo**, le **Categorie** di competenza, se l'accesso con **Password** è abilitato e se
l'account è **Attivo**. **Nuovo utente** ne crea uno. Il primo amministratore, creato dall'installazione, è l'**amministratore protetto**:
non si può eliminare e il suo ruolo non si può cambiare (nome, email e password sì), perché ci sia sempre almeno un accesso completo.

## Creare o modificare un utente

![Modifica di un utente editor](../immagini/utenti/modifica-editor.png)

- **Nome** ed **Email**: l'email è anche l'indirizzo a cui arriva il codice di accesso.
- **Ruolo**: Editor o Admin.
- **Account attivo**: se spento l'utente non può più entrare, ma l'account resta e si può riattivare.
- **Abilita login con password**: di norma si entra solo con il **codice via email**. Con questa opzione l'utente può accedere anche da
  `/admin/login/password`; in questo caso va impostata una **Password** (in modifica, lasciare il campo vuoto la lascia invariata). Senza
  un server di posta configurato l'accesso con la password è l'unico possibile: vedi [Configurazione del file .env](../00-installazione/03-configurazione-env.md).

### Permessi

Solo per gli editor (un amministratore ha tutto). Il modulo ha due tabelle.

**Sezioni con un solo interruttore «Lettura e scrittura»**: chi ha il permesso vede e modifica la sezione.

| Permesso | Cosa consente |
|---|---|
| Post | i [post](../02-contenuti/01-post.md), limitatamente alle categorie di competenza |
| Pagine | le [pagine](../02-contenuti/02-pagine.md): testo, estratto, ordine e pubblicazione (struttura e codice embed restano agli amministratori) |
| Trasparenza | i documenti della sezione [Trasparenza](../02-contenuti/04-trasparenza.md) |
| Documenti | la [libreria documenti](../02-contenuti/05-libreria-documenti.md) |
| Comunicazioni soci | le [comunicazioni](../04-area-soci/02-comunicazioni-soci.md), i [documenti riservati](../04-area-soci/03-documenti-riservati.md) e le [categorie riservate](../04-area-soci/04-categorie-riservate.md) (solo con l'area soci attiva) |
| Membri | l'elenco dei [membri](../04-area-soci/01-membri.md) (solo con l'area soci attiva) |
| Pagamenti corsi | la [gestione dei pagamenti](../03-formazione/03-pagamenti-corsi.md) (solo con la funzione attiva); è indipendente dal permesso Corsi |

Le righe **Immagini** e **File** sono previste per usi futuri e oggi non controllano nessuna funzione.

**Corsi**, con lettura e scrittura separate:

- **Lettura**: consulta [corsi](../03-formazione/01-corsi.md), iscritti ed elenchi, ed esporta;
- **Scrittura**: crea e modifica i corsi, gestisce gli iscritti e vede anche le [persone](../03-formazione/02-persone.md).

Attivando la scrittura si attiva anche la lettura; togliendo la lettura si toglie anche la scrittura.

### Categorie di competenza

Solo per gli editor. Decidono su quali **post** può lavorare: **Tutte le categorie** oppure un elenco preciso. La categoria **Notizie** è sempre
inclusa, perché è la categoria generica minima per poter pubblicare. Vedi [Categorie](../07-classificazioni/01-categorie.md).

## Eliminare un utente

L'eliminazione è definitiva. Il pannello la rifiuta per l'amministratore protetto, per il proprio account e per l'ultimo amministratore
rimasto. Per sospendere qualcuno senza perdere l'account basta disattivarlo.

Gli accessi di ogni utente sono registrati nel [Registro accessi](02-registro-accessi.md).
