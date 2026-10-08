<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Membro del comitato (volontario, volontario in estensione, dipendente). Anagrafica alimentata dagli
 * import Excel + inserimenti manuali; in futuro anche account area soci (login OTP via email, guard `member`).
 * Chiave naturale: codice_fiscale.
 */
class Member extends Authenticatable
{
    use Notifiable, SoftDeletes;

    public const RUOLO_VOLONTARIO = 'volontario';
    public const RUOLO_ESTENSIONE = 'volontario_estensione';
    public const RUOLO_DIPENDENTE = 'dipendente';

    public const RUOLI = [
        self::RUOLO_VOLONTARIO => 'Volontario',
        self::RUOLO_ESTENSIONE => 'Volontario in estensione',
        self::RUOLO_DIPENDENTE => 'Dipendente',
    ];

    protected $fillable = [
        'codice_fiscale', 'nome', 'cognome', 'data_nascita', 'email', 'telefono', 'telefoni_aggiuntivi', 'ruolo', 'disabilitato',
    ];

    // richiesta_disattivazione_at NON è fillable: la imposta solo il socio (ProfiloController) o la azzera l'admin.

    protected function casts(): array
    {
        return [
            'data_nascita' => 'date',
            'telefoni_aggiuntivi' => 'array',
            'disabilitato' => 'boolean',
            'richiesta_disattivazione_at' => 'datetime',
        ];
    }

    public function ruoloLabel(): string
    {
        return self::RUOLI[$this->ruolo] ?? $this->ruolo;
    }

    public function nomeCompleto(): string
    {
        return "{$this->cognome} {$this->nome}";
    }

    /** Soft-delete + disabilitazione in un colpo (la rimozione implica disabilitato). */
    public function rimuovi(): void
    {
        $this->disabilitato = true;
        $this->save();
        $this->delete();
    }

    public function ripristina(): void
    {
        $this->disabilitato = false;
        $this->restore();
    }
}
