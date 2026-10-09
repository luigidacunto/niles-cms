# Installazione su un server

Procedura per mettere NILES online su un server Linux, con un dominio e HTTPS. Tempo previsto: circa mezz'ora. Prima di
iniziare controlla i [requisiti](requisiti.md): PHP 8.4, MariaDB, Composer 2.10, Node 24, Git.

Negli esempi il dominio è `esempio.it` e il progetto sta in `/var/www/niles`: adatta i nomi al tuo server. Tutti i comandi
vanno lanciati con l'utente con cui gira il sito: vedi la sezione seguente.

## Con quale utente lanciare i comandi

Tutti i comandi di questa guida (`git`, `composer`, `npm`, `php artisan`) vanno lanciati con **lo stesso utente con cui gira il
sito**, mai come `root` e mai con `sudo`. Se i file vengono creati da `root`, PHP non può scrivere nella cartella `storage/` e il
sito risponde con un errore 500.

Quale sia l'utente dipende da come è configurato il server:

- **Server pulito**, senza pannello (Nginx o Apache con PHP-FPM installati dai pacchetti): l'utente è `www-data` su Debian e
  Ubuntu, `nginx` o `apache` sulla famiglia RHEL (AlmaLinux, Rocky). Prepara la cartella e apri una shell con quell'utente:

  ```bash
  sudo mkdir -p /var/www/niles
  sudo chown www-data:www-data /var/www/niles
  sudo -u www-data -H bash
  export COMPOSER_HOME=/tmp/composer-home NPM_CONFIG_CACHE=/tmp/npm-cache
  ```

  Le due variabili servono perché `www-data` non ha una cartella personale scrivibile: senza, Composer e npm segnalano errori
  di cache. Da questa shell prosegui con i passi seguenti; per uscire digita `exit`.

- **Server con un pannello di gestione** (HestiaCP, ISPConfig, cPanel e simili): ogni sito ha un suo utente di sistema, ed è
  quello da usare. Accedi via SSH con quell'utente (con la shell abilitata dal pannello: in HestiaCP è l'utente a cui appartiene il
  dominio, in ISPConfig l'utente del sito, per esempio `web1`, in cPanel l'utente dell'account) e lavora nella cartella del
  dominio indicata dal pannello, per esempio `~/web/esempio.it/public_html` in HestiaCP o `~/public_html` in cPanel, al posto di
  `/var/www/niles`.

Per controllare l'utente in uso digita `whoami`; per sapere con quale utente gira PHP-FPM, `ps -eo user,comm | grep php-fpm`
(il processo principale è di `root`, quelli di lavoro mostrano l'utente del sito).

Fanno eccezione i passi che riguardano il sistema e non il progetto: creare il database (passo 1) e configurare il server web e il
certificato (passo 6) si fanno con un utente amministratore (`sudo`) o dal pannello, in una shell separata.

## 1. Database

Crea un database vuoto e un utente dedicato, con una password lunga e casuale. Da riga di comando:

```sql
CREATE DATABASE niles CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'niles'@'localhost' IDENTIFIED BY 'una-password-lunga-e-casuale';
GRANT ALL PRIVILEGES ON niles.* TO 'niles'@'localhost';
FLUSH PRIVILEGES;
```

Con un pannello come HestiaCP o cPanel si fa dalla sezione Database, con le stesse impostazioni (charset `utf8mb4`).
Annota nome del database, utente e password: servono al passo 3.

## 2. Codice e dipendenze

```bash
cd /var/www/niles
git clone https://github.com/luigidacunto/niles-cms.git .
composer install --no-dev --optimize-autoloader
```

Il punto finale del `git clone` scarica il progetto direttamente nella cartella corrente, che deve essere vuota: se contiene file
di prova creati dal pannello (come `index.html`), cancellali prima.

Il ramo `main` contiene la versione stabile; le versioni pubblicate sono nella pagina Releases del repository. Per
installare una versione precisa: `git checkout v1.0.2` (sostituisci con la versione indicata in Releases) prima del `composer install`.

**Verifica:** il comando finisce senza errori e crea la cartella `vendor/`. Se compaiono errori sulla versione di PHP, vedi
[più versioni di PHP](requisiti.md#più-versioni-di-php-sullo-stesso-server).

## 3. Configurazione

```bash
cp .env.example .env
php artisan key:generate
```

Apri il file `.env` con un editor (`nano .env`) e imposta almeno queste voci:

```ini
APP_PUBLIC_NAME="Croce Rossa Italiana – Comitato di ..."
APP_ENV=production
APP_DEBUG=false
APP_URL=https://esempio.it

DB_DATABASE=niles
DB_USERNAME=niles
DB_PASSWORD="una-password-lunga-e-casuale"

INITIAL_ADMIN_EMAIL=nome@esempio.it
INITIAL_ADMIN_PASSWORD="una-password-iniziale-lunga"

MAIL_MAILER=log
```

- `APP_URL` è l'indirizzo pubblico completo, con `https://`.
- Le password che contengono caratteri speciali (`# $ = ?` o spazi) vanno **tra virgolette doppie**.
- `INITIAL_ADMIN_*` sono le credenziali del primo amministratore. Se non le imposti, viene usato l'indirizzo
  `administrator@example.it` e una password casuale, mostrata una sola volta al passo 4.
- `MAIL_MAILER=log` va bene per iniziare: le email di sistema non vengono spedite. Per l'invio vero vedi
  [Configurazione del file .env](configurazione-env.md).

Tutte le variabili sono spiegate in [Configurazione del file .env](configurazione-env.md).

## 4. Asset, database e contenuti iniziali

```bash
npm ci
npm run build
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
```

- `npm ci` e `npm run build` scaricano e compilano gli stili e gli script del sito (un paio di minuti).
- `migrate` crea le tabelle del database.
- `db:seed` crea il primo amministratore e i contenuti di base: menu, categorie, pagine di sistema, tipologie di corso,
  informative privacy e testi delle email. **Se non hai impostato `INITIAL_ADMIN_PASSWORD`, la password casuale compare qui, una sola
  volta: annotala.**
- `storage:link` rende raggiungibili dal sito le immagini e i file caricati.

**Verifica:** nessuno dei comandi mostra errori; `php artisan about` mostra l'ambiente `production` e il debug disattivato.

## 5. Cache e permessi

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
chmod -R ug+rwX storage bootstrap/cache
```

Le cartelle `storage/` e `bootstrap/cache/` devono essere scrivibili dall'utente con cui gira PHP-FPM. Se i comandi sopra
sono stati lanciati dallo stesso utente del sito, di solito basta così.

> Ogni volta che modifichi il file `.env` rilancia `php artisan config:cache`: senza, il sito continua a usare i valori vecchi.

## 6. Server web e HTTPS

Imposta il dominio in modo che la **radice dei documenti** sia la cartella `public/` del progetto (per esempio
`/var/www/niles/public`), non la cartella principale: il resto del codice non deve essere raggiungibile dal web. Attiva il
certificato HTTPS (per esempio con Let's Encrypt) e usa PHP-FPM 8.4 per il dominio.

- **Con un pannello** (HestiaCP, cPanel, Plesk): scegli il modello di sito «Laravel», oppure imposta la radice su
  `.../public`, e seleziona PHP 8.4 per il dominio.
- **Apache**: il file `public/.htaccess` è già incluso; servono `mod_rewrite` attivo e `AllowOverride All` sulla cartella.
- **Nginx**: usa la configurazione standard di Laravel, in particolare `try_files $uri $uri/ /index.php?$query_string;`
  nella `location /` e il passaggio dei file `.php` a PHP-FPM 8.4.

## 7. Verifica finale e primo accesso

1. Apri `https://esempio.it`: deve comparire la home del sito, con il menu già completo.
2. Apri `https://esempio.it/up`: deve rispondere con una pagina che conferma che l'applicazione è attiva.
3. Apri `https://esempio.it/admin/login/password` e accedi con l'email e la password del primo amministratore.
   L'accesso con il codice via email, sulla pagina `/admin/login`, funziona solo dopo aver configurato un vero server di posta.

Da qui prosegui con [Primo accesso al pannello](../configurazione-iniziale/primo-accesso.md).

## Problemi più comuni

| Sintomo | Causa e rimedio |
|---|---|
| Si vede l'elenco dei file o un errore invece della home | La radice del sito non è `public/`: correggila nel pannello o nella configurazione del server web. |
| «500» o pagina bianca | Guarda `storage/logs/laravel.log`. Di solito mancano i permessi su `storage/` e `bootstrap/cache/`, oppure i comandi sono stati lanciati da `root` e i file appartengono a `root`: `chown -R utente:gruppo storage bootstrap/cache`. |
| `composer` o `artisan` segnalano la versione di PHP sbagliata | Il comando `php` del terminale non è quello di PHP-FPM: usa `php8.4 artisan ...` e `php8.4 /usr/local/bin/composer ...` (vedi i [requisiti](requisiti.md)). |
| Le modifiche al `.env` non hanno effetto | Rilancia `php artisan config:cache`. |
| Le immagini caricate non si vedono | Manca il collegamento: `php artisan storage:link`, poi controlla che esista `public/storage`. |
| La password del database non viene accettata | Contiene caratteri speciali e non è tra virgolette doppie nel `.env`. |
| Il codice di accesso via email non arriva | Con `MAIL_MAILER=log` le email non vengono spedite: accedi con la password da `/admin/login/password`, oppure configura la posta in [Configurazione del file .env](configurazione-env.md). |
