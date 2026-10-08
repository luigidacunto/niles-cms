<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Admin extends Authenticatable
{
    use Notifiable;

    /** Sezioni con un solo permesso (lettura+scrittura insieme): salvato sempre come ['read','write']. */
    public const COMBINED_PERMISSIONS = ['membri', 'comunicazioni_soci'];

    protected $fillable = [
        'name', 'email', 'password', 'role', 'active', 'password_login_enabled',
        'permissions', 'all_categories',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'active' => 'boolean',
            'password_login_enabled' => 'boolean',
            'permissions' => 'array',
            'all_categories' => 'boolean',
            'protected' => 'boolean',
        ];
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    /**
     * role='admin' salta incondizionatamente ogni controllo qui sotto. Si chiama hasPermission() e non can()
     * per non oscurare Authorizable::can() (il proxy di Gate/Policy che si aspettano gli helper di Laravel).
     */
    public function hasPermission(string $resource, string $action = 'write'): bool
    {
        if ($this->role === 'admin') {
            return true;
        }

        return in_array($action, $this->permissions[$resource] ?? [], true);
    }

    public function canManageCategory(?Category $category): bool
    {
        if ($this->role === 'admin' || $this->all_categories) {
            return true;
        }

        if (! $category) {
            return false;
        }

        return $this->categories()->whereKey($category->id)->exists();
    }
}
