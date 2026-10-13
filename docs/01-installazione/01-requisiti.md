# Requisiti

NILES si installa su un server Linux (VPS, server dedicato o hosting con accesso SSH) con questi componenti. Le versioni indicate
sono le **minime provate**: con versioni precedenti il funzionamento non è garantito. Gli hosting condivisi senza accesso SSH
non sono al momento supportati.

| Componente | Versione | Note |
|---|---|---|
| PHP | 8.4 | con PHP-FPM per il sito e l'interfaccia a riga di comando (CLI) per i comandi |
| MariaDB | 10.11 | MySQL funziona, ma è provato meno; serve un database vuoto e un utente dedicato |
| Composer | 2.10 | da installare dal sito ufficiale, non dal pacchetto della distribuzione (vedi sotto) |
| Node.js e npm | Node 24, npm 11 | solo per compilare gli asset, durante l'installazione e gli aggiornamenti |
| Git | qualunque recente | per scaricare il codice e aggiornarlo |
| Server web | Apache o Nginx | con la radice del sito sulla cartella `public/` e HTTPS attivo |

Non servono cron né worker di coda.

## Estensioni PHP

Servono queste estensioni: `ctype`, `curl`, `dom`, `fileinfo`, `filter`, `gd`, `iconv`, `json`, `libxml`, `mbstring`,
`openssl`, `pcre`, `pdo_mysql`, `phar`, `session`, `simplexml`, `tokenizer`, `xml`, `xmlreader`, `xmlwriter`, `zip` e `zlib`.
Quasi tutte sono già incluse in PHP; di solito vanno aggiunte a parte solo `gd`, `mbstring`, `mysql`, `xml`, `zip` e `curl`
(su Debian e Ubuntu: `php8.4-gd php8.4-mbstring php8.4-mysql php8.4-xml php8.4-zip php8.4-curl`).

Per controllare quali mancano, dal terminale:

```bash
php -r 'foreach (explode(" ", "ctype curl dom fileinfo filter gd iconv json libxml mbstring openssl pcre pdo_mysql phar session simplexml tokenizer xml xmlreader xmlwriter zip zlib") as $e) { if (!extension_loaded($e)) echo "mancante: $e\n"; }'
```

Se non stampa nulla, le estensioni ci sono tutte.

## Limiti di caricamento

I documenti possono pesare fino a 20 MB. In `php.ini` (per PHP-FPM) imposta almeno:

```ini
upload_max_filesize = 20M
post_max_size = 25M
```

Con valori più bassi i caricamenti grandi falliscono senza un messaggio chiaro. Vedi anche [Immagini e file](../02-configurazione-iniziale/02-immagini-e-file.md).

## Come controllare le versioni

```bash
php -v          # deve indicare 8.4
composer --version
node -v         # 24 o superiore
npm -v
mysql --version # oppure mariadb --version
```

### Più versioni di PHP sullo stesso server

Se sul server ci sono più versioni di PHP, `php` e `composer` possono usarne una diversa da quella mostrata da `php -v`:
`composer diagnose` indica nella riga «PHP binary path» quale usa davvero. In quel caso lancia sempre i comandi con
l'interprete esplicito, per esempio `php8.4 artisan ...` e `php8.4 /usr/local/bin/composer ...`.

### Composer

I pacchetti `composer` delle distribuzioni sono spesso vecchi e con PHP 8.4 stampano molti avvisi. Installa la versione
ufficiale (istruzioni aggiornate su [getcomposer.org/download](https://getcomposer.org/download/)):

```bash
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php -r "if (hash_file('sha384', 'composer-setup.php') === trim(file_get_contents('https://composer.github.io/installer.sig'))) { echo 'Installer verificato'; } else { echo 'Installer corrotto'; unlink('composer-setup.php'); exit(1); }"
sudo php composer-setup.php --install-dir=/usr/local/bin --filename=composer
rm composer-setup.php
```

Poi `composer --version` deve mostrare la 2.10 o successiva.

### Node.js

Node.js serve solo per compilare gli stili e gli script del sito (`npm run build`), a ogni installazione e a ogni aggiornamento. I pacchetti
`nodejs` delle distribuzioni sono spesso troppo vecchi: installa la versione 24 (con npm 11) da una di queste vie. Le istruzioni sempre
aggiornate, per ogni sistema, sono su [nodejs.org/en/download](https://nodejs.org/en/download).

- **Su Debian e Ubuntu, per tutto il server** (richiede `sudo`), con il repository ufficiale NodeSource
  ([istruzioni](https://github.com/nodesource/distributions)):

  ```bash
  curl -fsSL https://deb.nodesource.com/setup_24.x | sudo -E bash -
  sudo apt-get install -y nodejs
  ```

- **Senza permessi di amministratore** (per esempio su un server con pannello, dove si lavora con l'utente del sito): `nvm`, che installa
  Node nella cartella personale dell'utente ([istruzioni di nvm](https://github.com/nvm-sh/nvm)). Dopo aver installato `nvm` e riaperto
  la shell: `nvm install 24`.

Poi `node -v` deve mostrare la versione 24 (o successiva) e `npm -v` la 11.

