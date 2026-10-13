# Aggiornamento e backup

NILES si aggiorna scaricando il codice nuovo dal repository e rilanciando alcuni passi di costruzione. Il repository include due script per
farlo in sicurezza: [`scripts/backup.sh`](../../scripts/backup.sh) per la copia di sicurezza e [`scripts/deploy.sh`](../../scripts/deploy.sh)
per l'aggiornamento. Vanno lanciati dall'utente con cui gira il sito, come spiegato in
[Installazione su un server](02-installazione-su-server.md#con-quale-utente-lanciare-i-comandi).

## Quando aggiornare

Le versioni pubblicate, con le note di ciascuna, sono nella pagina **Releases** del repository. Il numero di versione ha tre parti
(`1.2.3`): le versioni che cambiano solo l'ultima parte correggono errori o rifiniscono, quelle che cambiano la seconda aggiungono funzioni; un
cambio della prima parte può richiedere interventi manuali, segnalati nelle note.

Nel pannello, la dashboard mostra la **versione installata** e, se il controllo è attivo (`NILES_UPDATE_CHECK`, vedi
[Configurazione del file .env](03-configurazione-env.md)), segnala quando ne esiste una più recente.

## 1. Fare il backup

Prima di ogni aggiornamento:

```bash
cd /var/www/niles
bash scripts/backup.sh
```

Lo script crea nella cartella `~/niles-backups/<data_ora>/` due file con la versione di NILES nel nome:

- `db_vX.Y.Z.sql.gz`: la copia completa del database;
- `files_vX.Y.Z.tar.gz`: i file del progetto (compresi il `.env` e i file caricati dal pannello), senza `vendor`, `node_modules` e `.git`.

Con `bash scripts/backup.sh --only-db` si salva solo il database (più veloce). Lo script conserva gli **ultimi 10 backup** e cancella i più
vecchi.

> Il backup contiene il `.env`, quindi anche le password: tienilo fuori dalla cartella pubblica del sito e, per sicurezza, copia ogni tanto i
> backup anche su un altro computer o servizio, perché sullo stesso server non proteggono da un guasto del server.

**Verifica:** al termine lo script mostra le dimensioni dei due file e il percorso della cartella.

## 2. Aggiornare

```bash
bash scripts/deploy.sh
```

Per un sito in produzione, sul ramo `main`, lo script esegue in ordine:

1. `git pull`: scarica il codice nuovo;
2. `composer install --no-dev --optimize-autoloader`: aggiorna le librerie;
3. `npm ci` e `npm run build`: ricostruisce gli stili e gli script;
4. `php artisan migrate --force`: aggiorna il database alla nuova struttura;
5. svuota e ricrea le cache (`config:cache`, `route:cache`, `view:cache`);
6. controlla il collegamento `public/storage`.

Se l'aggiornamento non cambia librerie né struttura del database (le note della versione lo dicono), `bash scripts/deploy.sh --quick`
esegue solo il `git pull` e il rinnovo delle cache, in pochi secondi.

**Verifica:** lo script termina con «Deploy completato.»; aprendo il sito e la dashboard del pannello, la **versione corrente** è quella
nuova.

Note utili:

- Lo script cambia comportamento in base al ramo: sul ramo `dev` installa anche le librerie di sviluppo. **In produzione resta sempre sul
  ramo `main`.** Il parametro `--dev` forza l'installazione delle librerie di sviluppo anche su un ramo diverso da `dev`: **non va mai
  usato in produzione**, dove le librerie di sviluppo non servono e ampliano inutilmente il codice presente sul server.
- Con più versioni di PHP sul server, lo script usa `php8.4` per Composer se esiste; per i comandi `artisan` vale il `php` del terminale
  (vedi [Requisiti](01-requisiti.md#più-versioni-di-php-sullo-stesso-server)).
- Dopo una modifica al file `.env` basta `php artisan config:cache`, senza rifare l'aggiornamento.
- Se durante l'aggiornamento il sito non deve essere raggiungibile, `php artisan down` lo mette in manutenzione e `php artisan up` lo riapre.

## Ripristinare un backup

Se qualcosa va storto si torna alla copia di sicurezza. Il percorso esatto dei file compare alla fine dell'esecuzione di `backup.sh`.

**Database**:

```bash
gunzip -c ~/niles-backups/<data_ora>/db_vX.Y.Z.sql.gz | mysql -h 127.0.0.1 -u niles -p niles
```

(con host, utente e nome del database del proprio `.env`).

**File**, solo se serve ripristinare anche i file caricati o il `.env`: l'archivio contiene i percorsi completi, quindi si estrae dalla
radice del filesystem:

```bash
sudo tar -xzf ~/niles-backups/<data_ora>/files_vX.Y.Z.tar.gz -C /
```

Se si è tornati a una versione precedente del database, va riportato anche il codice alla stessa versione (con `git checkout vX.Y.Z`),
poi si rifanno `composer install --no-dev --optimize-autoloader`, `npm ci`, `npm run build` e `php artisan optimize:clear` seguito dalle cache. Le
migrazioni di una versione nuova non si annullano da sole: per questo il backup precedente all'aggiornamento è l'unica via di ritorno.
