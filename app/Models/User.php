<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Filament\Panel;
use App\Models\Card;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Jetstream\HasProfilePhoto;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens;
    use HasFactory;
    use HasProfilePhoto;
    use Notifiable;
    use TwoFactorAuthenticatable;

    const ROLE_SUPERADMIN = 'SuperAdmin';
    const ROLE_SUPERVISOR = 'SuperVisor';
    const ROLE_ADMIN = 'Admin';
    const ROLE_USER = 'User';

    const ROLE_DEFAULT = self::ROLE_SUPERADMIN;

    const ROLES = [
        self::ROLE_SUPERADMIN => 'SuperAdmin',
        self::ROLE_SUPERVISOR => 'SuperVisor',
        self::ROLE_ADMIN => 'Admin',
        self::ROLE_USER => 'User'
    ];

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->isAdmin() || $this->isSuperAdmin() || $this->isSuperVisor();
    }

    public function isSuperAdmin()
    {
        return $this->role === self::ROLE_SUPERADMIN;
    }

    public function isSuperVisor()
    {
        return $this->role === self::ROLE_SUPERVISOR;
    }

    public function isAdmin()
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function cards()
    {
        return $this->hasMany(Card::class);
    }

    public function members()
    {
        return $this->hasMany(Member::class, 'admin_id');
    }

    public function load_money()
    {
        return $this->hasMany(LoadMoney::class);
    }

    public function card()
    {
        return $this->hasMany(Card::class);
    }

    public function teams()
{
    return $this->belongsToMany(Team::class, 'team_admins', 'admin_id', 'team_id');
}


    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'balance'
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    protected $appends = [
        'profile_photo_url',
    ];


    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
