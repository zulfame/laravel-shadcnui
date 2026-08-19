<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, HasRoles, Notifiable, SoftDeletes;

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class)->latest('id');
    }

    protected $fillable = [
        'name',
        'username',
        'email',
        'phone',
        'office',
        'alias',
        'mso_code',
        'collector_code',
        'avatar',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Setel peranan pengguna. Kolom `role` adalah cerminan (denormalisasi) dari
     * peranan spatie agar bisa ditampilkan, diurutkan, dan diekspor langsung.
     */
    public function setRoleName(string $name): void
    {
        $this->syncRoles([$name]);
        $this->forceFill(['role' => $name])->saveQuietly();
    }

    /** Pengguna terarsip = soft deleted (tidak dapat masuk ke aplikasi). */
    public function isArchived(): bool
    {
        return $this->trashed();
    }
}
