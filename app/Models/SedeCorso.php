<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SedeCorso extends Model
{
    protected $table = 'sedi_corso';

    protected $fillable = ['nome', 'via', 'comune', 'provincia', 'cap', 'attivo', 'order'];

    protected function casts(): array
    {
        return ['attivo' => 'boolean'];
    }

    public function corsi(): HasMany
    {
        return $this->hasMany(Corso::class);
    }
}
