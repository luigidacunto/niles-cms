<?php

namespace App\Http\Requests;

use App\Models\CommitteeInfo;
use App\Models\IscrizioneCorso;
use App\Models\SicurezzaForm;
use App\Rules\CampoAnagrafico;
use App\Rules\CodiceFiscale;
use App\Rules\Maggiorenne;
use App\Rules\PartitaIva;
use App\Rules\TurnstileToken;
use App\Support\Pulizia;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CorsoIscrizioneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Pulisce i dati prima della validazione (e quindi di salvarli): maiuscolo/minuscolo uniformi, spazi in eccesso tolti.
     * Vedi App\Support\Pulizia.
     */
    protected function prepareForValidation(): void
    {
        $dati = [];

        if (is_array($this->input('richiedente'))) {
            $dati['richiedente'] = Pulizia::persona($this->input('richiedente'));
        }
        if (is_array($this->input('nominativi'))) {
            $dati['nominativi'] = array_map(fn ($n) => is_array($n) ? Pulizia::persona($n) : $n, $this->input('nominativi'));
        }
        if (is_array($this->input('fatturazione'))) {
            $dati['fatturazione'] = Pulizia::fatturazione($this->input('fatturazione'));
        }

        $this->merge($dati);
    }

    public function rules(): array
    {
        $fatturazioneUnica = $this->boolean('fatturazione_unica');
        $metodiPagamento = CommitteeInfo::current()->metodi_pagamento;
        $comeIscritto = $this->fatturazioneComeIscritto();

        return [
            'richiedente.nome' => ['required', 'string', 'max:255', new CampoAnagrafico('nome')],
            'richiedente.cognome' => ['required', 'string', 'max:255', new CampoAnagrafico('nome')],
            'richiedente.email' => ['required', 'email', 'max:255'],
            'richiedente.telefono' => ['nullable', 'string', 'max:30', new CampoAnagrafico('telefono')],
            'richiedente.codice_fiscale' => ['required', new CodiceFiscale],

            'fatturazione_unica' => ['boolean'],

            // Fatturazione unica per il gruppo: un solo blocco, valorizzato qui.
            'fatturazione.tipo' => [Rule::requiredIf($fatturazioneUnica && ! $comeIscritto), 'in:privato,azienda'],
            'fatturazione.nome' => ['required_if:fatturazione.tipo,privato', 'nullable', 'string', 'max:255', new CampoAnagrafico('nome')],
            'fatturazione.cognome' => ['required_if:fatturazione.tipo,privato', 'nullable', 'string', 'max:255', new CampoAnagrafico('nome')],
            'fatturazione.ragione_sociale' => ['required_if:fatturazione.tipo,azienda', 'nullable', 'string', 'max:255'],
            'fatturazione.partita_iva' => ['required_if:fatturazione.tipo,azienda', 'nullable', new PartitaIva],
            'fatturazione.codice_fiscale' => ['nullable', new CodiceFiscale],
            'fatturazione.via' => ['nullable', 'string', 'max:255', new CampoAnagrafico('via')],
            'fatturazione.comune' => ['nullable', 'string', 'max:255', new CampoAnagrafico('nome')],
            'fatturazione.provincia' => ['nullable', 'string', new CampoAnagrafico('provincia')],
            'fatturazione.cap' => ['nullable', 'string', new CampoAnagrafico('cap')],
            'fatturazione.codice_destinatario' => ['nullable', 'regex:/^[A-Za-z0-9]{6,7}$/'],
            'fatturazione.pec' => ['nullable', 'email', 'max:255'],
            'fatturazione.metodo_pagamento' => ['nullable', Rule::in($metodiPagamento)],

            'nominativi' => ['required', 'array', 'min:1', 'max:30'],
            'nominativi.*.nome' => ['required', 'string', 'max:255', new CampoAnagrafico('nome')],
            'nominativi.*.cognome' => ['required', 'string', 'max:255', new CampoAnagrafico('nome')],
            'nominativi.*.email' => ['required', 'email', 'max:255'],
            'nominativi.*.telefono' => ['required', 'string', 'max:30', new CampoAnagrafico('telefono')],
            'nominativi.*.codice_fiscale' => ['required', new CodiceFiscale],
            'nominativi.*.via' => ['required', 'string', 'max:255', new CampoAnagrafico('via')],
            'nominativi.*.comune' => ['required', 'string', 'max:255', new CampoAnagrafico('nome')],
            'nominativi.*.provincia' => ['required', 'string', new CampoAnagrafico('provincia')],
            'nominativi.*.cap' => ['required', 'string', new CampoAnagrafico('cap')],

            // Pagamenti separati (fatturazione_unica=false): per ogni persona solo il metodo di pagamento; la fattura/ricevuta
            // usa i dati del nominativo, come privato (vedi DatiFatturazioneCorso::inputDaNominativo()).
            'nominativi.*.fatturazione.metodo_pagamento' => ['nullable', Rule::in($metodiPagamento)],

            // Honeypot anti-bot: campo invisibile a un utente reale, spesso compilato dai bot più
            // semplici che riempiono ogni campo del form. Se arriva valorizzato, la richiesta è respinta
            // come se fosse un normale errore di validazione (nessun messaggio "sei un bot" che
            // aiuterebbe a raffinare l'attacco).
            'sito_web' => ['prohibited'],

            // CAPTCHA Cloudflare Turnstile, solo se attivato dal pannello (vedi App\Rules\TurnstileToken).
            'cf-turnstile-response' => [Rule::requiredIf(SicurezzaForm::current()->turnstileAttivo()), new TurnstileToken],

            'privacy_accettata' => ['accepted'],
            'autodichiarazione_terzi' => ['nullable', 'boolean'],
            // Le spunte appartengono a chi compila il modulo (il referente, o l'iscritto stesso se si iscrive da
            // solo): mai a persone iscritte da altri, che vengono invitate a decidere via email.
            'consenso_promemoria' => ['nullable', 'boolean'],
            'consenso_newsletter' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Spunta "fatturazione a me, stessi dati" (solo modalità "solo io": pagamento unico, un solo
     * nominativo): il blocco 'fatturazione' non viene inviato, lo ricostruisce il controller dal
     * nominativo. Condizioni difensive: fuori da quel caso il flag è ignorato e valgono le regole normali.
     */
    public function fatturazioneComeIscritto(): bool
    {
        return $this->boolean('fatturazione_come_iscritto')
            && $this->boolean('fatturazione_unica')
            && count($this->input('nominativi', [])) === 1;
    }

    /** Nomi leggibili dei campi nei messaggi di errore (al posto di "nominativi.0.nome"). */
    public function attributes(): array
    {
        return [
            'richiedente.nome' => 'nome di chi compila il modulo',
            'richiedente.cognome' => 'cognome di chi compila il modulo',
            'richiedente.email' => 'email di chi compila il modulo',
            'richiedente.telefono' => 'telefono di chi compila il modulo',
            'richiedente.codice_fiscale' => 'codice fiscale di chi compila il modulo',
            'richiedente.via' => 'via / largo / piazza / località di chi compila il modulo',
            'richiedente.comune' => 'comune di chi compila il modulo',
            'richiedente.provincia' => 'provincia di chi compila il modulo',
            'richiedente.cap' => 'CAP di chi compila il modulo',
            'nominativi.*.nome' => 'nome della persona da iscrivere',
            'nominativi.*.cognome' => 'cognome della persona da iscrivere',
            'nominativi.*.email' => 'email della persona da iscrivere',
            'nominativi.*.telefono' => 'telefono della persona da iscrivere',
            'nominativi.*.codice_fiscale' => 'codice fiscale della persona da iscrivere',
            'nominativi.*.via' => 'via / largo / piazza / località della persona da iscrivere',
            'nominativi.*.comune' => 'comune della persona da iscrivere',
            'nominativi.*.provincia' => 'provincia della persona da iscrivere',
            'nominativi.*.cap' => 'CAP della persona da iscrivere',
            'fatturazione.nome' => 'nome di fatturazione',
            'fatturazione.cognome' => 'cognome di fatturazione',
            'fatturazione.via' => 'via / largo / piazza / località di fatturazione',
            'fatturazione.comune' => 'comune di fatturazione',
            'fatturazione.provincia' => 'provincia di fatturazione',
            'fatturazione.cap' => 'CAP di fatturazione',
            'fatturazione.codice_fiscale' => 'codice fiscale di fatturazione',
            'fatturazione.ragione_sociale' => 'ragione sociale di fatturazione',
            'fatturazione.partita_iva' => 'partita IVA di fatturazione',
            'fatturazione.codice_destinatario' => 'codice destinatario di fatturazione',
            'fatturazione.pec' => 'PEC di fatturazione',
        ];
    }

    /** Messaggio generico per l'honeypot: non deve rivelare che 'sito_web' è una trappola anti-bot. */
    public function messages(): array
    {
        return [
            'sito_web.prohibited' => 'Richiesta non valida, riprova.',
            'cf-turnstile-response.required' => 'Completa la verifica anti-robot prima di inviare.',
            'fatturazione.codice_destinatario.regex' => 'Il codice destinatario deve essere di 7 caratteri (6 per la pubblica amministrazione), solo lettere e numeri.',
            'fatturazione.pec.email' => 'La PEC deve essere un indirizzo email valido.',
        ];
    }

    /** Codice fiscale normalizzato del referente (chi compila). */
    public function cfReferente(): string
    {
        return strtoupper((string) $this->input('richiedente.codice_fiscale'));
    }

    /**
     * - Autodichiarazione ("dichiaro di aver ricevuto autorizzazione a iscrivere le persone indicate"):
     *   obbligatoria se tra i nominativi c'è almeno una persona diversa dal referente (confronto sul
     *   codice fiscale). Non esprimibile con un required_if statico, quindi qui.
     * - Maggiorenni: l'iscrizione online è riservata agli adulti (età dal codice fiscale); i minorenni si
     *   rivolgono alla segreteria. Si controllano i nominativi e, se non è tra loro, anche il referente.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $referente = $this->cfReferente();
            $nominativi = $this->input('nominativi', []);
            $cfNominativi = array_map(fn ($n) => strtoupper((string) ($n['codice_fiscale'] ?? '')), $nominativi);

            $iscriveTerzi = collect($cfNominativi)->contains(fn ($cf) => $cf !== $referente);

            if ($iscriveTerzi && ! $this->boolean('autodichiarazione_terzi')) {
                $validator->errors()->add(
                    'autodichiarazione_terzi',
                    'Devi dichiarare di aver ricevuto autorizzazione a iscrivere le persone indicate.'
                );
            }

            // Mai la stessa persona due volte nello stesso corso: né già iscritta, né ripetuta in questo invio.
            $corso = $this->route('corso');
            $viste = [];
            foreach ($cfNominativi as $i => $cf) {
                if ($cf === '') {
                    continue;
                }
                $giaIscritta = $corso && IscrizioneCorso::attive()->where('corso_id', $corso->id)->where('codice_fiscale', $cf)->exists();
                if ($giaIscritta || in_array($cf, $viste, true)) {
                    $validator->errors()->add("nominativi.{$i}.codice_fiscale", "Il codice fiscale {$cf} risulta già iscritto a questo corso.");
                }
                $viste[] = $cf;
            }

            $controlla = [];
            foreach ($cfNominativi as $i => $cf) {
                $controlla["nominativi.{$i}.codice_fiscale"] = $cf;
            }
            if (! in_array($referente, $cfNominativi, true)) {
                $controlla['richiedente.codice_fiscale'] = $referente;
            }

            foreach ($controlla as $campo => $cf) {
                if ($validator->errors()->has($campo) || $cf === '') {
                    continue; // già segnalato (formato) o mancante
                }
                $maggiorenne = new Maggiorenne;
                $maggiorenne->validate($campo, $cf, fn ($messaggio) => $validator->errors()->add($campo, $messaggio));
            }
        });
    }
}
