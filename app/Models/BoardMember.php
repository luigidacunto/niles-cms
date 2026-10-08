<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class BoardMember extends Model
{
    /** Slot con ruolo fisso (uno ciascuno) + blocco libero ripetibile. */
    public const SLOT_PRESIDENT = 'president';
    public const SLOT_VICE = 'vice_president';
    public const SLOT_MEMBER = 'member';

    public const FIXED_SLOTS = [
        self::SLOT_PRESIDENT => 'Presidente',
        self::SLOT_VICE => 'Vice Presidente',
    ];

    protected $fillable = ['slot', 'role_label', 'name', 'bio', 'photo_path', 'order'];

    /** Presidente, poi vice, poi i blocchi liberi nell'ordine di inserimento. */
    public function scopeOrdered(Builder $query): void
    {
        // CASE portabile (niente FIELD(): MySQL-only, i test girano su SQLite).
        $query->orderByRaw("CASE slot WHEN 'president' THEN 0 WHEN 'vice_president' THEN 1 ELSE 2 END")
            ->orderBy('order')->orderBy('id');
    }

    /** Etichetta ruolo da mostrare: fissa per gli slot noti, libera per i blocchi `member`. */
    protected function roleName(): Attribute
    {
        return Attribute::make(
            get: fn () => self::FIXED_SLOTS[$this->slot] ?? ($this->role_label ?: 'Consigliere'),
        );
    }

    protected function photoUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->photo_path
                ? Storage::url($this->photo_path)
                : asset('images/board-photo-placeholder.svg'),
        );
    }
}
