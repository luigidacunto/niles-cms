# NILES

**N**ews & **I**nformation **L**ightweight **E**ditorial **S**ystem

CMS leggero e open source per i comitati locali della Croce Rossa Italiana: sito pubblico, pannello di
amministrazione, iscrizioni online ai corsi rivolti alla popolazione e un'area riservata ai soci per comunicazioni interne o materiali destinati ai soli soci. Un'installazione per comitato, pensata
per girare anche su qualsiasi tipo di hosting.

> Progetto indipendente, non è un prodotto ufficiale della Croce Rossa Italiana, ma è sviluppato da un volontario ormai di lunga data.

## Funzionalità

- **Sito pubblico**: notizie con categorie e tag, pagine istituzionali, menu configurabile, archivi,
  sezione Trasparenza, Struttura organizzativa, SEO (sitemap, dati strutturati, anteprime social).
- **Corsi di formazione** per la popolazione: catalogo corsi, iscrizioni online anche per più persone, dati di fatturazione, email
  di conferma con promemoria per il calendario, export CSV/Excel/PDF, protezione anti-bot (limiti e CAPTCHA
  Cloudflare Turnstile opzionale).
- **Persone e privacy**: anagrafica con consensi separati (promemoria, newsletter), pagina personale per gestire
  i propri consensi, richieste di cancellazione, informative privacy personalizzabili.
- **Area soci** (opzionale): accesso con codice via email, comunicazioni interne, documenti riservati, import
  dell'elenco soci da Excel.
- **Documenti e media**: libreria documenti con aree pubbliche e riservate, immagini ridimensionate al caricamento.
- **Pannello di amministrazione**: ruoli (amministratore, redattore con permessi per sezione), accesso con codice
  via email o password opzionale, registro degli accessi, template email modificabili.
- **Cookie e statistiche**: banner di consenso, statistiche senza cookie con GoatCounter e Google
  Analytics 4 opzionale, blocco degli incorporamenti esterni fino al consenso.

Ogni dato specifico del comitato (denominazione, contatti, loghi, IBAN, link social) si imposta dal pannello,
non nel codice.

## Requisiti

Versioni minime provate (con versioni precedenti il funzionamento non è garantito):

- PHP 8.4 con le estensioni standard di Laravel più `gd`
- MariaDB 10.11 (MySQL è supportato, ma provato in misura minore), con un database e un utente dedicati
- [Composer](https://getcomposer.org/) 2.10, installato dal sito ufficiale (i pacchetti delle distribuzioni sono spesso troppo vecchi e con PHP 8.4 stampano molti avvisi)
- Node.js 24 e npm 11 (solo per compilare gli asset)

## Installazione

```bash
git clone <url-del-repository> niles
cd niles

composer install
cp .env.example .env
php artisan key:generate
```

Configura nel file `.env` il database (`DB_*`), l'indirizzo del sito (`APP_URL`), il nome pubblico
(`APP_PUBLIC_NAME`) e l'invio delle email (`MAIL_*`; con `MAIL_MAILER=log` le email finiscono nel log). Poi:

```bash
php artisan migrate
php artisan db:seed
php artisan storage:link
npm ci
npm run build
```

Per provarlo in locale: `php artisan serve` (sito su `http://localhost:8000`, pannello su `/admin`).

Il seeder crea il primo amministratore con le credenziali indicate nel `.env` prima di `db:seed`:
`INITIAL_ADMIN_EMAIL` e `INITIAL_ADMIN_PASSWORD`. Se la password non è impostata ne viene generata una casuale,
mostrata una sola volta al termine del comando (email di partenza: `administrator@example.it`): annotala e
cambiala al primo accesso.

Gli script [`scripts/deploy.sh`](scripts/deploy.sh) e [`scripts/backup.sh`](scripts/backup.sh) automatizzano
aggiornamento e backup su un server Linux; ciascuno può adattarli al proprio ambiente di pubblicazione.

## Test

```bash
php artisan test
```

## Stack

PHP · Laravel 13 · MySQL/MariaDB · Blade, Tailwind CSS e Alpine.js per il sito pubblico · AdminLTE per il
pannello di amministrazione. Pagine renderizzate dal server, nessuna SPA e nessuna API separata.

## Marchi e loghi

Il codice è rilasciato con licenza libera, ma i marchi, gli emblemi e i loghi della Croce Rossa e di altre
organizzazioni **non fanno parte del software** e non sono inclusi nel repository: restano soggetti alle norme
e ai regolamenti dei rispettivi titolari. I loghi istituzionali, il logo del comitato e la favicon si caricano
dal pannello; senza file, il sito usa un'icona generica e semplici link testuali. Chi installa NILES è
responsabile di usare solo marchi di cui ha diritto.

## Contribuire e sicurezza

Le segnalazioni e le proposte sono benvenute: vedi [CONTRIBUTING.md](CONTRIBUTING.md). Per segnalare una
vulnerabilità segui le indicazioni in [SECURITY.md](SECURITY.md).

## Licenza

Distribuito con licenza [GNU Affero General Public License v3.0 o successiva](LICENSE). In breve: puoi usare,
studiare, modificare e ridistribuire il software; se offri una versione modificata come servizio online, devi
rendere disponibile il codice delle tue modifiche con la stessa licenza.
