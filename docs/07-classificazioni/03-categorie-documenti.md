# Categorie documenti

In **Categorie documenti** (menu *Classificazioni*) si gestiscono le categorie della [Libreria documenti](../02-contenuti/05-libreria-documenti.md),
che servono a ritrovare i file nell'elenco e a filtrarli. Non sono visibili ai visitatori del sito. La sezione è riservata agli
amministratori.

![Categorie della libreria documenti](../immagini/libreria-documenti/categorie.png)

L'elenco mostra l'**Ordine**, il **Nome**, lo **Slug** e il numero di **Documenti** di ogni categoria. L'installazione crea la categoria
**Documenti** (quella generica) e **Organigramma**.

## La categoria Organigramma

La categoria **Organigramma** è **riservata** e creata dal sistema: non si può eliminare e non compare nel menu a tendina quando si
carica un documento, perché ci finisce il file in automatico.

- **Organigramma**: contiene l'organigramma caricato dalla pagina [Struttura organizzativa](../02-contenuti/03-struttura-organizzativa.md),
  conservato come storico anche quando lo si toglie dalla pagina.

I [documenti riservati](../04-area-soci/03-documenti-riservati.md) ai soci non passano da qui: hanno le proprie
[categorie riservate](../04-area-soci/04-categorie-riservate.md), si aprono solo dopo l'accesso all'area soci e non compaiono nella
libreria né in questo elenco.

## Creare o modificare una categoria

**Nuova categoria** apre il form con quattro campi:

- **Nome**: come compare nel pannello.
- **Slug**: l'identificativo interno, in lettere minuscole, numeri e trattini; deve essere unico.
- **Descrizione**: facoltativa.
- **Ordine**: la posizione nell'elenco e nel menu a tendina del form dei documenti (0 = prima).

Le aree della pagina pubblica *Trasparenza* non si gestiscono qui ma in [Aree Trasparenza](02-aree-trasparenza.md).
