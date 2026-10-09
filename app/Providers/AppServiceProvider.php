<?php

namespace App\Providers;

use App\Models\Admin;
use App\Models\Corso;
use App\Models\SicurezzaForm;
use App\Support\AreaSoci;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Event;
use JeroenNoten\LaravelAdminLte\Events\BuildingMenu;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Usati dal menu laterale di AdminLTE ('can' => '...') per nascondere agli editor i link riservati
        // agli admin: il controllo vero è EnsureAdminRole (middleware admin.role) sulle rotte, queste
        // definizioni nascondono soltanto i link.
        Gate::define('manage-users', fn (Admin $admin) => $admin->role === 'admin');
        Gate::define('manage-categories', fn (Admin $admin) => $admin->role === 'admin');
        Gate::define('manage-posts', fn (Admin $admin) => $admin->hasPermission('posts', 'read') || $admin->hasPermission('posts', 'write'));
        Gate::define('manage-pages', fn (Admin $admin) => $admin->hasPermission('pages', 'read') || $admin->hasPermission('pages', 'write'));
        // Trasparenza: documenti pubblicati su /trasparenza (permesso dedicato); le aree sono struttura, solo admin.
        Gate::define('manage-trasparenza', fn (Admin $admin) => $admin->hasPermission('trasparenza', 'read') || $admin->hasPermission('trasparenza', 'write'));
        Gate::define('manage-trasparenza-aree', fn (Admin $admin) => $admin->role === 'admin');
        // Libreria documenti generica (permesso 'documents'); le categorie sono struttura, solo admin.
        Gate::define('manage-documents', fn (Admin $admin) => $admin->hasPermission('documents', 'read') || $admin->hasPermission('documents', 'write'));
        Gate::define('manage-document-categories', fn (Admin $admin) => $admin->role === 'admin');
        // Struttura Organizzativa: sezione strutturale, solo admin (come le tassonomie).
        Gate::define('manage-struttura-organizzativa', fn (Admin $admin) => $admin->role === 'admin');
        // Dati anagrafici/contatto del comitato (footer pubblico): sezione strutturale, solo admin.
        Gate::define('manage-comitato', fn (Admin $admin) => $admin->role === 'admin');
        // Corsi di formazione (permesso 'corsi', flat: nessuno scoping per categoria/sede); il catalogo
        // (tipologie/sedi) è struttura, solo admin.
        Gate::define('manage-corsi', fn (Admin $admin) => $admin->hasPermission('corsi', 'read') || $admin->hasPermission('corsi', 'write'));
        Gate::define('manage-corsi-catalogo', fn (Admin $admin) => $admin->role === 'admin');
        // Impostazioni di sicurezza dei moduli pubblici (rate limiting/captcha): sezione strutturale, solo admin.
        // Voci dell'area soci: nascoste (e rotte 404 via middleware area.soci) se l'interruttore in Dati comitato è spento.
        Gate::define('manage-membri', fn (Admin $admin) => AreaSoci::attiva() && $admin->hasPermission('membri'));
        Gate::define('manage-comunicazioni-soci', fn (Admin $admin) => AreaSoci::attiva() && $admin->hasPermission('comunicazioni_soci'));
        Gate::define('see-amministrazione', fn (Admin $admin) => $admin->role === 'admin' || Gate::forUser($admin)->allows('manage-membri'));
        Gate::define('manage-sicurezza-form', fn (Admin $admin) => $admin->role === 'admin');
        // Informative privacy (default+override per tipologia): contenuto legale, solo admin.
        Gate::define('manage-privacy-policies', fn (Admin $admin) => $admin->role === 'admin');
        // Template delle email di sistema (testo inviato a nome del comitato): solo admin.
        Gate::define('manage-email-templates', fn (Admin $admin) => $admin->role === 'admin');

        // Demo: voce fissa nella barra in alto del pannello (le pagine admin estendono tutte adminlte::page).
        Event::listen(BuildingMenu::class, function (BuildingMenu $event) {
            if (config('app.demo')) {
                $event->menu->add([
                    'text' => 'Versione dimostrativa: le modifiche non vengono salvate',
                    'url' => '#',
                    'icon' => 'fas fa-flask',
                    'topnav' => true,
                    'classes' => 'font-weight-bold text-danger',
                ]);
            }
        });

        // Dati del template di pagina "corsi" (pages.template='corsi'): corsi con iscrizioni aperte.
        View::composer('pages.templates.corsi', fn ($view) => $view->with(
            'corsi', Corso::conIscrizioniAperte()->with('tipologia')->get()
        ));

        // Rate limiting sul modulo pubblico di iscrizione ai corsi, attivabile/disattivabile da
        // Pannello → Sicurezza moduli pubblici (SicurezzaForm::current()) senza toccare le route — letto
        // ad ogni richiesta, non al boot, altrimenti un cambio impostazione richiederebbe un riavvio.
        // Due soglie per ogni route (al minuto: ferma i bot a raffica; all'ora: quelli lenti). ⚠️ Le chiavi
        // devono differire (`:min`/`:ora`): con la stessa chiave le due finestre condividerebbero un contatore.
        // Soglie dal pannello (SicurezzaForm::limite()), predefiniti in config/sicurezza.php.
        $limiti = function (string $prefisso) {
            return function ($request) use ($prefisso) {
                $sicurezza = SicurezzaForm::current();

                return $sicurezza->rate_limiting_attivo
                    ? [
                        Limit::perMinute($sicurezza->limite("{$prefisso}_minuto"))->by($request->ip().':min'),
                        Limit::perHour($sicurezza->limite("{$prefisso}_ora"))->by($request->ip().':ora'),
                    ]
                    : Limit::none();
            };
        };
        // Pagina personale col token: sempre attivo (non spegnibile), serve solo a scoraggiare chi prova token a caso.
        RateLimiter::for('preferenze', fn ($request) => [
            Limit::perMinute(20)->by($request->ip().':min'),
            Limit::perHour(120)->by($request->ip().':ora'),
        ]);
        RateLimiter::for('corsi-iscrizione-show', $limiti('visite'));
        RateLimiter::for('corsi-iscrizione-store', $limiti('invii'));
    }
}
