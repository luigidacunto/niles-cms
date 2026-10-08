<?php

namespace App\Models;

use App\Models\Concerns\IssuesLoginCodes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberLoginCode extends Model
{
    use IssuesLoginCodes;

    public const OWNER_COLUMN = 'member_id';

    protected $fillable = ['member_id', 'code_hash', 'expires_at'];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
