<?php

use App\Http\Controllers\Soci\ComunicazioniController;
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
        Route::get('comunicazioni/{comunicazione:slug}', [ComunicazioniController::class, 'show'])->name('comunicazioni.show');
    });
    Route::post('esci', [OtpController::class, 'logout'])->middleware('auth:member')->name('logout');
});
