<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'position',
        'department',
        'level',
        'skills',
        'avatar_color',
        'archived_at',
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
            'skills' => 'array',
            'archived_at' => 'datetime',
        ];
    }

    public function workload(): HasOne
    {
        return $this->hasOne(TeamWorkload::class);
    }

    public function teamAssignments(): HasMany
    {
        return $this->hasMany(TeamAssignment::class);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if (request()?->is('admin/logout')) {
            return true;
        }

        if ($this->archived_at !== null) {
            return false;
        }

        return $this->hasAnyRole(['admin', 'super_admin', 'developer']);
    }
}
