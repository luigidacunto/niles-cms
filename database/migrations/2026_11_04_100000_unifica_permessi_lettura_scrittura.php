<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Le sezioni con permesso unico (Admin::COMBINED_PERMISSIONS) non hanno più lettura e scrittura separate:
 * chi aveva anche solo la lettura passa a lettura+scrittura. Solo «corsi» resta separato.
 * Elenco copiato qui di proposito: la migrazione deve restare valida anche se la costante cambia in futuro.
 */
return new class extends Migration
{
    private const SEZIONI = ['posts', 'pages', 'trasparenza', 'documents', 'images', 'files', 'membri', 'comunicazioni_soci'];

    public function up(): void
    {
        foreach (DB::table('admins')->whereNotNull('permissions')->get(['id', 'permissions']) as $admin) {
            $permessi = json_decode($admin->permissions, true);
            if (! is_array($permessi)) {
                continue;
            }

            $nuovi = $permessi;
            foreach (self::SEZIONI as $sezione) {
                if (! empty($permessi[$sezione])) {
                    $nuovi[$sezione] = ['read', 'write'];
                }
            }

            if ($nuovi !== $permessi) {
                DB::table('admins')->where('id', $admin->id)->update(['permissions' => json_encode($nuovi)]);
            }
        }
    }

    public function down(): void
    {
        // I permessi di sola lettura non sono ricostruibili: nessuna azione.
    }
};
