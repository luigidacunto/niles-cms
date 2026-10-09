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

- PHP 8.4 con le estensioni `ctype`, `curl`, `dom`, `fileinfo`, `filter`, `gd`, `iconv`, `json`, `libxml`, `mbstring`,
  `openssl`, `pcre`, `pdo_mysql`, `phar`, `session`, `simplexml`, `tokenizer`, `xml`, `xmlreader`, `xmlwriter`, `zip`
  e `zlib` (la maggior parte è già inclusa in una normale installazione)
- MariaDB 10.11 (MySQL è supportato, ma provato in misura minore), con un database e un utente dedicati
- [Composer](https://getcomposer.org/) 2.10, installato dal sito ufficiale (i pacchetti delle distribuzioni sono spesso troppo vecchi e con PHP 8.4 stampano molti avvisi)
- Node.js 24 e npm 11 (solo per compilare gli asset)

## Installazione su un server

Procedura per un server Linux in produzione. Per provare l'applicazione in locale vedi [Sviluppo in locale](#sviluppo-in-locale).

1. **Database**: crea un database vuoto (charset `utf8mb4`) e un utente dedicato con tutti i permessi su quel database.
2. **Sito web**: configura il dominio con HTTPS e imposta come radice dei documenti la cartella `public/` del progetto
   (non la cartella principale: il resto del codice non deve essere raggiungibile dal web). Il sito gira con PHP-FPM
   in PHP 8.4. Non servono cron né worker di coda.
3. **Codice e dipendenze**:

   ```bash
   git clone https://github.com/luigidacunto/niles-cms.git
   cd niles-cms
   composer install --no-dev --optimize-autoloader
   cp .env.example .env
   php artisan key:generate
   ```

   Il ramo `main` contiene la versione stabile; le versioni rilasciate sono nella pagina Releases del repository.
   Se sul server sono installate più versioni di PHP, `php` e `composer` possono usare una versione diversa da
   quella attesa: verifica con `php -v` e `composer diagnose` (voce «PHP binary path») e, se serve, usa
   `php8.4` in modo esplicito (`php8.4 /usr/local/bin/composer install ...`, `php8.4 artisan ...`).
4. **Configurazione**: modifica il file `.env`. Obbligatori: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL`
   (indirizzo pubblico con `https://`), `APP_PUBLIC_NAME` (nome del sito), `DB_*` (database creato al passo 1),
   `MAIL_*` (senza un vero server SMTP imposta `MAIL_MAILER=log`: le email finiscono in
   `storage/logs/laravel.log`, ma solo con `LOG_LEVEL=debug`) e, per il primo amministratore,
   `INITIAL_ADMIN_EMAIL` e `INITIAL_ADMIN_PASSWORD`. Tutte le variabili sono commentate in
   [`.env.example`](.env.example). Dopo ogni modifica al `.env` va rilanciato `php artisan config:cache`.
5. **Asset, database e contenuti iniziali**:

   ```bash
   npm ci
   npm run build
   php artisan migrate --force
   php artisan db:seed --force
   php artisan storage:link
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

   `db:seed` crea il primo amministratore e i contenuti di base (menu, categorie, pagine di sistema, tipologie di
   corso, informative e testi delle email). Se `INITIAL_ADMIN_PASSWORD` non è impostata, la password casuale viene
   mostrata una sola volta al termine del comando: annotala. Il menu nasce già completo e navigabile, con testi
   standard uguali per tutti i comitati (Storia e Principi, Statuto, Diventa Volontario, Cosa Facciamo, 5x1000) e
   segnaposto «Da completare» da riempire (Contatti, Dove Trovarci, codice fiscale per il 5x1000). Le pagine che
   non tutti i comitati hanno (Corpo Infermiere Volontarie, Diventa Infermiera Volontaria, Donazioni, Innovazione)
   nascono in bozza: ogni comitato attiva dal pannello quelle che usa.
6. **Permessi**: le cartelle `storage/` e `bootstrap/cache/` devono essere scrivibili dall'utente con cui gira PHP.
7. **Primo accesso**: apri `https://<dominio>/admin/login/password` e accedi con le credenziali del primo
   amministratore (l'accesso con codice via email è sulla pagina `/admin/login`). Poi compila **Dati del comitato**
   dal pannello: denominazione, contatti, loghi, coordinate bancarie e metodi di pagamento.

Gli aggiornamenti si fanno con [`scripts/deploy.sh`](scripts/deploy.sh) (`git pull`, dipendenze, build, migrazioni e
cache) e i backup con [`scripts/backup.sh`](scripts/backup.sh); ciascuno può adattarli al proprio ambiente di
pubblicazione. Prima di un aggiornamento in produzione conviene un backup del database.

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
