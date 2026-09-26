<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'nip',
        'email',
        'password',
        'role',
        'jabatan',
        'unit_organisasi_id',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'unit_organisasi_id' => 'integer',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return (bool) $this->is_active;
    }

    public function unitOrganisasi(): BelongsTo
    {
        return $this->belongsTo(UnitOrganisasi::class, 'unit_organisasi_id');
    }

    public function laporanDetails(): HasMany
    {
        return $this->hasMany(LaporanDetail::class, 'user_id');
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'superadmin' || $this->hasRole('superadmin');
    }

    public function isAdminKecamatan(): bool
    {
        return $this->role === 'admin_kecamatan' || $this->hasRole('admin_kecamatan');
    }

    public function isKasi(): bool
    {
        return $this->role === 'kasi' || $this->hasRole('kasi');
    }

    public function isCamat(): bool
    {
        return $this->role === 'camat' || $this->hasRole('camat');
    }

    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }
}
