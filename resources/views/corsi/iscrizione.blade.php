@extends('layouts.public')

@section('title', 'Iscrizione: '.$corso->tipologia->nome.' — '.config('app.public_name'))
@section('meta_description', $corso->tipologia->nome.' — '.$corso->periodoLabel().($corso->sedeLabel() ? ', '.$corso->sedeLabel() : '').'. Iscriviti online.')
{{-- Anteprima social: immagine della tipologia; senza, il layout ripiega sul logo del comitato. --}}
@if ($corso->immagineUrl())
    @section('og_image', $corso->immagineUrl())
@endif

@push('jsonld')
    {{-- Event schema.org: dati del corso aperto (questa vista è solo per corsi con iscrizioni aperte). --}}
    <script type="application/ld+json">
        {!! json_encode(array_filter([
            '@@context' => 'https://schema.org',
            '@type' => 'Event',
            'name' => $corso->tipologia->nome,
            'description' => \Illuminate\Support\Str::limit(strip_tags($corso->descrizione ?: $corso->tipologia->nome.' organizzato da '.config('app.public_name')), 300),
            'startDate' => $corso->inizioLocale()->toIso8601String(),
            'endDate' => $corso->fineLocale()->toIso8601String(),
            'eventStatus' => 'https://schema.org/EventScheduled',
            'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
            'location' => $corso->sedeLabel() ? ['@type' => 'Place', 'name' => $corso->sedeLabel(), 'address' => $corso->sedeLabel()] : null,
            'image' => $corso->immagineUrl() ? url($corso->immagineUrl()) : null,
            'organizer' => ['@type' => 'Organization', 'name' => config('app.public_name'), 'url' => url('/')],
            'offers' => [
                '@type' => 'Offer',
                'url' => route('corsi.iscrizione.show', $corso),
                'price' => (string) $corso->costo,
                'priceCurrency' => 'EUR',
                'availability' => 'https://schema.org/InStock',
            ],
        ]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}
    </script>
@endpush

@section('content')
<div class="max-w-3xl mx-auto px-4 py-10" x-data="iscrizioneForm()" x-effect="modalita === 'solo-me' && (fatturazioneUnica = true)">
    <h1 class="text-2xl font-bold text-gray-900 mb-1">{{ $corso->tipologia->nome }}</h1>
    <p class="text-sm text-gray-500 mb-6">Protocollo {{ $corso->protocollo }}
        @if ($corso->sedeLabel()) &middot; {{ $corso->sedeLabel() }} @endif
        &middot; {{ $corso->periodoLabel() }}
        @if ($corso->costo > 0) &middot; Quota: {{ number_format($corso->costo, 2, ',', '.') }} € @else &middot; Gratuito @endif
    </p>

    @if ($corso->descrizione)
        <div class="prose max-w-none mb-8 text-gray-700">{{ $corso->descrizione }}</div>
    @endif

    <p class="text-sm text-gray-600 mb-6">L'iscrizione online è riservata ai <strong>maggiorenni</strong>: per iscrivere un minorenne contatta la segreteria del Comitato.</p>

    @if ($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-800 rounded p-4 mb-6">
            <ul class="list-disc pl-5 space-y-1 text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('corsi.iscrizione.store', $corso) }}" class="space-y-8">
        @csrf

        {{-- Honeypot anti-bot: fuori schermo (non display:none, alcuni bot lo verificano), mai visibile
             o raggiungibile da tastiera per un utente reale. Vedi CorsoIscrizioneRequest. --}}
        <div style="position:absolute; left:-9999px" aria-hidden="true">
            <label for="sito_web">Non compilare questo campo</label>
            <input type="text" name="sito_web" id="sito_web" tabindex="-1" autocomplete="off">
        </div>

        {{-- Switch iniziale: la maggior parte si iscrive da sola, non ha senso far ripetere i dati due
             volte (una come "referente", una come nominativo) --}}
        <section class="border border-gray-200 rounded p-5">
            <h2 class="font-semibold text-gray-900 mb-3">Chi si iscrive?</h2>
            <div class="flex flex-col sm:flex-row gap-3">
                <label class="flex-1 flex items-center gap-2 border rounded px-4 py-3 cursor-pointer"
                    :class="modalita === 'solo-me' ? 'border-[#cc0000] bg-red-50' : 'border-gray-300'">
                    <input type="radio" name="modalita_ui" value="solo-me" x-model="modalita">
                    <span>Mi iscrivo solo io</span>
                </label>
                <label class="flex-1 flex items-center gap-2 border rounded px-4 py-3 cursor-pointer"
                    :class="modalita === 'altri' ? 'border-[#cc0000] bg-red-50' : 'border-gray-300'">
                    <input type="radio" name="modalita_ui" value="altri" x-model="modalita">
                    <span>Sto iscrivendo anche altre persone</span>
                </label>
            </div>
        </section>

        {{-- Richiedente/referente: solo se si iscrivono anche altri — chi compila non è necessariamente
             uno degli iscritti (es. un'azienda che iscrive i dipendenti). Quando ci si iscrive da soli,
             i dati del referente sono gli stessi dell'unico nominativo (vedi campi nascosti più sotto). --}}
        <template x-if="modalita === 'altri'">
            <section class="border border-gray-200 rounded p-5">
                <h2 class="font-semibold text-gray-900 mb-3">I tuoi dati (referente dell'iscrizione)</h2>
                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Nome</label>
                        <input name="richiedente[nome]" value="{{ old('richiedente.nome') }}" required class="form-input">
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Cognome</label>
                        <input name="richiedente[cognome]" value="{{ old('richiedente.cognome') }}" required class="form-input">
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Email</label>
                        <input type="email" name="richiedente[email]" value="{{ old('richiedente.email') }}" required class="form-input">
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Telefono</label>
                        <input name="richiedente[telefono]" value="{{ old('richiedente.telefono') }}" class="form-input">
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Codice fiscale</label>
                        <input name="richiedente[codice_fiscale]" x-model="richiedenteCf" value="{{ old('richiedente.codice_fiscale') }}" required maxlength="16" class="form-input uppercase">
                    </div>
                </div>
                <p class="text-xs text-gray-500 mt-3">Se ti stai iscrivendo anche tu, ripeti i tuoi dati anche tra i nominativi qui sotto, con lo stesso codice fiscale.</p>
            </section>
        </template>
        <template x-if="modalita === 'solo-me'">
            <div>
                <input type="hidden" name="richiedente[nome]" :value="nominativi[0]?.nome">
                <input type="hidden" name="richiedente[cognome]" :value="nominativi[0]?.cognome">
                <input type="hidden" name="richiedente[email]" :value="nominativi[0]?.email">
                <input type="hidden" name="richiedente[telefono]" :value="nominativi[0]?.telefono">
                <input type="hidden" name="richiedente[codice_fiscale]" :value="nominativi[0]?.codice_fiscale">
            </div>
        </template>

        {{-- Nominativi --}}
        <section>
            <h2 class="font-semibold text-gray-900 mb-3" x-show="modalita === 'altri'">Persone da iscrivere</h2>
            <h2 class="font-semibold text-gray-900 mb-3" x-show="modalita === 'solo-me'">I tuoi dati</h2>

            <template x-for="(n, index) in nominativi" :key="index">
                <div class="border border-gray-200 rounded p-5 mb-4">
                    <div class="flex items-center justify-between mb-3" x-show="modalita === 'altri'">
                        <h3 class="font-medium text-gray-800">Nominativo <span x-text="index + 1"></span></h3>
                        <button type="button" x-show="nominativi.length > 1" @click="remove(index)" class="text-sm text-red-600 hover:underline">Rimuovi</button>
                    </div>

                    <div class="grid sm:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Nome</label>
                            <input x-model="n.nome" :name="`nominativi[${index}][nome]`" required class="form-input">
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Cognome</label>
                            <input x-model="n.cognome" :name="`nominativi[${index}][cognome]`" required class="form-input">
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Email</label>
                            <input type="email" x-model="n.email" :name="`nominativi[${index}][email]`" required class="form-input">
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Telefono</label>
                            <input x-model="n.telefono" :name="`nominativi[${index}][telefono]`" required class="form-input">
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Codice fiscale</label>
                            <input x-model="n.codice_fiscale" :name="`nominativi[${index}][codice_fiscale]`" required maxlength="16" class="form-input uppercase">
                        </div>
                    </div>

                    <p class="text-sm text-gray-600 mb-2">Residenza</p>
                    <div class="grid sm:grid-cols-4 gap-4 mb-4">
                        <div class="sm:col-span-2">
                            <label class="block text-xs text-gray-500 mb-1">Via / largo / piazza / località</label>
                            <input :name="`nominativi[${index}][via]`" required class="form-input">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Comune</label>
                            <input :name="`nominativi[${index}][comune]`" required class="form-input">
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Prov.</label>
                                <input :name="`nominativi[${index}][provincia]`" required maxlength="2" class="form-input">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">CAP</label>
                                <input :name="`nominativi[${index}][cap]`" required maxlength="5" class="form-input">
                            </div>
                        </div>
                    </div>

                    <template x-if="modalita === 'altri' && !fatturazioneUnica">
                        <div class="border-t border-gray-100 pt-4">
                            <p class="text-sm text-gray-600 mb-2">Pagamento di questa persona</p>
                            @include('corsi.partials.metodo-pagamento', ['base' => '`nominativi[${index}][fatturazione]`'])
                            <p class="text-xs text-gray-500 mt-2">Ciascuna persona paga per sé e riceve fattura o ricevuta <strong>con i dati inseriti qui sopra, come privato</strong>. Per fatturare a un'azienda o a un unico intestatario seleziona «Pagamento unico» più sotto.</p>
                        </div>
                    </template>
                </div>
            </template>

            <button type="button" x-show="modalita === 'altri'" @click="add" class="text-sm font-semibold text-[#cc0000] hover:underline">
                + Aggiungi un altro nominativo
            </button>
        </section>

        {{-- Fatturazione (dopo i nominativi: prima i dati delle persone, poi come si paga). Con "solo io"
             la spunta copia i dati del nominativo nel server (DatiFatturazioneCorso::inputDaNominativo)
             e restano visibili solo i campi ancora necessari, cioè il metodo di pagamento. --}}
        <section class="border border-gray-200 rounded p-5">
            <h2 class="font-semibold text-gray-900 mb-3">Fatturazione</h2>

            <template x-if="modalita === 'altri'">
                <div>
                    <label class="flex items-center gap-2 mb-4 text-sm text-gray-700">
                        <input type="checkbox" name="fatturazione_unica" value="1" x-model="fatturazioneUnica" @if(old('fatturazione_unica', true)) checked @endif>
                        Pagamento unico per tutti i nominativi indicati (un'unica fattura/ricevuta)
                    </label>
                    <p class="text-xs text-gray-500 mb-4" x-show="!fatturazioneUnica">
                        Deseleziona se ognuno paga per sé: indica il metodo di pagamento di ciascuna persona, più sopra.
                    </p>
                </div>
            </template>
            <template x-if="modalita === 'solo-me'">
                <div>
                    <input type="hidden" name="fatturazione_unica" value="1">
                    <label class="flex items-center gap-2 mb-4 text-sm text-gray-700">
                        <input type="checkbox" name="fatturazione_come_iscritto" value="1" x-model="stessiDati">
                        Fatturazione a me, con gli stessi dati indicati sopra
                    </label>
                </div>
            </template>

            <template x-if="modalita === 'solo-me' && stessiDati">
                <div>
                    @include('corsi.partials.metodo-pagamento', ['base' => "'fatturazione'"])
                </div>
            </template>
            <template x-if="fatturazioneUnica && !(modalita === 'solo-me' && stessiDati)">
                <div>
                    @include('corsi.partials.blocco-fatturazione', ['base' => "'fatturazione'"])
                </div>
            </template>
        </section>

        {{-- Privacy / autodichiarazione / newsletter --}}
        <section class="border border-gray-200 rounded p-5 space-y-4 text-sm">
            <label class="flex items-start gap-2">
                <input type="checkbox" name="privacy_accettata" value="1" required class="mt-1" @checked(old('privacy_accettata'))>
                <span>
                    Accetto il trattamento dei dati per l'iscrizione a questo corso, come descritto
                    nell'<a href="{{ route('privacy-policy.show', 'corsi-popolazione') }}" target="_blank" class="underline text-[#cc0000]">informativa privacy</a>.
                    <span class="text-red-600">(obbligatorio)</span>
                </span>
            </label>

            <label class="flex items-start gap-2" x-show="modalita === 'altri'">
                <input type="checkbox" name="autodichiarazione_terzi" value="1" class="mt-1" @checked(old('autodichiarazione_terzi'))>
                <span>Dichiaro di aver ricevuto autorizzazione dalle persone sopra indicate a fornire i loro dati e a iscriverle per loro conto. <span class="text-red-600">(obbligatorio se iscrivi altre persone)</span></span>
            </label>

            {{-- Consensi facoltativi, due finalità distinte (config/consensi.php). Appartengono a chi compila:
                 il promemoria dell'attestato solo se frequenta anche lui; le persone iscritte da altri
                 decidono loro, dal link nell'email di iscrizione. --}}
            <div class="border-t border-gray-100 pt-4 space-y-3">
                <p class="font-medium text-gray-800">Comunicazioni dal Comitato <span class="text-gray-500 font-normal">(facoltative, revocabili in qualsiasi momento)</span></p>

                <label class="flex items-start gap-2" x-show="referenteFrequenta()">
                    <input type="checkbox" name="consenso_promemoria" value="1" class="mt-1" @checked(old('consenso_promemoria'))>
                    <span>{{ config('consensi.promemoria.testo') }}</span>
                </label>

                <label class="flex items-start gap-2">
                    <input type="checkbox" name="consenso_newsletter" value="1" class="mt-1" @checked(old('consenso_newsletter'))>
                    <span>{{ config('consensi.newsletter.testo') }}</span>
                </label>

                <p class="text-xs text-gray-500" x-show="modalita === 'altri'">
                    Le altre persone che iscrivi riceveranno un'email con un link per scegliere da sole se ricevere promemoria e newsletter.
                </p>
            </div>
        </section>

        {{-- Cloudflare Turnstile (opzionale, attivato da Pannello → Sicurezza moduli pubblici). Verifica
             lato server in App\Rules\TurnstileToken; qui solo il widget, che aggiunge al form il campo
             nascosto `cf-turnstile-response`. --}}
        @if ($sicurezza->turnstileAttivo())
            <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
            <div class="cf-turnstile" data-sitekey="{{ $sicurezza->turnstile_site_key }}" data-language="it"></div>
            <p class="text-xs text-gray-500">Il modulo è protetto da Cloudflare Turnstile — <a href="{{ route('privacy-policy.show', 'corsi-popolazione') }}#captcha" target="_blank" class="underline">come vengono trattati i dati</a>.</p>
        @endif

        <button type="submit" class="bg-[#cc0000] text-white font-semibold px-6 py-3 rounded hover:bg-[#a30000]">
            Invia iscrizione
        </button>
    </form>
</div>

<script>
    function iscrizioneForm() {
        return {
            modalita: 'solo-me',
            fatturazioneUnica: {{ old('fatturazione_unica', true) ? 'true' : 'false' }},
            stessiDati: {{ (! session()->hasOldInput() || old('fatturazione_come_iscritto')) ? 'true' : 'false' }},
            richiedenteCf: '',
            nominativi: [{ nome: '', cognome: '', email: '', telefono: '', codice_fiscale: '' }],
            add() { this.nominativi.push({ nome: '', cognome: '', email: '', telefono: '', codice_fiscale: '' }) },
            remove(index) { this.nominativi.splice(index, 1) },
            // Il referente frequenta anche lui? (da solo: sempre; altrimenti se il suo codice fiscale è tra i nominativi)
            referenteFrequenta() {
                if (this.modalita === 'solo-me') return true
                const cf = (this.richiedenteCf || '').trim().toUpperCase()
                return cf !== '' && this.nominativi.some(n => (n.codice_fiscale || '').trim().toUpperCase() === cf)
            },
        }
    }
</script>
@stop
