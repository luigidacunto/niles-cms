# NILES

**N**ews & **I**nformation **L**ightweight **E**ditorial **S**ystem

CMS leggero e open source per i comitati locali della Croce Rossa Italiana: sito pubblico, pannello di
amministrazione, iscrizioni online ai corsi rivolti alla popolazione e un'area riservata ai soci per comunicazioni interne o materiali destinati ai soli soci. Un'installazione per comitato, pensata
per girare anche su qualsiasi tipo di hosting.

> Progetto indipendente, non è un prodotto ufficiale della Croce Rossa Italiana, ma è sviluppato da un volontario ormai di lunga data.

**Demo online**: <https://demo.niles.luigidacunto.com> — sito e pannello di prova con contenuti inventati. Le credenziali sono
indicate nella pagina di accesso del pannello (`/admin/login`); le modifiche non vengono salvate.

**Documentazione**: guida all'installazione, alla configurazione iniziale e a ogni sezione del pannello in [docs/](docs/README.md).

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

PHP 8.4, MariaDB 10.11 (MySQL è supportato, ma provato in misura minore), Composer 2.10 e Node.js 24 con npm 11 (solo per compilare gli
asset). Sono le versioni minime provate: con versioni precedenti il funzionamento non è garantito. L'elenco delle estensioni PHP, i limiti di
caricamento e come controllare le versioni sono in [Requisiti](docs/01-installazione/01-requisiti.md).

## Installazione su un server

Riepilogo per un server Linux in produzione, con il dominio che punta alla cartella `public/` del progetto:

```bash
git clone https://github.com/luigidacunto/niles-cms.git .
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
# nel .env: APP_ENV=production, APP_DEBUG=false, APP_URL, APP_PUBLIC_NAME, DB_*, INITIAL_ADMIN_*, MAIL_*
npm ci && npm run build
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

Poi si apre `https://<dominio>/admin/login/password` e si accede con le credenziali del primo amministratore.

La [guida all'installazione](docs/01-installazione/02-installazione-su-server.md) spiega la procedura completa, con i controlli passo per passo,
l'utente con cui lanciare i comandi, la configurazione del server web e la soluzione dei problemi più comuni. Le variabili del file `.env` sono
in [Configurazione del file .env](docs/01-installazione/03-configurazione-env.md); per gli aggiornamenti e i backup vedi
[Aggiornamento e backup](docs/01-installazione/04-aggiornamento.md).

## Sviluppo in locale

```bash
git clone https://github.com/luigidacunto/niles-cms.git
cd niles-cms
composer install
cp .env.example .env
php artisan key:generate
# configura DB_* nel .env, poi:
php artisan migrate
php artisan db:seed
php artisan storage:link
npm ci
npm run build
php artisan serve
```

Il sito è su `http://localhost:8000` e il pannello su `/admin`. Per lo sviluppo con ricarica automatica degli
asset usa `npm run dev` al posto della build.

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
