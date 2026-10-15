<?php
declare(strict_types=1);

/**
 * NILES - installer per hosting condiviso (solo FTP e un database, senza SSH né Composer).
 *
 * Si carica nella radice del sito (la cartella servita dal dominio) e si apre dal browser. Scarica l'ultima release
 * (niles.zip, con le dipendenze già incluse), la estrae sul posto e guida l'installazione: dati del database,
 * del comitato e del primo amministratore, poi crea `.env`, esegue migrazioni e seeder e si cancella.
 *
 * Se accanto a questo file esiste un `niles.zip` (e, facoltativo, `niles.zip.sha256`) viene usato al posto del download.
 *
 * Il file non fa parte dell'applicazione: nelle installazioni con Composer non viene mai raggiunto dal web.
 * Le funzioni `niles*()` non dipendono dal framework (che a inizio installazione non c'è ancora).
 */

const NILES_ZIP_URL = 'https://github.com/luigidacunto/niles-cms/releases/latest/download/niles.zip';
const NILES_SHA_URL = 'https://github.com/luigidacunto/niles-cms/releases/latest/download/niles.zip.sha256';

// ---------------------------------------------------------------------------------------------------------------
// Funzioni pure (coperte da tests/Unit/NilesInstallerTest.php)
// ---------------------------------------------------------------------------------------------------------------

function h(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

/** Valore per `.env`: tra virgolette doppie, con escape, se contiene caratteri speciali (# $ " spazi ...). */
function nilesEnvValue(string $v): string
{
    if ($v === '') {
        return '';
    }
    if (preg_match('/^[A-Za-z0-9_.:\/@+=-]+$/', $v)) {
        return $v;
    }

    return '"'.str_replace(['\\', '"', '$'], ['\\\\', '\\"', '\\$'], $v).'"';
}

/** Contenuto di `.env`. `$c`: public_name, url, key, db[host,port,name,user,pass], smtp[host,port,enc,user,pass,from,from_name]|null. */
function nilesBuildEnv(array $c): string
{
    $l = [
        'APP_NAME' => 'NILES',
        'APP_PUBLIC_NAME' => $c['public_name'],
        'APP_ENV' => 'production',
        'APP_KEY' => $c['key'],
        'APP_DEBUG' => 'false',
        'APP_URL' => $c['url'],
        'APP_LOCALE' => 'it',
        'APP_FALLBACK_LOCALE' => 'en',
        'LOG_CHANNEL' => 'stack',
        'LOG_STACK' => 'single',
        'LOG_LEVEL' => 'warning',
        'DB_CONNECTION' => 'mysql',
        'DB_HOST' => $c['db']['host'],
        'DB_PORT' => $c['db']['port'],
        'DB_DATABASE' => $c['db']['name'],
        'DB_USERNAME' => $c['db']['user'],
        'DB_PASSWORD' => $c['db']['pass'],
        'SESSION_DRIVER' => 'database',
        'CACHE_STORE' => 'database',
        // Nessun worker su un hosting condiviso: la coda (non usata dall'applicazione) resta sincrona.
        'QUEUE_CONNECTION' => 'sync',
    ];
    if (! empty($c['smtp'])) {
        $s = $c['smtp'];
        $l['MAIL_MAILER'] = 'smtp';
        $l['MAIL_HOST'] = $s['host'];
        $l['MAIL_PORT'] = $s['port'];
        if ($s['enc'] === 'ssl') {
            // TLS implicito (porta 465): schema smtps. Con STARTTLS o senza cifratura resta lo schema predefinito.
            $l['MAIL_SCHEME'] = 'smtps';
        }
        $l['MAIL_USERNAME'] = $s['user'];
        $l['MAIL_PASSWORD'] = $s['pass'];
        $l['MAIL_FROM_ADDRESS'] = $s['from'];
        $l['MAIL_FROM_NAME'] = $s['from_name'];
    } else {
        $l['MAIL_MAILER'] = 'log';
    }
    $out = '';
    foreach ($l as $k => $v) {
        $out .= $k.'='.nilesEnvValue((string) $v)."\n";
    }

    return $out;
}

/** Un nome di voce dello zip è accettabile solo se relativo e senza risalite (zip-slip). */
function nilesZipEntrySafe(string $name): bool
{
    if ($name === '' || str_contains($name, "\0") || str_contains($name, '\\') || $name[0] === '/' || preg_match('/^[A-Za-z]:/', $name)) {
        return false;
    }

    return ! in_array('..', explode('/', $name), true);
}

/** Primo hash SHA-256 (64 esadecimali) di un file `.sha256` nel formato di sha256sum. */
function nilesParseSha(string $text): ?string
{
    return preg_match('/^[a-f0-9]{64}/i', trim($text), $m) ? strtolower($m[0]) : null;
}

function nilesAppKey(): string
{
    return 'base64:'.base64_encode(random_bytes(32));
}

/** Requisiti: righe [etichetta, esito ok|warn|ko, dettaglio]. Bloccanti: i `ko`. */
function nilesRequirements(string $dir): array
{
    $r = [[ 'PHP >= 8.4', version_compare(PHP_VERSION, '8.4.0', '>=') ? 'ok' : 'ko', PHP_VERSION ]];
    $mancanti = array_filter(
        ['ctype', 'dom', 'fileinfo', 'filter', 'hash', 'iconv', 'json', 'libxml', 'mbstring', 'openssl', 'pcre', 'pdo', 'pdo_mysql', 'session', 'tokenizer', 'xml', 'xmlwriter', 'zip'],
        fn ($e) => ! extension_loaded($e)
    );
    $r[] = ['Estensioni PHP obbligatorie', $mancanti ? 'ko' : 'ok', $mancanti ? 'mancano: '.implode(', ', $mancanti) : 'presenti'];
    $r[] = ['Estensione immagini (gd o imagick)', (extension_loaded('gd') || extension_loaded('imagick')) ? 'ok' : 'warn', 'serve per le immagini caricate dal pannello'];
    $r[] = ['Estensione intl', extension_loaded('intl') ? 'ok' : 'warn', 'consigliata'];
    $r[] = ['Download da internet', (extension_loaded('curl') || ini_get('allow_url_fopen')) ? 'ok' : 'warn', 'senza, si può caricare a mano niles.zip accanto a questo file'];
    $r[] = ['Cartella scrivibile', is_writable($dir) ? 'ok' : 'ko', $dir];
    $r[] = ['Collegamenti simbolici (symlink)', function_exists('symlink') ? 'ok' : 'warn', 'per gli upload pubblici (public/storage)'];
    $sottocartella = dirname($_SERVER['SCRIPT_NAME'] ?? '/') !== '/' && dirname($_SERVER['SCRIPT_NAME'] ?? '/') !== '\\';
    $r[] = ['File nella radice del sito', $sottocartella ? 'ko' : 'ok', $sottocartella ? 'in una sottocartella non è supportato: usare la radice del dominio o di un sottodominio' : 'ok'];

    return $r;
}

/** Voci già presenti nella cartella, a parte l'installer e i file ammessi. */
function nilesExistingEntries(string $dir): array
{
    $ammessi = ['.', '..', 'niles-installer.php', 'niles.zip', 'niles.zip.sha256', 'niles.zip.part', 'cgi-bin', '.well-known', '.ftpquota', '.niles-probe'];

    return array_values(array_diff(scandir($dir) ?: [], $ammessi));
}

/** Verifica una connessione SMTP (collegamento, STARTTLS se richiesto, autenticazione). Ritorna null se ok, altrimenti il motivo. */
function nilesSmtpTest(string $host, int $port, string $enc, string $user, string $pass): ?string
{
    $fp = @stream_socket_client(($enc === 'ssl' ? 'ssl://' : 'tcp://').$host.':'.$port, $errno, $errstr, 15);
    if (! $fp) {
        return "collegamento a $host:$port non riuscito ($errstr)";
    }
    stream_set_timeout($fp, 15);
    $read = function () use ($fp): string {
        $out = '';
        while (($l = fgets($fp, 515)) !== false) {
            $out .= $l;
            if (strlen($l) < 4 || $l[3] === ' ') {
                break;
            }
        }

        return $out;
    };
    $send = function (string $cmd) use ($fp, $read): string {
        fwrite($fp, $cmd."\r\n");

        return $read();
    };
    $code = fn (string $r): int => (int) substr($r, 0, 3);
    $ehlo = 'EHLO '.(gethostname() ?: 'localhost');
    $errore = null;

    $r = $read();
    if ($code($r) !== 220) {
        $errore = 'risposta iniziale inattesa: '.trim($r);
    } elseif ($code($r = $send($ehlo)) !== 250) {
        $errore = 'EHLO rifiutato: '.trim($r);
    } elseif ($enc === 'tls' && ($code($r = $send('STARTTLS')) !== 220 || ! @stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT) || $code($r = $send($ehlo)) !== 250)) {
        $errore = 'STARTTLS non riuscito: '.trim($r);
    } elseif ($user !== '') {
        if ($code($r = $send('AUTH LOGIN')) !== 334 || $code($r = $send(base64_encode($user))) !== 334) {
            $errore = 'autenticazione non supportata: '.trim($r);
        } elseif ($code($r = $send(base64_encode($pass))) !== 235) {
            $errore = 'utente o password rifiutati ('.trim($r).')';
        }
    }
    @fwrite($fp, "QUIT\r\n");
    @fclose($fp);

    return $errore;
}

// ---------------------------------------------------------------------------------------------------------------
// Operazioni (rete, file system, database)
// ---------------------------------------------------------------------------------------------------------------

/** Scarica un URL in un file (curl, altrimenti allow_url_fopen). Ritorna null se ok, altrimenti il motivo. */
function nilesDownload(string $url, string $dest): ?string
{
    if (function_exists('curl_init')) {
        $fp = fopen($dest, 'wb');
        $c = curl_init($url);
        curl_setopt_array($c, [CURLOPT_FILE => $fp, CURLOPT_FOLLOWLOCATION => true, CURLOPT_TIMEOUT => 300, CURLOPT_FAILONERROR => true, CURLOPT_USERAGENT => 'niles-installer']);
        $ok = curl_exec($c);
        $err = curl_error($c);
        curl_close($c);
        fclose($fp);

        return $ok ? null : "download non riuscito ($err)";
    }
    if (ini_get('allow_url_fopen')) {
        $ctx = stream_context_create(['http' => ['timeout' => 300, 'header' => "User-Agent: niles-installer\r\n"]]);

        return @copy($url, $dest, $ctx) ? null : 'download non riuscito';
    }

    return 'né curl né allow_url_fopen disponibili: caricare a mano niles.zip accanto a questo file';
}

/** Procura niles.zip (locale o scaricato), ne verifica l'hash e lo estrae in $dir. Ritorna null se ok, altrimenti il motivo. */
function nilesFetchAndExtract(string $dir): ?string
{
    @set_time_limit(0);
    $zip = $dir.'/niles.zip';
    $sha = $dir.'/niles.zip.sha256';
    if (! is_file($zip)) {
        if ($err = nilesDownload(NILES_ZIP_URL, $zip.'.part')) {
            @unlink($zip.'.part');

            return $err;
        }
        rename($zip.'.part', $zip);
        if ($err = nilesDownload(NILES_SHA_URL, $sha)) {
            return 'impossibile scaricare il checksum: '.$err;
        }
    }
    if (is_file($sha)) {
        $atteso = nilesParseSha((string) file_get_contents($sha));
        if ($atteso === null || ! hash_equals($atteso, hash_file('sha256', $zip))) {
            return 'il checksum del pacchetto non corrisponde: file danneggiato o alterato, non viene installato';
        }
    }
    $z = new ZipArchive();
    if ($z->open($zip) !== true) {
        return 'il pacchetto non è un file zip valido';
    }
    for ($i = 0; $i < $z->numFiles; $i++) {
        if (! nilesZipEntrySafe((string) $z->getNameIndex($i))) {
            $z->close();

            return 'il pacchetto contiene percorsi non ammessi: non viene installato';
        }
    }
    $ok = $z->extractTo($dir);
    $z->close();
    if (! $ok) {
        return 'estrazione non riuscita (spazio o permessi insufficienti?)';
    }
    @unlink($zip);
    @unlink($sha);

    return null;
}

/** Connessione al database e controllo che sia utilizzabile. Ritorna [PDO|null, errore|null]. */
function nilesCheckDb(array $db): array
{
    try {
        $pdo = new PDO(sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $db['host'], (int) $db['port'], $db['name']), $db['user'], $db['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $tabelle = $pdo->query('SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE()')->fetchAll(PDO::FETCH_COLUMN);
        $tabelle = array_map('strtolower', $tabelle);
        if ($tabelle) {
            // Ammesso solo riprendere un'installazione interrotta (migrazioni parziali, nessun amministratore).
            $ripresa = in_array('migrations', $tabelle, true) && (! in_array('admins', $tabelle, true) || (int) $pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn() === 0);
            if (! $ripresa) {
                return [null, 'il database non è vuoto: usare un database nuovo, per non toccare dati esistenti'];
            }
        }

        return [$pdo, null];
    } catch (Throwable $e) {
        return [null, 'connessione al database non riuscita ('.$e->getMessage().')'];
    }
}

/** Avvia Laravel dentro questo processo ed esegue migrazioni, seeder e collegamento di storage. Ritorna [log, versione]. */
function nilesRunLaravel(string $dir, string $adminEmail, string $adminPassword): array
{
    // Variabili d'ambiente di processo: Dotenv non le sovrascrive e la password non finisce in `.env`.
    foreach (['INITIAL_ADMIN_EMAIL' => $adminEmail, 'INITIAL_ADMIN_PASSWORD' => $adminPassword] as $k => $v) {
        putenv("$k=$v");
        $_ENV[$k] = $_SERVER[$k] = $v;
    }
    require $dir.'/vendor/autoload.php';
    $app = require $dir.'/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    $log = '';
    foreach ([['migrate', ['--force' => true]], ['db:seed', ['--force' => true]], ['storage:link', []]] as [$cmd, $args]) {
        $code = $kernel->call($cmd, $args);
        $log .= "> $cmd\n".$kernel->output()."\n";
        if ($code !== 0 && $cmd !== 'storage:link') {
            throw new RuntimeException("Il comando «$cmd» è fallito.\n\n$log");
        }
    }

    return [$log, (string) $app->make('config')->get('app.version')];
}

// ---------------------------------------------------------------------------------------------------------------
// Pagine
// ---------------------------------------------------------------------------------------------------------------

function nilesPage(string $titolo, string $corpo, int $passo = 0): never
{
    $passi = ['Requisiti', 'Dati', 'Fine'];
    $nav = '';
    foreach ($passi as $i => $p) {
        $nav .= '<span class="'.($i + 1 === $passo ? 'on' : '').'">'.($i + 1).'. '.h($p).'</span>';
    }
    echo '<!doctype html><html lang="it"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex">'
        .'<title>Installazione di NILES</title><style>'
        .'body{font:15px/1.5 system-ui,sans-serif;background:#f4f4f4;color:#222;margin:0}main{max-width:720px;margin:24px auto;background:#fff;padding:24px 28px;border-radius:8px;box-shadow:0 1px 4px #0002}'
        .'h1{font-size:22px;margin:0 0 4px}h2{font-size:17px;margin:22px 0 6px}nav{margin:8px 0 18px}nav span{display:inline-block;margin-right:14px;color:#888}nav .on{color:#c00;font-weight:700}'
        .'table{border-collapse:collapse;width:100%}td{border-bottom:1px solid #eee;padding:5px 6px;vertical-align:top}td:nth-child(2){font-weight:700;width:90px}'
        .'.ok{color:#0a7d2c}.warn{color:#b26a00}.ko{color:#c00}label{display:block;margin:10px 0 2px;font-weight:600}input[type=text],input[type=email],input[type=password],input[type=number],select{width:100%;padding:7px;box-sizing:border-box;border:1px solid #bbb;border-radius:4px}'
        .'.row{display:flex;gap:12px}.row>div{flex:1}button{background:#c00;color:#fff;border:0;border-radius:4px;padding:10px 20px;font-size:15px;cursor:pointer;margin-top:16px}button[disabled]{background:#999}'
        .'.box{padding:10px 14px;border-radius:4px;margin:12px 0}.err{background:#fdecec;border:1px solid #c00}.avv{background:#fff6e5;border:1px solid #b26a00}.good{background:#eaf7ee;border:1px solid #0a7d2c}'
        .'small{color:#666}pre{background:#f4f4f4;padding:8px;overflow:auto;font-size:12px}details{margin:14px 0}'
        .'</style></head><body><main><h1>Installazione di NILES</h1><nav>'.$nav.'</nav>'.($titolo !== '' ? '<h2>'.h($titolo).'</h2>' : '').$corpo.'</main></body></html>';
    exit;
}

/** Attributo onsubmit: disattiva il pulsante e mostra che l'operazione è in corso (evita il doppio invio e la sensazione di blocco). */
function nilesBusy(string $messaggio): string
{
    return h("var b=this.querySelector('button');b.disabled=true;b.textContent=".json_encode($messaggio, JSON_UNESCAPED_UNICODE).';');
}

function nilesBox(string $classe, string $html): string
{
    return '<div class="box '.$classe.'">'.$html.'</div>';
}

function nilesScreenCheck(string $dir, ?string $errore = null): never
{
    $righe = nilesRequirements($dir);
    $bloccato = (bool) array_filter($righe, fn ($r) => $r[1] === 'ko');
    $t = '<table>';
    foreach ($righe as [$l, $s, $d]) {
        $t .= '<tr><td>'.h($l).'</td><td class="'.$s.'">'.['ok' => 'OK', 'warn' => 'ATTENZIONE', 'ko' => 'ERRORE'][$s].'</td><td>'.h($d).'</td></tr>';
    }
    $t .= '</table>';
    $corpo = ($errore ? nilesBox('err', h($errore)) : '').$t;
    $esistenti = nilesExistingEntries($dir);
    if ($esistenti) {
        $corpo .= nilesBox('avv', 'La cartella contiene già: <b>'.h(implode(', ', array_slice($esistenti, 0, 12))).(count($esistenti) > 12 ? ', …' : '').'</b>. I file con lo stesso nome del pacchetto verranno sovrascritti.'
            .'<label><input type="checkbox" name="conferma" value="1" form="f"> Ho controllato: procedi</label>');
    }
    $locale = is_file($dir.'/niles.zip') ? 'Verrà usato il file <code>niles.zip</code> già presente.' : 'Il pacchetto verrà scaricato dall\'ultima release su GitHub.';
    $corpo .= '<p><small>'.$locale.'</small></p><form id="f" method="post" onsubmit="'.nilesBusy('Estrazione in corso: richiede 1-2 minuti, non chiudere né ricaricare la pagina…').'"><input type="hidden" name="azione" value="scarica">'
        .'<button'.($bloccato ? ' disabled' : '').'>Scarica e installa</button></form>';
    nilesPage('Controllo requisiti', $corpo, 1);
}

function nilesScreenForm(string $dir, array $v = [], ?string $errore = null, bool $smtpFallito = false): never
{
    $g = fn (string $k, string $def = '') => h($v[$k] ?? $def);
    // File sentinella: la pagina verifica dal browser che i file nascosti e quelli della radice non siano serviti.
    $token = bin2hex(random_bytes(8));
    @file_put_contents($dir.'/.niles-probe', $token);
    $url = ((! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://').($_SERVER['HTTP_HOST'] ?? 'localhost');

    $c = ($errore ? nilesBox('err', h($errore)) : '').'<div id="sicurezza"></div>'
        .'<form method="post" id="f" onsubmit="'.nilesBusy('Installazione in corso: può richiedere un minuto, non chiudere né ricaricare la pagina…').'"><input type="hidden" name="azione" value="installa">'
        .'<h2>Database MySQL</h2><small>Creato dal pannello dell\'hosting. Deve essere vuoto.</small>'
        .'<div class="row"><div><label>Host</label><input type="text" name="db_host" value="'.$g('db_host', 'localhost').'" required></div><div style="max-width:110px"><label>Porta</label><input type="number" name="db_port" value="'.$g('db_port', '3306').'" required></div></div>'
        .'<label>Nome del database</label><input type="text" name="db_name" value="'.$g('db_name').'" required>'
        .'<div class="row"><div><label>Utente</label><input type="text" name="db_user" value="'.$g('db_user').'" required></div><div><label>Password</label><input type="password" name="db_pass" value=""></div></div>'
        .'<h2>Sito</h2>'
        .'<label>Nome del comitato (titolo delle pagine pubbliche)</label><input type="text" name="public_name" value="'.$g('public_name').'" placeholder="Croce Rossa Italiana – Comitato di …" required>'
        .'<label>Indirizzo pubblico del sito</label><input type="text" name="app_url" value="'.$g('app_url', $url).'" required>'
        .'<h2>Primo amministratore</h2>'
        .'<label>Email</label><input type="email" name="admin_email" value="'.$g('admin_email').'" required>'
        .'<div class="row"><div><label>Password (almeno 10 caratteri)</label><input type="password" name="admin_pass" minlength="10" required></div><div><label>Ripeti la password</label><input type="password" name="admin_pass2" minlength="10" required></div></div>'
        .'<details'.(! empty($v['smtp_host']) ? ' open' : '').'><summary><b>Posta in uscita (facoltativo)</b></summary>'
        .'<small>Serve per il codice di accesso via email e per le comunicazioni. Si può configurare anche più tardi: nel frattempo l\'accesso con password resta attivo.</small>'
        .'<div class="row"><div><label>Server SMTP</label><input type="text" name="smtp_host" value="'.$g('smtp_host').'" placeholder="smtps.aruba.it"></div><div style="max-width:110px"><label>Porta</label><input type="number" name="smtp_port" value="'.$g('smtp_port', '465').'"></div>'
        .'<div style="max-width:170px"><label>Cifratura</label><select name="smtp_enc">'
        .implode('', array_map(fn ($o, $t) => '<option value="'.$o.'"'.(($v['smtp_enc'] ?? 'ssl') === $o ? ' selected' : '').'>'.$t.'</option>', ['ssl', 'tls', 'none'], ['SSL/TLS (465)', 'STARTTLS (587)', 'nessuna']))
        .'</select></div></div>'
        .'<div class="row"><div><label>Utente</label><input type="text" name="smtp_user" value="'.$g('smtp_user').'"></div><div><label>Password</label><input type="password" name="smtp_pass"></div></div>'
        .'<div class="row"><div><label>Indirizzo mittente</label><input type="email" name="smtp_from" value="'.$g('smtp_from').'" placeholder="la stessa casella dell\'utente"></div><div><label>Nome mittente</label><input type="text" name="smtp_from_name" value="'.$g('smtp_from_name').'"></div></div>'
        .($smtpFallito ? '<label><input type="checkbox" name="smtp_skip" value="1"> Installa comunque senza configurare la posta</label>' : '')
        .'</details>'
        .'<button id="vai">Installa NILES</button> <small>Può richiedere un minuto: non chiudere la pagina.</small></form>'
        .'<script>(async()=>{const T='.json_encode($token).',bad=[];'
        .'const t=async(u,f)=>{try{const r=await fetch(u,{cache:"no-store"});const x=await r.text();if(r.status===200&&f(x))bad.push(u)}catch(e){}};'
        .'await t("/.niles-probe",x=>x.includes(T));await t("/composer.json",x=>x.includes("laravel/framework"));await t("/vendor/autoload.php",x=>x.length>0);'
        .'if(bad.length){document.getElementById("sicurezza").innerHTML=\'<div class="box err"><b>Installazione bloccata: file riservati raggiungibili dal web</b> (\'+bad.join(", ")+\'). Il file .htaccess della radice non è attivo su questo hosting: non installare, i dati di accesso al database sarebbero esposti.</div>\';document.getElementById("vai").disabled=true}'
        .'})()</script>';
    nilesPage('Dati per l\'installazione', $c, 2);
}

function nilesScreenDone(string $url, string $email, string $log, bool $installerCancellato): never
{
    $c = nilesBox('good', '<b>NILES è installato.</b>').'<p>Accedi al pannello con l\'email <b>'.h($email).'</b> e la password scelta:</p>'
        .'<p><a href="'.h(rtrim($url, '/')).'/admin/login/password"><b>'.h(rtrim($url, '/')).'/admin/login/password</b></a></p>'
        .'<p>Il codice di accesso via email funzionerà dopo aver configurato la posta in uscita; finché non è configurata, usa l\'accesso con password.</p>'
        .($installerCancellato ? '' : nilesBox('err', '<b>Cancella a mano il file <code>niles-installer.php</code> dalla radice del sito</b> (non è stato possibile farlo in automatico).'))
        .'<details><summary>Dettagli tecnici</summary><pre>'.h($log).'</pre></details>';
    nilesPage('Fatto', $c, 3);
}

// ---------------------------------------------------------------------------------------------------------------
// Flusso
// ---------------------------------------------------------------------------------------------------------------

function nilesMain(): void
{
    @ini_set('display_errors', '0');
    $dir = __DIR__;

    if (is_file($dir.'/storage/installed')) {
        @unlink(__FILE__);
        nilesPage('', nilesBox('avv', 'NILES risulta già installato: questo file non serve più e va cancellato.'));
    }

    $haApp = is_file($dir.'/vendor/autoload.php') && is_file($dir.'/artisan');
    $post = $_SERVER['REQUEST_METHOD'] === 'POST';

    if (! $haApp) {
        if ($post && ($_POST['azione'] ?? '') === 'scarica') {
            if (nilesExistingEntries($dir) && empty($_POST['conferma'])) {
                nilesScreenCheck($dir, 'Conferma di aver controllato i file già presenti nella cartella.');
            }
            if ($err = nilesFetchAndExtract($dir)) {
                nilesScreenCheck($dir, 'Errore: '.$err);
            }
            header('Location: '.$_SERVER['SCRIPT_NAME'], true, 303);
            exit;
        }
        nilesScreenCheck($dir);
    }

    if (! $post || ($_POST['azione'] ?? '') !== 'installa') {
        nilesScreenForm($dir);
    }

    // Installazione
    $v = array_map(fn ($x) => is_string($x) ? trim(str_replace(["\r", "\n"], '', $x)) : '', $_POST);
    $pass = (string) ($_POST['db_pass'] ?? '');
    $apass = (string) ($_POST['admin_pass'] ?? '');
    $form = fn (string $m, bool $smtp = false) => nilesScreenForm($dir, $v, $m, $smtp);

    foreach (['db_host', 'db_port', 'db_name', 'db_user', 'public_name', 'app_url', 'admin_email'] as $k) {
        if (($v[$k] ?? '') === '') {
            $form('Compila tutti i campi obbligatori.');
        }
    }
    if (! filter_var($v['admin_email'], FILTER_VALIDATE_EMAIL)) {
        $form('L\'email dell\'amministratore non è valida.');
    }
    if (strlen($apass) < 10 || $apass !== (string) ($_POST['admin_pass2'] ?? '')) {
        $form('La password dell\'amministratore deve avere almeno 10 caratteri e coincidere nei due campi.');
    }
    if (! preg_match('#^https?://[^\s/]+#i', $v['app_url'])) {
        $form('L\'indirizzo del sito deve iniziare con https:// (o http://).');
    }

    $db = ['host' => $v['db_host'], 'port' => $v['db_port'], 'name' => $v['db_name'], 'user' => $v['db_user'], 'pass' => $pass];
    [, $err] = nilesCheckDb($db);
    if ($err) {
        $form('Database: '.$err);
    }

    $smtp = null;
    if ($v['smtp_host'] !== '' && empty($v['smtp_skip'])) {
        $smtp = ['host' => $v['smtp_host'], 'port' => $v['smtp_port'] ?: '465', 'enc' => in_array($v['smtp_enc'], ['ssl', 'tls', 'none'], true) ? $v['smtp_enc'] : 'ssl',
            'user' => $v['smtp_user'], 'pass' => (string) ($_POST['smtp_pass'] ?? ''), 'from' => $v['smtp_from'] ?: $v['smtp_user'], 'from_name' => $v['smtp_from_name'] ?: $v['public_name']];
        if (! filter_var($smtp['from'], FILTER_VALIDATE_EMAIL)) {
            $form('L\'indirizzo mittente della posta non è valido.');
        }
        if ($err = nilesSmtpTest($smtp['host'], (int) $smtp['port'], $smtp['enc'], $smtp['user'], $smtp['pass'])) {
            $form('Posta: '.$err, true);
        }
    }

    try {
        @set_time_limit(0);
        $env = nilesBuildEnv(['public_name' => $v['public_name'], 'url' => rtrim($v['app_url'], '/'), 'key' => nilesAppKey(), 'db' => $db, 'smtp' => $smtp]);
        if (file_put_contents($dir.'/.env', $env) === false) {
            throw new RuntimeException('Impossibile scrivere il file .env nella radice del sito.');
        }
        @chmod($dir.'/.env', 0640);
        [$log, $versione] = nilesRunLaravel($dir, $v['admin_email'], $apass);
        file_put_contents($dir.'/storage/installed', json_encode(['versione' => $versione, 'data' => date('c')]));
    } catch (Throwable $e) {
        $form('Installazione interrotta: '.$e->getMessage().' — correggi e riprova (il database viene ripreso da dove si era fermato).');
    }

    @unlink($dir.'/.niles-probe');
    $cancellato = @unlink(__FILE__);
    nilesScreenDone($v['app_url'], $v['admin_email'], $log, $cancellato);
}

if (PHP_SAPI !== 'cli') {
    nilesMain();
}
