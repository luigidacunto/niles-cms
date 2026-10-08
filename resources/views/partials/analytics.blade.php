{{--
    Script di statistiche. Reso solo in produzione.
    - Cookieless (GoatCounter): sempre attivo, nessun consenso — vedi config/tracking.php.
    - GA4: caricato SOLO se il visitatore ha accettato la categoria "Statistiche" nel banner
      (cookie `cookie_consent`, gestito da partials/cookie-consent.blade.php). Nessun reload:
      all'accettazione arriva l'evento `consent:updated` e lo script viene iniettato al volo.
    Ogni strumento ha anche un interruttore rapido da pannello (Dati del comitato →
    goatcounter_enabled/ga_enabled, default true): spento da lì, non si carica anche se configurato
    nel .env — comodo per disattivare al volo senza toccare il server.
--}}
@production
    @php($committeeInfo ??= \App\Models\CommitteeInfo::current())
    @php($cookieless = $committeeInfo->goatcounter_enabled ? config('tracking.cookieless') : [])
    @php($ga = $committeeInfo->ga_enabled ? config('tracking.ga.id') : null)

    @if (!empty($cookieless['src']) && !empty($cookieless['endpoint']))
        <script data-goatcounter="{{ $cookieless['endpoint'] }}"
                async src="{{ $cookieless['src'] }}"></script>
    @endif

    @if ($ga)
        <script>
            (function () {
                var ID = @json($ga);
                function hasStats() {
                    var m = document.cookie.match(/(?:^|;\s*)cookie_consent=([^;]+)/);
                    if (!m) return false;
                    try { return !!JSON.parse(decodeURIComponent(m[1])).statistiche; }
                    catch (e) { return false; }
                }
                function loadGA() {
                    if (window.__gaLoaded) return;
                    window.__gaLoaded = true;
                    var s = document.createElement('script');
                    s.async = true;
                    s.src = 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(ID);
                    document.head.appendChild(s);
                    window.dataLayer = window.dataLayer || [];
                    window.gtag = function () { dataLayer.push(arguments); };
                    gtag('js', new Date());
                    gtag('config', ID, { anonymize_ip: true });
                }
                if (hasStats()) loadGA();
                window.addEventListener('consent:updated', function () { if (hasStats()) loadGA(); });
            })();
        </script>
    @endif
@endproduction
