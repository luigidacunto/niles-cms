<?php

use App\Http\Controllers\Admin\AdminUsersController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CommitteeInfoController;
use App\Http\Controllers\Admin\CorsoController;
use App\Http\Controllers\Admin\ComunicazioneSociController;
use App\Http\Controllers\Admin\CorsoIscrizioneController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DocumentCategoryController;
use App\Http\Controllers\Admin\DocumentController;
use App\Http\Controllers\Admin\LoginAuditController;
use App\Http\Controllers\Admin\MemberController;
use App\Http\Controllers\Admin\OtpController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\PostController;
use App\Http\Controllers\Admin\EmailTemplateController;
use App\Http\Controllers\Admin\PersonaController;
use App\Http\Controllers\Admin\PrivacyPolicyController;
use App\Http\Controllers\Admin\SicurezzaFormController;
use App\Http\Controllers\Admin\StrutturaOrganizzativaController;
use App\Http\Controllers\Admin\TipologiaCorsoController;
use App\Http\Controllers\Admin\TrasparenzaAreaController;
use App\Http\Controllers\Admin\TrasparenzaDocumentController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest:admin')->group(function () {
        // OTP via email è il flusso primario.
        Route::get('login', [OtpController::class, 'showRequest'])->name('login');
        Route::post('login', [OtpController::class, 'sendCode'])
            ->middleware('throttle:5,1')
            ->name('login.otp.send');
        Route::get('login/verifica', [OtpController::class, 'showVerify'])->name('login.otp.verify');
        Route::post('login/verifica', [OtpController::class, 'confirmCode'])
            ->middleware('throttle:10,1')
            ->name('login.otp.confirm');

        // Password: solo per gli account con password_login_enabled=true.
        Route::get('login/password', [AuthController::class, 'showLogin'])->name('login.password');
        Route::post('login/password', [AuthController::class, 'login'])->name('login.password.confirm');
    });

    Route::middleware('auth:admin')->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        // JSON per la dashboard (chiamata in async): esito del confronto con l'ultima versione pubblicata.
        Route::get('aggiornamenti', [DashboardController::class, 'aggiornamenti'])->middleware('admin.role')->name('aggiornamenti');

        Route::middleware('admin.role')->prefix('utenti')->name('users.')->group(function () {
            Route::get('/', [AdminUsersController::class, 'index'])->name('index');
            Route::get('nuovo', [AdminUsersController::class, 'create'])->name('create');
            Route::post('/', [AdminUsersController::class, 'store'])->name('store');
            Route::get('{admin}/modifica', [AdminUsersController::class, 'edit'])->name('edit');
            Route::put('{admin}', [AdminUsersController::class, 'update'])->name('update');
            Route::delete('{admin}', [AdminUsersController::class, 'destroy'])->name('destroy');
        });

        Route::middleware('admin.role')->prefix('categorie')->name('categories.')->group(function () {
            Route::get('/', [CategoryController::class, 'index'])->name('index');
            Route::get('nuova', [CategoryController::class, 'create'])->name('create');
            Route::post('/', [CategoryController::class, 'store'])->name('store');
            Route::get('{category}/modifica', [CategoryController::class, 'edit'])->name('edit');
            Route::put('{category}', [CategoryController::class, 'update'])->name('update');
            Route::delete('{category}', [CategoryController::class, 'destroy'])->name('destroy');
        });

        // Niente 'admin.role' qui: accessibile anche agli editor, permessi/categoria verificati
        // dentro PostController (hasPermission('posts', ...) + canManageCategory()).
        Route::prefix('post')->name('posts.')->group(function () {
            Route::get('/', [PostController::class, 'index'])->name('index');
            Route::get('nuovo', [PostController::class, 'create'])->name('create');
            Route::post('/', [PostController::class, 'store'])->name('store');
            Route::get('{post}/modifica', [PostController::class, 'edit'])->name('edit');
            Route::get('{post}', [PostController::class, 'show'])->name('show');
            Route::put('{post}', [PostController::class, 'update'])->name('update');
            Route::delete('{post}', [PostController::class, 'destroy'])->name('destroy');
        });

        // --- Trasparenza: documenti pubblicati su /trasparenza (solo PDF) ---
        Route::prefix('trasparenza')->group(function () {
            // Aree Trasparenza: struttura del sito, solo admin.
            Route::middleware('admin.role')->prefix('aree')->name('trasparenza-aree.')->group(function () {
                Route::get('/', [TrasparenzaAreaController::class, 'index'])->name('index');
                Route::get('nuova', [TrasparenzaAreaController::class, 'create'])->name('create');
                Route::post('/', [TrasparenzaAreaController::class, 'store'])->name('store');
                Route::get('{trasparenzaArea}/modifica', [TrasparenzaAreaController::class, 'edit'])->name('edit');
                Route::put('{trasparenzaArea}', [TrasparenzaAreaController::class, 'update'])->name('update');
                Route::delete('{trasparenzaArea}', [TrasparenzaAreaController::class, 'destroy'])->name('destroy');
            });

            // Niente 'admin.role': accessibile agli editor con permesso 'trasparenza' (verificato nel controller).
            Route::prefix('documenti')->name('trasparenza-documenti.')->group(function () {
                Route::get('/', [TrasparenzaDocumentController::class, 'index'])->name('index');
                Route::get('nuovo', [TrasparenzaDocumentController::class, 'create'])->name('create');
                Route::post('/', [TrasparenzaDocumentController::class, 'store'])->name('store');
                Route::get('{document}/modifica', [TrasparenzaDocumentController::class, 'edit'])->name('edit');
                Route::get('{document}', [TrasparenzaDocumentController::class, 'show'])->name('show');
                Route::put('{document}', [TrasparenzaDocumentController::class, 'update'])->name('update');
                Route::delete('{document}', [TrasparenzaDocumentController::class, 'destroy'])->name('destroy');
            });
        });

        // --- Libreria documenti generica: file da linkare nei contenuti ---
        // Le categorie vanno registrate PRIMA di {document}, altrimenti "categorie" verrebbe letto come id.
        Route::middleware('admin.role')->prefix('documenti/categorie')->name('document-categories.')->group(function () {
            Route::get('/', [DocumentCategoryController::class, 'index'])->name('index');
            Route::get('nuova', [DocumentCategoryController::class, 'create'])->name('create');
            Route::post('/', [DocumentCategoryController::class, 'store'])->name('store');
            Route::get('{documentCategory}/modifica', [DocumentCategoryController::class, 'edit'])->name('edit');
            Route::put('{documentCategory}', [DocumentCategoryController::class, 'update'])->name('update');
            Route::delete('{documentCategory}', [DocumentCategoryController::class, 'destroy'])->name('destroy');
        });

        // Niente 'admin.role': accessibile agli editor con permesso 'documents' (verificato nel controller).
        Route::prefix('documenti')->name('documents.')->group(function () {
            Route::get('/', [DocumentController::class, 'index'])->name('index');
            // Picker per l'editor Summernote (post/pagine): JSON dei documenti pubblicati. Prima di {document}.
            Route::get('picker', [DocumentController::class, 'picker'])->name('picker');
            Route::get('nuovo', [DocumentController::class, 'create'])->name('create');
            Route::post('/', [DocumentController::class, 'store'])->name('store');
            Route::get('{document}/modifica', [DocumentController::class, 'edit'])->name('edit');
            Route::get('{document}', [DocumentController::class, 'show'])->name('show');
            Route::put('{document}', [DocumentController::class, 'update'])->name('update');
            Route::delete('{document}', [DocumentController::class, 'destroy'])->name('destroy');
        });

        // Dati anagrafici/contatto del comitato (footer pubblico). Solo admin.
        Route::middleware('admin.role')->prefix('comitato')->name('comitato.')->group(function () {
            Route::get('/', [CommitteeInfoController::class, 'edit'])->name('edit');
            Route::put('/', [CommitteeInfoController::class, 'update'])->name('update');
        });

        // Interruttori anti-bot dei moduli pubblici (rate limiting/captcha). Solo admin.
        Route::middleware('admin.role')->prefix('sicurezza-form')->name('sicurezza-form.')->group(function () {
            Route::get('/', [SicurezzaFormController::class, 'edit'])->name('edit');
            Route::put('/', [SicurezzaFormController::class, 'update'])->name('update');
        });

        // Informative privacy per tipologia (default seedato + override opzionale). Solo admin.
        Route::middleware('admin.role')->prefix('informative-privacy')->name('privacy-policies.')->group(function () {
            Route::get('/', [PrivacyPolicyController::class, 'index'])->name('index');
            Route::get('{tipo}/modifica', [PrivacyPolicyController::class, 'edit'])->name('edit');
            Route::put('{tipo}', [PrivacyPolicyController::class, 'update'])->name('update');
            Route::delete('{tipo}', [PrivacyPolicyController::class, 'destroy'])->name('destroy');
        });

        // Persone dei corsi (anagrafica, consensi, export, anonimizzazione). Permesso 'corsi' verificato nel
        // controller; l'anonimizzazione è solo admin (middleware + controllo nel controller).
        Route::prefix('persone')->name('persone.')->group(function () {
            Route::get('/', [PersonaController::class, 'index'])->name('index');
            Route::get('esporta', [PersonaController::class, 'esporta'])->name('esporta');
            Route::get('{persona}', [PersonaController::class, 'show'])->name('show');
            Route::post('{persona}/consensi/{finalita}/revoca', [PersonaController::class, 'revoca'])->name('revoca');
            Route::post('{persona}/anonimizza', [PersonaController::class, 'anonimizza'])->middleware('admin.role')->name('anonimizza');
        });

        // Template delle email di sistema (default seedato + personalizzazione). Solo admin.
        Route::middleware('admin.role')->prefix('template-email')->name('email-templates.')->group(function () {
            Route::get('/', [EmailTemplateController::class, 'index'])->name('index');
            Route::get('{tipo}/modifica', [EmailTemplateController::class, 'edit'])->name('edit');
            Route::get('{tipo}/anteprima', [EmailTemplateController::class, 'anteprima'])->name('anteprima');
            Route::put('{tipo}', [EmailTemplateController::class, 'update'])->name('update');
            Route::delete('{tipo}', [EmailTemplateController::class, 'destroy'])->name('destroy');
        });

        // Struttura Organizzativa: composizione del costrutto board_members (pagina di sistema). Solo admin.
        Route::middleware('admin.role')->prefix('struttura-organizzativa')->name('struttura-organizzativa.')->group(function () {
            Route::get('/', [StrutturaOrganizzativaController::class, 'edit'])->name('edit');
            Route::put('/', [StrutturaOrganizzativaController::class, 'update'])->name('update');
        });

        // Registro accessi (admin e soci), sola lettura. Solo admin.
        Route::middleware('admin.role')->get('registro-accessi', [LoginAuditController::class, 'index'])->name('registro-accessi');

        // Comunicazioni interne ai soci (post riservati + allegati privati). Permesso unico 'comunicazioni_soci'
        // verificato nel controller; area spenta → 404.
        Route::prefix('comunicazioni-soci')->name('comunicazioni-soci.')->middleware('area.soci')->group(function () {
            Route::get('/', [ComunicazioneSociController::class, 'index'])->name('index');
            Route::get('nuova', [ComunicazioneSociController::class, 'create'])->name('create');
            Route::post('/', [ComunicazioneSociController::class, 'store'])->name('store');
            Route::get('{comunicazione}/modifica', [ComunicazioneSociController::class, 'edit'])->name('edit');
            Route::put('{comunicazione}', [ComunicazioneSociController::class, 'update'])->name('update');
            Route::delete('{comunicazione}', [ComunicazioneSociController::class, 'destroy'])->name('destroy');
        });

        // Anagrafica membri del comitato (+ import Excel). Niente admin.role: permesso unico 'membri'
        // (lettura+scrittura insieme) verificato nel controller. Import/restore prima di {member}.
        Route::prefix('membri')->name('membri.')->middleware('area.soci')->group(function () {
            Route::get('/', [MemberController::class, 'index'])->name('index');
            Route::get('nuovo', [MemberController::class, 'create'])->name('create');
            Route::post('/', [MemberController::class, 'store'])->name('store');
            Route::get('import', [MemberController::class, 'importForm'])->name('import');
            Route::post('import/anteprima', [MemberController::class, 'importPreview'])->name('import.preview');
            Route::post('import/conferma', [MemberController::class, 'importApply'])->name('import.apply');
            Route::put('{member}/respingi-richiesta', [MemberController::class, 'respingiRichiesta'])->name('respingi-richiesta');
            Route::put('{id}/ripristina', [MemberController::class, 'restore'])->whereNumber('id')->name('restore');
            Route::get('{member}/modifica', [MemberController::class, 'edit'])->name('edit');
            Route::put('{member}', [MemberController::class, 'update'])->name('update');
            Route::delete('{member}', [MemberController::class, 'destroy'])->name('destroy');
        });

        // Niente 'admin.role' qui: accessibile anche agli editor, permesso verificato dentro
        // PageController (hasPermission('pages', ...)) — il campo "template" resta admin-only nel
        // controller stesso, non a livello di rotta.
        Route::prefix('pagine')->name('pages.')->group(function () {
            Route::get('/', [PageController::class, 'index'])->name('index');
            Route::get('nuova', [PageController::class, 'create'])->name('create');
            Route::post('/', [PageController::class, 'store'])->name('store');
            Route::get('{page}/modifica', [PageController::class, 'edit'])->name('edit');
            Route::get('{page}', [PageController::class, 'show'])->name('show');
            Route::put('{page}', [PageController::class, 'update'])->name('update');
            Route::delete('{page}', [PageController::class, 'destroy'])->name('destroy');
        });

        // Catalogo corsi (Tipologie): struttura, solo admin — come Categorie/Categorie documenti.
        // Le Sedi corso NON hanno una sezione admin dedicata — si creano inline nel form Corso
        // (vedi CorsoController::risolviSede()) e restano poi selezionabili per i corsi successivi.
        Route::middleware('admin.role')->prefix('corsi-catalogo/tipologie')->name('corsi-tipologie.')->group(function () {
            Route::get('/', [TipologiaCorsoController::class, 'index'])->name('index');
            Route::get('nuova', [TipologiaCorsoController::class, 'create'])->name('create');
            Route::post('/', [TipologiaCorsoController::class, 'store'])->name('store');
            Route::get('{tipologiaCorso}/modifica', [TipologiaCorsoController::class, 'edit'])->name('edit');
            Route::put('{tipologiaCorso}', [TipologiaCorsoController::class, 'update'])->name('update');
            Route::delete('{tipologiaCorso}', [TipologiaCorsoController::class, 'destroy'])->name('destroy');
        });

        // Niente admin.role: permesso 'corsi' verificato nel controller (come posts/pages).
        Route::prefix('corsi')->name('corsi.')->group(function () {
            Route::get('/', [CorsoController::class, 'index'])->name('index');
            Route::get('nuovo', [CorsoController::class, 'create'])->name('create');
            Route::post('/', [CorsoController::class, 'store'])->name('store');
            Route::get('{corso}/modifica', [CorsoController::class, 'edit'])->name('edit');
            Route::get('{corso}', [CorsoController::class, 'show'])->name('show');
            Route::put('{corso}', [CorsoController::class, 'update'])->name('update');
            Route::delete('{corso}', [CorsoController::class, 'destroy'])->name('destroy');
            Route::put('{corso}/annulla', [CorsoController::class, 'annulla'])->name('annulla');
            Route::put('{corso}/riattiva', [CorsoController::class, 'riattiva'])->name('riattiva');
            Route::put('{corso}/chiudi', [CorsoController::class, 'chiudi'])->name('chiudi');
            Route::put('{corso}/riapri', [CorsoController::class, 'riapri'])->name('riapri');

            Route::prefix('{corso}/iscritti')->name('iscritti.')->group(function () {
                Route::get('/', [CorsoIscrizioneController::class, 'index'])->name('index');
                Route::get('nuovo', [CorsoIscrizioneController::class, 'create'])->name('create');
                Route::get('stampa', [CorsoIscrizioneController::class, 'stampa'])->name('stampa');
                Route::put('{iscrizione}/ritira', [CorsoIscrizioneController::class, 'ritira'])->name('ritira');
                Route::put('{iscrizione}/ripristina', [CorsoIscrizioneController::class, 'ripristina'])->name('ripristina');
                Route::post('/', [CorsoIscrizioneController::class, 'store'])->name('store');
                Route::get('export/{formato}', [CorsoIscrizioneController::class, 'export'])
                    ->whereIn('formato', ['csv', 'xlsx', 'pdf'])->name('export');
            });
        });
    });
});
