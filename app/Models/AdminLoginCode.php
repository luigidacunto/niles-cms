<?php

namespace App\Models;

use App\Models\Concerns\IssuesLoginCodes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminLoginCode extends Model
{
    use IssuesLoginCodes;

    public const OWNER_COLUMN = 'admin_id';

    protected $fillable = ['admin_id', 'code_hash', 'expires_at'];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
        ];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }
}
