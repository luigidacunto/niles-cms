<?php

// Tipi di email di sistema modificabili da Pannello → Template email (App\Models\EmailTemplate). Per aggiungerne
// uno: una voce qui (+ `predefinito` = testo di partenza, usato dal seeder di prima installazione e dal pulsante «Ripristina», e il codice che lo invia). `segnaposto` =
// valori specifici di quella email; in tutte valgono anche i dati del comitato (PolicyPlaceholders).
return [
    'iscrizione-corso' => [
        'label' => 'Iscrizione a un corso',
        'descrizione' => 'Inviata a ogni persona iscritta a un corso (dal modulo pubblico o inserita a mano). Allega il promemoria per il calendario e invita ad aprire la pagina personale per vedere i dati e scegliere i consensi.',
        'segnaposto' => [
            '{nome}' => 'Nome di chi si è iscritto',
            '{cognome}' => 'Cognome di chi si è iscritto',
            '{corso}' => 'Nome del corso',
            '{protocollo}' => 'Protocollo del corso (es. BLSD-2026-001)',
            '{periodo}' => 'Data e orario del corso',
            '{luogo}' => 'Sede del corso',
            '{costo}' => 'Quota di partecipazione (o "Gratuito")',
            '{dati_pagamento}' => 'Coordinate del bonifico (importo, IBAN, causale): compaiono solo se l\'iscritto ha scelto il bonifico, il corso ha una quota e l\'IBAN è impostato in Dati del comitato; altrimenti il segnaposto resta vuoto',
            '{link_preferenze}' => 'Indirizzo della pagina personale (usalo dentro un link: <a href="{link_preferenze}">…</a>)',
        ],
        'predefinito' => [
            'oggetto' => 'Iscrizione registrata: {corso}',
            'corpo' => '<p>Ciao {nome},</p>'
                .'<p>abbiamo registrato la tua iscrizione al corso <strong>{corso}</strong> (protocollo {protocollo}).</p>'
                .'<table cellpadding="4" style="border-collapse:collapse">'
                .'<tr><td><strong>Quando</strong></td><td>{periodo}</td></tr>'
                .'<tr><td><strong>Dove</strong></td><td>{luogo}</td></tr>'
                .'<tr><td><strong>Quota</strong></td><td>{costo}</td></tr>'
                .'</table>'
                .'{dati_pagamento}'
                .'<p>In allegato trovi il promemoria da aggiungere al tuo calendario.</p>'
                .'<p><strong>Conferma la tua presenza e scegli le comunicazioni</strong> che vuoi ricevere da noi '
                .'(promemoria sulla scadenza dell\'attestato e newsletter, entrambi facoltativi e revocabili quando vuoi) '
                .'aprendo la tua pagina personale:</p>'
                .'<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:8px 0 18px"><tr>'
                .'<td align="center" bgcolor="#cc0000" style="background-color:#cc0000;border-radius:4px">'
                .'<a href="{link_preferenze}" style="display:inline-block;padding:14px 32px;font-family:Arial,Helvetica,sans-serif;'
                .'font-size:17px;font-weight:bold;color:#ffffff;text-decoration:none">Conferma la tua presenza</a>'
                .'</td></tr></table>'
                .'<p>Se non puoi più partecipare, avvisaci rispondendo a questa email'
                .' oppure contattaci al {telefono_comitato}.</p>'
                .'<p>A presto,<br>{nome_comitato}</p>',
        ],
    ],
    'riepilogo-referente' => [
        'label' => 'Riepilogo per chi ha iscritto altre persone',
        'descrizione' => 'Inviata a chi compila il modulo iscrivendo anche altre persone (non a chi si iscrive da solo): elenca chi ha iscritto, i dati inseriti e, con la fatturazione unica, i dati di fatturazione e le coordinate per il bonifico (importo totale e causale). Con i pagamenti separati ognuno riceve le indicazioni nella propria email di iscrizione.',
        'segnaposto' => [
            '{nome}' => 'Nome di chi ha compilato il modulo',
            '{corso}' => 'Nome del corso',
            '{protocollo}' => 'Protocollo del corso (es. BLSD-2026-001)',
            '{periodo}' => 'Data e orario del corso',
            '{luogo}' => 'Sede del corso',
            '{costo}' => 'Quota per persona (o "Gratuito")',
            '{numero_iscritti}' => 'Quante persone ha iscritto',
            '{elenco_iscritti}' => 'Tabella con le persone iscritte (nome, codice fiscale, email, telefono ed eventuale metodo di pagamento)',
            '{dati_fatturazione}' => 'Dati di fatturazione inseriti (pagamento unico) oppure l\'avviso che ognuno paga per sé',
            '{dati_pagamento}' => 'Coordinate del bonifico con importo totale e causale: solo con fatturazione unica, bonifico, corso a pagamento e IBAN impostato; altrimenti vuoto',
        ],
        'predefinito' => [
            'oggetto' => 'Riepilogo iscrizione: {corso} ({numero_iscritti} persone)',
            'corpo' => '<p>Ciao {nome},</p>'
                .'<p>hai iscritto <strong>{numero_iscritti} persone</strong> al corso <strong>{corso}</strong> (protocollo {protocollo}). '
                .'Grazie! Qui sotto trovi il riepilogo di quello che hai inserito.</p>'
                .'<table cellpadding="4" style="border-collapse:collapse">'
                .'<tr><td><strong>Quando</strong></td><td>{periodo}</td></tr>'
                .'<tr><td><strong>Dove</strong></td><td>{luogo}</td></tr>'
                .'<tr><td><strong>Quota per persona</strong></td><td>{costo}</td></tr>'
                .'</table>'
                .'<p style="margin:16px 0 4px"><strong>Persone iscritte</strong></p>'
                .'{elenco_iscritti}'
                .'{dati_fatturazione}'
                .'{dati_pagamento}'
                .'<p>Ogni persona iscritta riceve una email con il promemoria del corso. Se qualcosa non è corretto, '
                .'rispondi a questa email oppure contattaci al {telefono_comitato}.</p>'
                .'<p>A presto,<br>{nome_comitato}</p>',
        ],
    ],
];
