<!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <title>{{ $pagina['titolo'] }}</title>
    {{-- PDF di un documento della guida (dompdf): le immagini arrivano come percorsi sul disco, vedi App\Support\Guida::pagina(). --}}
    <style>
        @page { margin: 16mm 14mm; }
        body { font: 11px/1.45 DejaVu Sans, sans-serif; color: #111; }
        h1 { font-size: 20px; } h2 { font-size: 15px; margin-top: 18px; } h3 { font-size: 13px; }
        img { max-width: 100%; border: 1px solid #ccc; }
        table { width: 100%; border-collapse: collapse; } th, td { border: 1px solid #999; padding: 4px; text-align: left; }
        pre, code { font-family: DejaVu Sans Mono, monospace; font-size: 10px; } pre { background: #f4f4f4; padding: 6px; }
        blockquote { border-left: 3px solid #ccc; margin-left: 0; padding-left: 10px; color: #444; }
        a { color: #0b5394; }
    </style>
</head>
<body>
    {!! $pagina['html'] !!}
</body>
</html>
