<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Category;
use App\Models\CommitteeInfo;
use App\Models\ComunicazioneSoci;
use App\Models\DocumentCategory;
use App\Models\EmailTemplate;
use App\Models\Page;
use App\Models\PrivacyPolicy;
use App\Models\TipologiaCorso;
use App\Support\AreaSoci;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seed di **prima installazione**: crea il primo amministratore e tutto il contenuto di base del sito
 * (categorie, struttura del menu, pagine di sistema, aree documenti, tipologie di corso, informative
 * privacy, template email, area soci). Se esiste già un qualsiasi admin non fa nulla (guardia su
 * `Admin::exists()`, non su una email fissa): rilanciare `db:seed` su un sito in uso non deve né ricreare
 * un account di fabbrica né sovrascrivere le personalizzazioni fatte dal pannello.
 *
 * Crea soltanto, non aggiorna mai nulla di esistente. Gli aggiornamenti successivi alla prima versione
 * pubblica si fanno con seeder separati, uno per intervento, lanciati con `db:seed --class=...` e scritti
 * in modo da non toccare ciò che l'installazione ha già personalizzato.
 *
 * L'admin creato qui è `protected = true` (vedi `App\Models\Admin` e `Admin\AdminUsersController`):
 * non eliminabile ed è impossibile fargli perdere il ruolo admin dal pannello, nemmeno da un altro
 * admin: serve garantire che resti sempre almeno un amministratore raggiungibile.
 *
 * Credenziali iniziali da `.env` (`INITIAL_ADMIN_EMAIL`, `INITIAL_ADMIN_PASSWORD`, vedi `config/app.php`):
 * se la password non è impostata ne viene generata una casuale, stampata una sola volta dal comando.
 */
class FirstInstallSeeder extends Seeder
{
    public function run(): void
    {
        if (Admin::exists()) {
            return;
        }

        $this->amministratore();
        $this->categorie();
        $this->pagine();
        $this->pagineLegali();
        $this->documenti();
        $this->tipologieCorso();
        $this->informativePrivacy();
        $this->templateEmail();
        $this->areaSoci();
    }

    private function amministratore(): void
    {
        $password = config('app.initial_admin.password');
        $generata = blank($password);
        if ($generata) {
            $password = Str::random(16);
        }

        $admin = Admin::create([
            'name' => 'Administrator',
            'email' => config('app.initial_admin.email'),
            'password' => $password,
            'role' => 'admin',
            'password_login_enabled' => true, // deve poter entrare anche prima di configurare l'invio email OTP
        ]);

        $admin->forceFill(['protected' => true])->save();

        if ($generata) {
            $this->command?->warn("Primo amministratore: {$admin->email} / {$password}");
            $this->command?->warn('Password generata, mostrata una sola volta: annotarla e cambiarla al primo accesso.');
        }
    }

    /**
     * 9 categorie pensate per un comitato CRI, allineate allo schema attività di cri.it. Ogni categoria ha
     * (o può avere) una home pubblica: `news` (home + archivio notizie), `comunicato-stampa` (archivio
     * dedicato), le 7 aree attività (pagine sezione di Cosa Facciamo con feed notizie). `formazione` e
     * `innovazione` sono sezioni di sistema disattivabili (bozza) da chi non le usa.
     */
    private function categorie(): void
    {
        $categorie = [
            ['slug' => 'news', 'name' => 'Notizie', 'order' => 0],
            ['slug' => 'salute', 'name' => 'Salute', 'order' => 1],
            ['slug' => 'inclusione-sociale', 'name' => 'Inclusione Sociale', 'order' => 2],
            ['slug' => 'emergenze', 'name' => 'Emergenze', 'order' => 3],
            ['slug' => 'principi-e-valori', 'name' => 'Principi e Valori', 'order' => 4],
            ['slug' => 'giovani', 'name' => 'Giovani', 'order' => 5],
            ['slug' => 'formazione', 'name' => 'Formazione', 'order' => 6],
            ['slug' => 'innovazione', 'name' => 'Innovazione', 'order' => 7],
            // order alto di proposito: "Comunicato Stampa" è una categoria specifica, usata di rado:
            // deve restare sempre in fondo anche aggiungendone altre dal pannello.
            ['slug' => 'comunicato-stampa', 'name' => 'Comunicato Stampa', 'order' => 100],
        ];

        foreach ($categorie as $categoria) {
            Category::create($categoria);
        }
    }

    /**
     * Struttura del menu principale e pagine di sistema (`pages.system = true`: dal pannello non si
     * eliminano né si spostano, resta editabile il testo introduttivo, si disabilitano mettendole in bozza).
     * I segnaposto del menu nascono in bozza (`published = false`): si collega il contenuto e si pubblica
     * dal pannello.
     */
    private function pagine(): void
    {
        $committee = config('app.public_name');

        $this->pagina('Trasparenza', null, 0, [
            'excerpt' => 'Documenti e informazioni che il Comitato pubblica per obbligo di legge e per trasparenza verso soci e cittadini.',
            'body' => '<p>In questa sezione trovi i documenti che '.e($committee).' pubblica in adempimento '
                .'agli obblighi di trasparenza (in particolare la Legge 124/2017) e per rendere conto del proprio operato.</p>',
            'published' => true, 'in_menu' => false, 'system' => true,
        ]);

        $chiSiamo = $this->pagina('Chi Siamo', null, 0);
        $this->pagina('Struttura Organizzativa', $chiSiamo, 2, [
            'excerpt' => 'Gli organi del Comitato: presidente, vice presidente, consiglio direttivo.',
            'body' => '<p>Di seguito la struttura organizzativa del Comitato.</p>',
            'published' => true, 'system' => true,
        ]);

        $cosaFacciamo = $this->pagina('Cosa Facciamo', null, 1);

        // [titolo, slug categoria notizie, ordine, excerpt, body, pubblicata?, nel menu?]. Pubblicata/nel menu
        // di default: false/true. Nascono tutte in bozza: ogni comitato attiva (pubblica) quelle che usa;
        // `innovazione` resta anche fuori dal menu (sezione prevista dallo schema CRI ma non usata da tutti). Testo introduttivo generico CRI: il materiale specifico del
        // comitato si carica dal pannello. Il feed delle notizie e l'archivio sono automatici.
        $sezioni = [
            'principi-e-valori-umanitari' => ['Principi e Valori Umanitari', 'principi-e-valori', 0,
                'Disseminiamo il Diritto Internazionale Umanitario, i Principi Fondamentali e i Valori Umanitari e cooperiamo con gli altri membri del Movimento Internazionale.',
                '<p>Diffondiamo il Diritto Internazionale Umanitario, i Principi Fondamentali e i Valori Umanitari, in rete con gli altri membri del Movimento Internazionale di Croce Rossa e Mezzaluna Rossa.</p>'
                .'<h3>Diplomazia umanitaria</h3>'
                .'<p>Promuoviamo la tutela della dignità umana e la protezione delle persone in condizioni di vulnerabilità, anche attraverso il dialogo con le istituzioni del territorio.</p>'
                .'<h3>Educazione umanitaria</h3>'
                .'<p>Diffondiamo la conoscenza del Diritto Internazionale Umanitario e dei Principi Fondamentali tra scuole, cittadini e centri di formazione del territorio.</p>'],
            'salute' => ['Salute', 'salute', 1,
                'Tuteliamo e proteggiamo la salute e la vita.',
                '<p>Ci impegniamo a proteggere e promuovere la salute dei nostri soci e della comunità, intesa come stato di completo benessere fisico, mentale e sociale (OMS). Lavoriamo per garantire stili di vita sani e sicuri e per diffondere le competenze di primo soccorso necessarie a proteggere la propria vita e quella degli altri.</p>'
                .'<h3>Primo soccorso e formazione sanitaria</h3>'
                .'<p>Diffondiamo le competenze di primo soccorso e organizziamo corsi (es. manovre salvavita) per genitori, insegnanti ed educatori.</p>'
                .'<h3>Assistenza sanitaria sul territorio</h3>'
                .'<p>Offriamo servizi di emergenza-urgenza, trasporto sanitario e assistenza a eventi e manifestazioni, in convenzione con le strutture sanitarie locali.</p>'],
            'inclusione-sociale' => ['Inclusione Sociale', 'inclusione-sociale', 2,
                'Promuoviamo l\'inclusione sociale e lo sviluppo della persona.',
                '<p>Promuoviamo l\'inclusione sociale e lo sviluppo della persona, sostenendo le sue abilità e accrescendo il suo potenziale in un\'ottica di contrasto all\'esclusione sociale, in rete con le altre realtà del territorio.</p>'
                .'<h3>Sussidi e sostegno alle persone in difficoltà</h3>'
                .'<p>Sosteniamo le persone in situazione di vulnerabilità con aiuti economici, alimentari e di prima necessità, raccolti tramite iniziative di beneficenza e donazioni.</p>'
                .'<h3>Progetti contro la povertà</h3>'
                .'<p>Partecipiamo a programmi nazionali ed europei di contrasto alla povertà e alla deprivazione materiale, in rete con altre organizzazioni del territorio.</p>'],
            'emergenza-e-soccorso' => ['Emergenza e Soccorso', 'emergenze', 3,
                'Prepariamo le comunità e diamo risposta a emergenze e disastri.',
                '<p>Prepariamo il territorio e diamo risposta a emergenze e disastri, come parte del Sistema Nazionale di Protezione Civile: dalla previsione dei rischi alla formazione dei volontari, dalla risposta immediata al supporto delle comunità nella fase di ripristino.</p>'
                .'<h3>Previsione e prevenzione</h3>'
                .'<p>Studiamo il territorio per conoscere i rischi a cui è maggiormente esposto e formiamo volontari e cittadini per essere pronti a un\'emergenza.</p>'
                .'<h3>Risposta alle emergenze</h3>'
                .'<p>Interveniamo in caso di calamità con soccorso e assistenza alla popolazione, fino al ripristino delle normali condizioni di vita.</p>'],
            'giovani' => ['Giovani', 'giovani', 4,
                'Promuoviamo lo sviluppo dei giovani e una cultura della cittadinanza attiva.',
                '<p>Supportiamo lo sviluppo dei giovani come agenti di cambiamento, per costruire comunità più resilienti e inclusive, attraverso l\'educazione, la partecipazione attiva e nuove modalità di volontariato.</p>'
                .'<h3>Educazione</h3>'
                .'<p>Agevoliamo i processi educativi dei giovani soci e volontari, per generare comportamenti positivi e consapevoli.</p>'
                .'<h3>Partecipazione</h3>'
                .'<p>Favoriamo il coinvolgimento attivo dei giovani nelle decisioni e nelle attività dell\'Associazione.</p>'],
            'formazione' => ['Formazione', 'formazione', 5,
                'Formiamo volontari, operatori e cittadini.',
                '<p>Dal 1864 la Croce Rossa Italiana fa della formazione lo strumento con cui prepara i propri volontari e diffonde nella comunità le competenze utili a proteggere la propria vita e quella degli altri.</p>'
                .'<h3>Formazione per i volontari</h3>'
                .'<p>Offriamo ai nostri volontari percorsi di formazione e aggiornamento continuo, per renderli sempre più competenti e consapevoli del ruolo dell\'Associazione.</p>'
                .'<h3>Formazione per la comunità</h3>'
                .'<p>Proponiamo anche alla cittadinanza corsi informativi, di diffusione e di abilitazione (es. primo soccorso, manovre salvavita), in base a quanto attivato dal Comitato.</p>'],
            'innovazione' => ['Innovazione', 'innovazione', 6,
                'Innoviamo per essere più efficaci ed efficienti al servizio dei nostri soci e del territorio.',
                '<p>Innovare significa promuovere efficacia ed efficienza nel modo in cui il Comitato risponde ai bisogni dei propri soci e del territorio: saper innovare vuol dire adattarsi ai cambiamenti, sviluppare nuovi strumenti e strategie.</p>'
                .'<h3>Innovazione per la partecipazione</h3>'
                .'<p>Garantiamo la piena partecipazione dei soci alla vita del Comitato, impiegando le competenze di tutti al servizio delle nuove sfide del territorio.</p>'
                .'<h3>Innovazione per la comunità</h3>'
                .'<p>Favoriamo una cultura della progettazione che comprende il monitoraggio e la valutazione delle attività, e sviluppiamo collaborazioni con le realtà e le istituzioni del territorio.</p>'
                .'<h3>Innovazione per la comunicazione e la digitalizzazione</h3>'
                .'<p>Curiamo la comunicazione verso i soci e verso l\'esterno, e lavoriamo per ammodernare gli strumenti e i sistemi informatici del Comitato.</p>',
                false, false], // bozza + fuori menu: si attiva quando il comitato la usa
        ];

        foreach ($sezioni as $slug => $s) {
            [$titolo, $slugCategoria, $ordine, $excerpt, $body] = $s;
            $this->pagina($titolo, $cosaFacciamo, $ordine, [
                'slug' => $slug,
                'excerpt' => $excerpt,
                'body' => $body,
                'published' => $s[5] ?? false,
                'in_menu' => $s[6] ?? true,
                'system' => true,
                'category_id' => Category::where('slug', $slugCategoria)->value('id'),
            ]);
        }

        // "Corsi di Formazione": pagina di sistema con template `corsi` (elenco dei corsi con iscrizioni aperte).
        $servizi = $this->pagina('Servizi', $cosaFacciamo, 5);
        $this->pagina('Corsi di Formazione', $servizi, 4, [
            'excerpt' => 'I corsi aperti alla popolazione: scopri le prossime date e iscriviti online.',
            'body' => '<p>Di seguito i corsi con iscrizioni aperte. Scegli quello che ti interessa e compila il modulo di iscrizione.</p>',
            'published' => true, 'system' => true, 'template' => 'corsi',
        ]);

        $volontariato = $this->pagina('Volontariato', null, 2);
        $this->pagina('Diventa Volontario', $volontariato, 0);
        $this->pagina('Formazione Volontari', $volontariato, 1);

        // Donazioni: pagina di sistema con blocco `embed_html` (script/form delle piattaforme di donazione,
        // reso senza sanitizer, editabile solo da admin).
        $sostienici = $this->pagina('Sostienici', null, 3);
        $this->pagina('Dona il 5x1000', $sostienici, 0);
        $this->pagina('Donazioni', $sostienici, 1, [
            'excerpt' => 'Sostieni le attività del Comitato con una donazione.',
            'body' => '<p>Con il tuo contributo aiuti il Comitato a garantire soccorso, assistenza e vicinanza alle persone più vulnerabili del territorio. Ogni donazione, anche piccola, fa la differenza.</p>',
            'system' => true,
        ]);

        $archivi = $this->pagina('Archivi', null, 4);
        $this->pagina('Notizie', $archivi, 0);

        $contatti = $this->pagina('Contatti', null, 5);
        $this->pagina('Dove Trovarci', $contatti, 0);
    }

    /** Crea una pagina (slug da `$extra['slug']` o dal titolo); `system` e `category_id` non sono fillable. */
    private function pagina(string $titolo, ?Page $genitore, int $ordine, array $extra = []): Page
    {
        return Page::unguarded(fn () => Page::create($extra + [
            'slug' => Str::slug($titolo),
            'title' => $titolo,
            'body' => '',
            'published' => false,
            'in_menu' => true,
            'parent_id' => $genitore?->id,
            'order' => $ordine,
        ]));
    }

    /**
     * Privacy Policy + Cookie Policy: pagine di sistema editabili, fuori dal menu, linkate dal footer e dal
     * banner cookie. Testo base generico e volutamente non specifico di un comitato: ogni installazione lo
     * adatta (titolare, contatti, strumenti effettivamente usati).
     */
    private function pagineLegali(): void
    {
        $c = e(config('app.public_name'));

        $policies = [
            'privacy' => ['Privacy Policy',
                'Come '.$c.' tratta i dati personali di chi visita questo sito e usa i suoi servizi.',
                '<p>La presente informativa è resa ai sensi degli artt. 13-14 del Regolamento (UE) 2016/679 '
                .'("GDPR") a chi consulta questo sito web.</p>'
                .'<h2>Titolare del trattamento</h2>'
                .'<p>Il titolare del trattamento è '.$c.'. Per esercitare i tuoi diritti o per qualsiasi '
                .'richiesta relativa ai tuoi dati puoi contattare il Comitato ai recapiti pubblicati nella '
                .'sezione contatti del sito.</p>'
                .'<h2>Tipi di dati trattati</h2>'
                .'<ul>'
                .'<li><strong>Dati di navigazione</strong>: i sistemi informatici acquisiscono, nel normale '
                .'funzionamento, alcuni dati la cui trasmissione è implicita nell\'uso dei protocolli di '
                .'Internet (es. indirizzi IP, pagine visitate, orari di accesso). Sono usati per ricavare '
                .'informazioni statistiche anonime e per controllare il corretto funzionamento del sito.</li>'
                .'<li><strong>Dati forniti volontariamente</strong>: l\'invio facoltativo di messaggi agli '
                .'indirizzi del Comitato o la compilazione di eventuali moduli comporta il trattamento dei '
                .'dati di contatto necessari a rispondere.</li>'
                .'<li><strong>Cookie</strong>: vedi la <a href="/cookie-policy">Cookie Policy</a>.</li>'
                .'</ul>'
                .'<h2>Finalità e base giuridica</h2>'
                .'<p>I dati sono trattati per erogare i contenuti e i servizi del sito, per rispondere alle '
                .'richieste degli utenti e per la sicurezza e la manutenzione del sito. La base giuridica è '
                .'il legittimo interesse del titolare a gestire il proprio sito e, per le richieste degli '
                .'utenti, il riscontro alla richiesta dell\'interessato.</p>'
                .'<h2>Destinatari e conservazione</h2>'
                .'<p>I dati possono essere trattati da fornitori di servizi tecnici (es. hosting) nominati '
                .'responsabili del trattamento, e non sono diffusi. Sono conservati per il tempo strettamente '
                .'necessario alle finalità indicate.</p>'
                .'<h2>Diritti dell\'interessato</h2>'
                .'<p>Puoi in ogni momento chiedere l\'accesso ai tuoi dati, la rettifica, la cancellazione, '
                .'la limitazione o l\'opposizione al trattamento, e proporre reclamo al Garante per la '
                .'protezione dei dati personali (www.garanteprivacy.it).</p>'],

            'cookie-policy' => ['Cookie Policy',
                'Quali cookie usa questo sito, quanto durano e come gestire il consenso.',
                '<p>I cookie sono piccoli file di testo che i siti visitati inviano al dispositivo '
                .'dell\'utente, dove vengono memorizzati per essere ritrasmessi agli stessi siti alla visita '
                .'successiva. Questo sito usa i cookie descritti di seguito, secondo le linee guida del '
                .'Garante per la protezione dei dati personali. Per il titolare del trattamento e i diritti '
                .'dell\'interessato vedi la <a href="/privacy">Privacy Policy</a>: questa pagina riguarda '
                .'solo i cookie.</p>'
                .'<h2>Categorie di cookie</h2>'
                .'<table border="1" cellpadding="6" cellspacing="0">'
                .'<thead><tr><th>Categoria</th><th>Finalità</th><th>Consenso</th></tr></thead>'
                .'<tbody>'
                .'<tr><td><strong>Necessari</strong></td><td>Cookie tecnici, indispensabili per la '
                .'navigazione, l\'autenticazione al pannello e la sicurezza (protezione dai moduli '
                .'falsificati) e per ricordare la tua scelta sui cookie stessa. Non richiedono consenso '
                .'perché senza di essi il sito non funziona.</td><td>Non richiesto</td></tr>'
                .'<tr><td><strong>Statistiche</strong></td><td>Analisi aggregata del traffico. Il sito usa '
                .'uno strumento senza cookie per le statistiche di base (i visitatori non sono identificabili '
                .'singolarmente) e Google Analytics, che installa cookie propri e tratta dati solo dopo il '
                .'tuo consenso esplicito (vedi sotto).</td><td>Richiesto (opt-in)</td></tr>'
                .'<tr><td><strong>Contenuti di terze parti</strong></td><td>Mappe, video e moduli di servizi '
                .'esterni incorporati in una pagina: restano disattivati finché non clicchi per caricarli '
                .'(vedi sotto), nessun cookie di terze parti parte prima di quel momento.</td>'
                .'<td>Nessuno (caricamento su tua azione)</td></tr>'
                .'</tbody></table>'
                .'<h2>Cookie usati nel dettaglio</h2>'
                .'<table border="1" cellpadding="6" cellspacing="0">'
                .'<thead><tr><th>Nome</th><th>Finalità</th><th>Durata</th></tr></thead>'
                .'<tbody>'
                .'<tr><td>Cookie di sessione dell\'applicazione</td><td>Mantiene lo stato della tua '
                .'navigazione (es. login al pannello).</td><td>Sessione del browser</td></tr>'
                .'<tr><td><code>XSRF-TOKEN</code></td><td>Protezione tecnica dei moduli da richieste '
                .'falsificate (sicurezza).</td><td>Sessione del browser</td></tr>'
                .'<tr><td><code>cookie_consent</code></td><td>Ricorda la scelta fatta sul banner cookie, così '
                .'non viene richiesta ad ogni visita.</td><td>180 giorni</td></tr>'
                .'<tr><td><code>_ga</code>, <code>_ga_*</code></td>'
                .'<td>Statistiche di traffico dettagliate per Google Analytics.</td>'
                .'<td>Fino a 2 anni</td></tr>'
                .'</tbody></table>'
                .'<h2>Trasferimento dati extra-UE</h2>'
                .'<p>Google Analytics comporta il trasferimento di alcuni dati (es. indirizzo IP, '
                .'identificativi del dispositivo) a Google LLC negli Stati Uniti per l\'elaborazione. '
                .'Il Garante per la protezione dei dati personali ha segnalato criticità su questo punto: '
                .'per questo lo strumento resta disattivato per chi non dà consenso esplicito, ed è '
                .'comunque disattivabile in qualsiasi momento.</p>'
                .'<h2>Contenuti di terze parti</h2>'
                .'<p>Alcune pagine possono incorporare contenuti da servizi esterni (es. Google Maps, video '
                .'YouTube, moduli Google Forms). Questi contenuti <strong>non si caricano automaticamente</strong>: '
                .'al loro posto trovi un riquadro con un pulsante, e solo cliccandolo il contenuto viene '
                .'effettivamente richiesto al servizio esterno, che da quel momento può impostare i propri '
                .'cookie secondo la propria informativa.</p>'
                .'<h2>Gestire il consenso</h2>'
                .'<p>Puoi modificare in qualsiasi momento le tue scelte tramite il link '
                .'<a href="#cookie-preferences">Gestisci cookie</a> (sempre raggiungibile in fondo alla '
                .'pagina). Puoi inoltre bloccare o cancellare i cookie dalle impostazioni del tuo browser, '
                .'ma alcune funzioni del sito potrebbero non funzionare correttamente senza i cookie '
                .'necessari.</p>'],
        ];

        foreach ($policies as $slug => [$titolo, $excerpt, $body]) {
            Page::unguarded(fn () => Page::create([
                'slug' => $slug, 'title' => $titolo, 'excerpt' => $excerpt, 'body' => $body,
                'published' => true, 'in_menu' => false, 'system' => true,
            ]));
        }
    }

    /**
     * Aree documenti: le quattro della sezione Trasparenza, una categoria generica per la Libreria documenti
     * (gli admin creano le altre da `/admin/documenti/categorie`) e quella riservata `organigramma`, dove
     * finiscono gli organigrammi caricati dalla pagina Struttura Organizzativa (non selezionabile a mano).
     */
    private function documenti(): void
    {
        $trasparenza = [
            ['slug' => 'albo', 'name' => 'Albo', 'order' => 0],
            ['slug' => 'sovvenzioni', 'name' => 'Sovvenzioni', 'order' => 1],
            ['slug' => 'consiglio-direttivo', 'name' => 'Consiglio Direttivo', 'order' => 2],
            ['slug' => 'assemblea-soci', 'name' => 'Assemblea Soci', 'order' => 3],
        ];

        foreach ($trasparenza as $categoria) {
            DocumentCategory::create($categoria + ['scope' => 'trasparenza']);
        }

        DocumentCategory::create(['slug' => 'documenti', 'name' => 'Documenti', 'scope' => 'library', 'selectable' => true, 'order' => 0]);
        DocumentCategory::create(['slug' => 'organigramma', 'name' => 'Organigramma', 'scope' => 'library', 'selectable' => false, 'order' => 99]);
    }

    /** Set base di tipologie di corso comuni per un comitato CRI. */
    private function tipologieCorso(): void
    {
        $tipologie = [
            ['sigla' => 'BLSD', 'nome' => 'Rianimazione cardiopolmonare e defibrillazione', 'rilascia_attestato' => true, 'costo_predefinito' => 35, 'validita_tipo' => 'mesi', 'validita_valore' => 24, 'order' => 0],
            ['sigla' => 'AGG-BLSD', 'nome' => 'Aggiornamento BLSD', 'rilascia_attestato' => true, 'costo_predefinito' => 15, 'validita_tipo' => 'mesi', 'validita_valore' => 24, 'order' => 1],
            ['sigla' => 'PSA', 'nome' => 'Primo Soccorso Aziendale', 'rilascia_attestato' => true, 'costo_predefinito' => 0, 'order' => 2],
            ['sigla' => 'MSP', 'nome' => 'Manovre Salvavita Pediatriche', 'rilascia_attestato' => false, 'costo_predefinito' => 30, 'order' => 3],
            ['sigla' => 'PSP', 'nome' => 'Primo Soccorso Popolazione', 'rilascia_attestato' => false, 'costo_predefinito' => 0, 'order' => 4],
        ];

        foreach ($tipologie as $tipologia) {
            TipologiaCorso::create($tipologia);
        }
    }

    /**
     * Un default per ogni tipologia in `config('privacy_policies')`. Chi vuole una versione diversa crea un
     * override da Pannello → Informative privacy, senza toccare il default.
     *
     * ⚠️ Testo di partenza (bozza), NON revisionato da un legale/DPO: va controllato e adattato (titolare,
     * eventuali destinatari terzi reali, tempi di conservazione) prima che un modulo che lo usa vada in produzione.
     */
    private function informativePrivacy(): void
    {
        $testoCorsiPopolazione = '<p>La presente informativa integra la <a href="/privacy">Privacy Policy</a> '
            .'generale del sito e riguarda specificamente i dati raccolti tramite il modulo di iscrizione '
            .'ai corsi di formazione, ai sensi degli artt. 13-14 del Regolamento (UE) 2016/679 ("GDPR").</p>'
            .'<h2>Titolare del trattamento</h2>'
            .'<p>Il titolare del trattamento è {nome_comitato}. Per esercitare i tuoi diritti o per '
            .'qualsiasi richiesta relativa ai tuoi dati puoi contattare il Comitato'
            .' (email: {email_comitato}, telefono: {telefono_comitato}).</p>'
            .'<h2>Dati trattati</h2>'
            .'<ul>'
            .'<li><strong>Dati anagrafici e di contatto</strong> dei partecipanti (nome, cognome, codice '
            .'fiscale, residenza, email, telefono).</li>'
            .'<li><strong>Dati di fatturazione</strong> (privato o azienda/associazione: eventuale ragione '
            .'sociale, partita IVA, indirizzo di fatturazione, metodo di pagamento indicato).</li>'
            .'</ul>'
            .'<h2>Finalità e base giuridica</h2>'
            .'<p>I dati sono trattati per gestire l\'iscrizione al corso, organizzare lo svolgimento, gestire '
            .'il pagamento della quota (dove prevista) ed emettere fattura/ricevuta, e — dove il corso lo '
            .'prevede — rilasciare l\'attestato di partecipazione. La base giuridica è l\'esecuzione di una '
            .'richiesta dell\'interessato (l\'iscrizione stessa) e, per i dati di fatturazione, l\'adempimento '
            .'di obblighi contabili e fiscali.</p>'
            .'<p>Se acconsenti separatamente (spunta facoltativa nel modulo), i tuoi dati di contatto sono '
            .'trattati anche per ricontattarti in futuro per promemoria di scadenza attestati o inviti a '
            .'nuove iniziative — mai per finalità pubblicitarie. Puoi revocare questo consenso in qualsiasi '
            .'momento contattando il Comitato.</p>'
            .'<h2>Destinatari e conservazione</h2>'
            .'<p>I dati di fatturazione possono essere comunicati a chi cura la contabilità del Comitato. '
            .'I dati sono conservati per la durata del rapporto e, per i documenti fiscali, per il periodo '
            .'previsto dalla normativa vigente.</p>'
            .'<h2>Diritti dell\'interessato</h2>'
            .'<p>Puoi in ogni momento chiedere l\'accesso ai tuoi dati, la rettifica, la cancellazione, la '
            .'limitazione o l\'opposizione al trattamento, e proporre reclamo al Garante per la protezione '
            .'dei dati personali (www.garanteprivacy.it).</p>';

        $testoSoci = '<p>La presente informativa riguarda i dati dei soci e dei dipendenti del Comitato e '
            .'l\'uso dell\'area riservata, ai sensi degli artt. 13-14 del Regolamento (UE) 2016/679 ("GDPR"). '
            .'Per la navigazione del sito vale la <a href="/privacy">Privacy Policy</a> generale.</p>'
            .'<h2>Titolare del trattamento</h2>'
            .'<p>Il titolare del trattamento è {nome_comitato}. Per qualsiasi richiesta relativa ai tuoi dati '
            .'puoi contattare il Comitato (email: {email_comitato}, telefono: {telefono_comitato}).</p>'
            .'<h2>Dati trattati e provenienza</h2>'
            .'<ul>'
            .'<li>Nome, cognome, codice fiscale, data di nascita, ruolo (volontario, volontario in estensione, '
            .'dipendente), email e numeri di telefono.</li>'
            .'<li>I dati provengono dall\'anagrafica gestita tramite i sistemi della Croce Rossa Italiana e dal '
            .'rapporto associativo con il Comitato.</li>'
            .'<li><strong>Registro degli accessi</strong> all\'area riservata: data, ora e indirizzo IP di ogni '
            .'accesso (riuscito o fallito), conservati per 12 mesi e usati solo per la sicurezza del servizio.</li>'
            .'</ul>'
            .'<h2>Finalità e base giuridica</h2>'
            .'<p>Gestione del rapporto associativo e dell\'attività del Comitato; fornitura dell\'accesso '
            .'all\'area riservata ai soci; sicurezza dei sistemi (legittimo interesse).</p>'
            .'<h2>Conservazione</h2>'
            .'<p>I dati anagrafici sono conservati finché sei socio e, dopo la cessazione, per il tempo '
            .'necessario agli obblighi di legge e alla tutela del Comitato; poi sono cancellati. '
            .'Il registro degli accessi è conservato 12 mesi.</p>'
            .'<h2>I tuoi diritti</h2>'
            .'<p>Puoi chiedere l\'accesso ai tuoi dati, la rettifica, la limitazione o l\'opposizione al '
            .'trattamento, e proporre reclamo al Garante per la protezione dei dati personali '
            .'(www.garanteprivacy.it). Finché sei socio, alcuni dati non possono essere cancellati perché '
            .'conservati per obbligo di legge o necessari al rapporto associativo. '
            .'Puoi invece <strong>chiedere in qualsiasi momento la disattivazione dell\'accesso all\'area '
            .'riservata</strong> dalla tua pagina "I miei dati": la richiesta è confermata da un '
            .'amministratore del Comitato.</p>';

        $default = [
            'soci' => [
                'titolo' => 'Informativa privacy — Soci e area riservata',
                'testo' => $testoSoci,
            ],
            'corsi-popolazione' => [
                'titolo' => 'Informativa privacy — Iscrizione ai corsi di formazione',
                'testo' => $testoCorsiPopolazione,
            ],
        ];

        foreach (array_keys(config('privacy_policies')) as $tipo) {
            if (! isset($default[$tipo])) {
                continue;
            }

            PrivacyPolicy::create([
                'tipo' => $tipo, 'is_default' => true,
                'titolo' => $default[$tipo]['titolo'], 'testo' => $default[$tipo]['testo'],
            ]);
        }
    }

    /** Testi predefiniti delle email di sistema (`config/email_templates.php`, chiave `predefinito`). */
    private function templateEmail(): void
    {
        foreach (config('email_templates') as $tipo => $definizione) {
            EmailTemplate::create(['tipo' => $tipo, 'is_default' => true] + $definizione['predefinito']);
        }
    }

    /**
     * Base dell'area soci: categoria documenti riservata e comunicazione di benvenuto, così la sezione non
     * parte mai vuota e c'è una guida per i primi accessi.
     */
    private function areaSoci(): void
    {
        AreaSoci::categoriaDocumenti();

        $nome = CommitteeInfo::current()->denominazione ?: config('app.public_name');

        ComunicazioneSoci::create([
            'slug' => 'benvenuto-nell-area-soci',
            'title' => 'Benvenuto nell\'area soci',
            'excerpt' => 'Che cos\'è quest\'area riservata e cosa troverai al suo interno.',
            'body' => '<p>Questa è l\'<strong>area riservata ai soci</strong> di '.e($nome).'. Vi accedi con il tuo indirizzo '
                .'email, senza password: ti inviamo un codice di 6 cifre ogni volta che vuoi entrare.</p>'
                .'<h2>Cosa trovi qui</h2>'
                .'<ul>'
                .'<li><strong>Comunicazioni</strong>: gli avvisi interni del comitato, visibili solo a chi ha effettuato l\'accesso. '
                .'Alle comunicazioni possono essere allegati documenti, scaricabili solo da qui.</li>'
                .'<li><strong>I miei dati</strong>: dal menu in alto a destra (l\'icona con il tuo nome) puoi consultare i dati che '
                .'il comitato ha di te. Se qualcosa non è corretto, contatta la segreteria.</li>'
                .'<li>In futuro qui troverai anche materiali e pagine dedicate ai soci.</li>'
                .'</ul>'
                .'<h2>Privacy e accesso</h2>'
                .'<p>Registriamo data, ora e indirizzo IP degli accessi per motivi di sicurezza. Puoi chiedere in qualsiasi momento '
                .'la disattivazione del tuo accesso dalla pagina "I miei dati". Tutti i dettagli sono nell\'informativa privacy dei soci.</p>',
            'published' => true,
            'published_at' => now(),
            'author_name' => $nome,
        ]);
    }
}
