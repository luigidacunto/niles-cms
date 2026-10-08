{{--
    Banner + modale preferenze cookie. Nessuna dipendenza nuova: Alpine (già nello stack) + un
    cookie first-party `cookie_consent` = {v:1, statistiche:bool, ts:epoch}, scadenza ~6 mesi.
    partials/analytics.blade.php legge lo stesso cookie e carica GA4 solo se `statistiche` è true.

    Incluso dal layout solo quando c'è qualcosa da consentire (GA4 configurato) o fuori produzione.
    Riapribile da qualunque link con href="#cookie-preferences" (footer, pagina Cookie Policy).
--}}
<div x-data="cookieConsent()" x-cloak>

    {{-- Barra (stile cri.it: fascia scura in basso, "Accetta tutti" rosso) --}}
    <div x-show="showBar" x-transition.opacity
         class="fixed inset-x-0 bottom-0 z-50 bg-[#2b2b2b] text-white text-sm shadow-[0_-2px_12px_rgba(0,0,0,.35)]">
        <div class="max-w-6xl mx-auto px-4 py-4 flex flex-col md:flex-row md:items-center gap-3">
            <p class="flex-1 leading-relaxed text-white/90">
                Questo sito usa cookie tecnici necessari al funzionamento e, previo tuo consenso,
                strumenti di statistica. Leggi la
                <a href="{{ $cookiePage ? route('pages.show', $cookiePage) : '#cookie-preferences' }}"
                   class="underline hover:text-white">Cookie Policy</a>.
            </p>
            <div class="flex flex-wrap gap-2 shrink-0">
                <button type="button" @click="openModal()"
                        class="px-4 py-2 rounded border border-white/40 hover:bg-white/10">Personalizza</button>
                <button type="button" @click="rejectAll()"
                        class="px-4 py-2 rounded border border-white/40 hover:bg-white/10">Rifiuta tutti</button>
                <button type="button" @click="acceptAll()"
                        class="px-4 py-2 rounded bg-[#cc0000] hover:bg-[#a30000] font-semibold">Accetta tutti</button>
            </div>
        </div>
    </div>

    {{-- Modale preferenze --}}
    <div x-show="showModal" x-transition.opacity @keydown.escape.window="showModal = false"
         class="fixed inset-0 z-[60] bg-black/60 flex items-center justify-center p-4" @click.self="showModal = false">
        <div class="bg-white text-gray-800 rounded-lg max-w-lg w-full max-h-[90vh] overflow-y-auto shadow-xl">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold">Preferenze cookie</h2>
            </div>
            <div class="px-6 py-4 space-y-5 text-sm">
                <div>
                    <div class="flex items-center justify-between">
                        <span class="font-semibold">Necessari</span>
                        <span class="text-xs uppercase tracking-wide text-gray-500">Sempre attivi</span>
                    </div>
                    <p class="text-gray-600 mt-1">
                        Cookie di sessione indispensabili per la navigazione e la sicurezza del sito.
                        Non richiedono consenso.
                    </p>
                </div>
                <div>
                    <label class="flex items-center justify-between cursor-pointer">
                        <span class="font-semibold">Statistiche</span>
                        <input type="checkbox" x-model="prefs.statistiche" class="h-4 w-4 accent-[#cc0000]">
                    </label>
                    <p class="text-gray-600 mt-1">
                        Strumenti di analisi del traffico (es. Google Analytics) per capire come viene
                        usato il sito. Disattivati, nessuna statistica con cookie viene raccolta.
                    </p>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-gray-200 flex flex-wrap justify-end gap-2">
                <button type="button" @click="rejectAll()"
                        class="px-4 py-2 rounded border border-gray-300 hover:bg-gray-50">Rifiuta tutti</button>
                <button type="button" @click="save()"
                        class="px-4 py-2 rounded border border-gray-300 hover:bg-gray-50">Salva preferenze</button>
                <button type="button" @click="acceptAll()"
                        class="px-4 py-2 rounded bg-[#cc0000] text-white font-semibold hover:bg-[#a30000]">Accetta tutti</button>
            </div>
        </div>
    </div>
</div>

<script>
    // Globale prima di Alpine.start(): questo <script> inline gira in fase di parsing, il bundle
    // JS (modulo Vite, deferred) parte dopo.
    function cookieConsent() {
        return {
            showBar: false,
            showModal: false,
            prefs: { statistiche: false },

            init() {
                const saved = this.read();
                this.showBar = !saved;
                if (saved) this.prefs.statistiche = !!saved.statistiche;

                window.addEventListener('cookie-consent:open', () => this.openModal());
                // Link "Gestisci cookie" ovunque nel DOM (footer, corpo della Cookie Policy):
                // usa href="#cookie-preferences", indipendente da eventuali sanitizzatori HTML.
                document.addEventListener('click', (e) => {
                    const a = e.target.closest('a[href="#cookie-preferences"]');
                    if (a) { e.preventDefault(); this.openModal(); }
                });
            },

            read() {
                const m = document.cookie.match(/(?:^|;\s*)cookie_consent=([^;]+)/);
                if (!m) return null;
                try { return JSON.parse(decodeURIComponent(m[1])); } catch (e) { return null; }
            },

            write() {
                const v = { v: 1, statistiche: !!this.prefs.statistiche, ts: Math.floor(Date.now() / 1000) };
                document.cookie = 'cookie_consent=' + encodeURIComponent(JSON.stringify(v)) +
                    ';path=/;max-age=15552000;SameSite=Lax' +
                    (location.protocol === 'https:' ? ';Secure' : '');
                window.dispatchEvent(new CustomEvent('consent:updated', { detail: v }));
                this.showBar = false;
                this.showModal = false;
                // GA già caricato in questa pagina e consenso appena revocato: ricarico pulito.
                if (window.__gaLoaded && !v.statistiche) location.reload();
            },

            openModal() { this.showModal = true; },
            acceptAll() { this.prefs.statistiche = true; this.write(); },
            rejectAll() { this.prefs.statistiche = false; this.write(); },
            save() { this.write(); },
        };
    }
</script>
