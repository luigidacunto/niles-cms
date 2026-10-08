<?php

/*
|--------------------------------------------------------------------------
| Legacy URL redirects
|--------------------------------------------------------------------------
|
| Meccanismo generico (vale per ogni installazione NILES), dati specifici del comitato/dominio
| in un file non versionato alla radice del progetto: `legacy-redirects.php` (copia di
| `legacy-redirects.example.php`). Se quel file non esiste, il meccanismo resta disattivato.
|
|
*/

$file = base_path('legacy-redirects.php');

return is_file($file) ? require $file : [
    'exact' => [],
    'category_map' => [],
    'post_pattern' => false,
];
