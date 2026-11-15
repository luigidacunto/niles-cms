# NILES

**N**ews & **I**nformation **L**ightweight **E**ditorial **S**ystem

CMS leggero e open source per i comitati locali della Croce Rossa Italiana: sito pubblico, pannello di
amministrazione, iscrizioni online ai corsi rivolti alla popolazione e un'area riservata ai soci per comunicazioni interne o materiali destinati ai soli soci. Un'installazione per comitato.

**Si installa in due modi**: su un **server con accesso da riga di comando (SSH)**, oppure su un **comune hosting condiviso con solo
accesso FTP e un database MySQL**, senza comandi, Composer o Node: basta caricare un file e seguire la procedura guidata dal browser.
Vedi [Installazione](#installazione).

> Progetto indipendente, non è un prodotto ufficiale della Croce Rossa Italiana, ma è sviluppato da un volontario ormai di lunga data.

**Demo online**: <https://demo.niles.luigidacunto.com> — sito e pannello di prova con contenuti inventati. Le credenziali sono
indicate nella pagina di accesso del pannello (`/admin/login`); le modifiche non vengono salvate.

**Documentazione**: guida all'installazione (su [server](docs/00-installazione/02-installazione-su-server.md) o su
[hosting condiviso](docs/00-installazione/05-installazione-su-hosting-condiviso.md)), alla configurazione iniziale e a ogni sezione
del pannello in [docs/](docs/README.md).

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

Comuni a entrambe le installazioni: PHP 8.4 con le estensioni indicate nei [Requisiti](docs/00-installazione/01-requisiti.md) e un database
MariaDB 10.11 o MySQL (MariaDB è la più provata). Sono le versioni minime provate: con versioni precedenti il funzionamento non è garantito.

- **Server con SSH**: in più Composer 2.10 e Node.js 24 con npm 11 (solo per compilare gli asset).
- **Hosting condiviso**: Apache con `.htaccess` e `mod_rewrite` attivi, un dominio o sottodominio **dedicato** a NILES con HTTPS (l'installazione
  in una sottocartella non è supportata) e un database vuoto. Composer e Node **non servono**.

L'elenco delle estensioni PHP, i limiti di caricamento e come controllare le versioni sono in [Requisiti](docs/00-installazione/01-requisiti.md).

## Installazione

### Hosting condiviso (solo FTP e database)

Per un hosting che offre solo l'accesso ai file (FTP o file manager) e un database, senza SSH. Tempo previsto: circa un quarto d'ora.

1. Nel pannello del provider scegliere **PHP 8.4** per il dominio e creare un **database vuoto** (servono host, nome, utente e password).
2. Dalla pagina **Releases** di questo repository scaricare il file **`niles-installer.php`** (tra gli *Assets* dell'ultima versione) e caricarlo
   via FTP nella cartella servita dal dominio.
3. Aprire `https://<dominio>/niles-installer.php` e seguire la procedura: controllo dei requisiti, download e installazione del pacchetto (circa
   50 MB, uno o due minuti), poi dati del database, nome del comitato e primo amministratore. Al termine il file si cancella da solo.

Poi si accede al pannello da `https://<dominio>/admin/login/password`. Gli aggiornamenti successivi si fanno **dal pannello**, con il pulsante
*Aggiorna* in dashboard. Se l'hosting non permette di scaricare file da internet, il pacchetto `niles.zip` si carica a mano accanto all'installer.
La [guida all'installazione su hosting condiviso](docs/00-installazione/05-installazione-su-hosting-condiviso.md) descrive ogni passo, con i
controlli e la soluzione dei problemi più comuni.

### Server con accesso SSH

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

La [guida all'installazione](docs/00-installazione/02-installazione-su-server.md) spiega la procedura completa, con i controlli passo per passo,
l'utente con cui lanciare i comandi, la configurazione del server web e la soluzione dei problemi più comuni. Le variabili del file `.env` sono
in [Configurazione del file .env](docs/00-installazione/03-configurazione-env.md); per gli aggiornamenti e i backup vedi
[Aggiornamento e backup](docs/00-installazione/04-aggiornamento.md).

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
