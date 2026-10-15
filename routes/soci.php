<?php

use App\Http\Controllers\Soci\ComunicazioniController;
use App\Http\Controllers\Soci\DocumentiController;
use App\Http\Controllers\Soci\OtpController;
use App\Http\Controllers\Soci\ProfiloController;
use Illuminate\Support\Facades\Route;

// Area soci. Come admin.php: va richiesta in cima a web.php, prima delle route pubbliche dinamiche
// (/{category}/{post} catturerebbe /soci/accedi).
Route::prefix('soci')->name('soci.')->middleware('area.soci')->group(function () {
    Route::middleware('guest:member')->group(function () {
        Route::get('accedi', [OtpController::class, 'showRequest'])->name('login');
        Route::post('accedi', [OtpController::class, 'sendCode'])->middleware('throttle:5,1')->name('login.send');
        Route::get('accedi/verifica', [OtpController::class, 'showVerify'])->name('login.verify');
        Route::post('accedi/verifica', [OtpController::class, 'confirmCode'])->middleware('throttle:10,1')->name('login.confirm');
    });

    Route::get('profilo', [ProfiloController::class, 'show'])->middleware('auth:member')->name('profilo');
    Route::post('profilo/disattivazione', [ProfiloController::class, 'richiediDisattivazione'])->middleware('auth:member')->name('profilo.disattivazione');
    Route::middleware('auth:member')->group(function () {
        Route::get('comunicazioni', [ComunicazioniController::class, 'index'])->name('comunicazioni');
        Route::get('comunicazioni/immagini/{immagine}', [ComunicazioniController::class, 'immagine'])->whereNumber('immagine')->name('comunicazioni.immagine');
        Route::get('comunicazioni/{comunicazione:slug}', [ComunicazioniController::class, 'show'])->name('comunicazioni.show');
        Route::get('comunicazioni/{comunicazione:slug}/copertina', [ComunicazioniController::class, 'copertina'])->name('comunicazioni.copertina');

        Route::get('documenti', [DocumentiController::class, 'index'])->name('documenti');
        Route::get('documenti/{document}', [DocumentiController::class, 'anteprima'])->whereNumber('document')->name('documenti.anteprima');
        Route::get('documenti/{document}/file', [DocumentiController::class, 'file'])->whereNumber('document')->name('documenti.file');
        Route::get('documenti/{document}/scarica', [DocumentiController::class, 'scarica'])->whereNumber('document')->name('documenti.scarica');
    });
    Route::post('esci', [OtpController::class, 'logout'])->middleware('auth:member')->name('logout');
});
