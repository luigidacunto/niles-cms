# Documentazione di NILES

Guide per installare, configurare e usare NILES, il CMS per i comitati locali della Croce Rossa Italiana. Per una panoramica del
progetto e per l'installazione in breve vedi il [README](../README.md).

## Installazione

Per mettere NILES online su un server.

1. [Requisiti](installazione/requisiti.md): versioni di PHP, database, Node e Composer, estensioni e limiti di caricamento.
2. [Installazione su un server](installazione/installazione-su-server.md): la procedura passo per passo.
3. [Configurazione del file .env](installazione/configurazione-env.md): tutte le variabili, spiegate.
4. [Aggiornamento e backup](installazione/aggiornamento.md): nuove versioni e copie di sicurezza.

## Configurazione iniziale del pannello

Cosa fare dopo l'installazione, nell'ordine consigliato: cosa inserire, dove inserirlo e quali immagini caricare.

1. [Primo accesso al pannello](configurazione-iniziale/primo-accesso.md)
2. [Dati del comitato](configurazione-iniziale/dati-del-comitato.md): denominazione, contatti, loghi e favicon, statistiche.
3. [Menu e pagine](configurazione-iniziale/menu-e-pagine.md): le pagine create dall'installazione e come attivarle.
4. [Post e categorie](configurazione-iniziale/post-e-categorie.md)
5. [Struttura organizzativa](configurazione-iniziale/struttura-organizzativa.md)
6. [Trasparenza](configurazione-iniziale/trasparenza.md)
7. [Libreria documenti](configurazione-iniziale/libreria-documenti.md)
8. [Corsi di formazione](configurazione-iniziale/corsi-di-formazione.md)
9. [Area soci](configurazione-iniziale/area-soci.md)
10. [Utenti e accessi](configurazione-iniziale/utenti-e-accessi.md)
11. [Sicurezza dei moduli pubblici](configurazione-iniziale/sicurezza-moduli.md)
12. [Informative privacy ed email](configurazione-iniziale/informative-privacy-ed-email.md)

Da consultare durante la configurazione: [Immagini e file](configurazione-iniziale/immagini-e-file.md), con formati,
dimensioni, pesi e margini di loghi, copertine, foto e documenti.

## Organizzazione della cartella

```
docs/
  README.md                    questo indice
  installazione/               requisiti, installazione, .env, aggiornamento
  configurazione-iniziale/     una pagina per ogni area del pannello, più «Immagini e file»
  immagini/                    schermate usate dalle guide, una cartella per documento (si crea con le prime schermate)
```

Ogni argomento sta in un solo documento: gli altri rimandano con un collegamento.
