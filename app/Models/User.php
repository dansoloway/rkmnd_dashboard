<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'password',
        'role',
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
        ];
    }

    /**
     * Get the tenant that owns the user
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public const ROLE_USER = 'user';

    public const ROLE_ADMIN = 'admin';

    public const ROLE_SUPERADMIN = 'superadmin';

    /** Can view Analytics (queries/results) only — not library, playground, vocab, etc. */
    public const ROLE_ANALYTICS = 'analytics';

    /**
     * @return list<string>
     */
    public static function roles(): array
    {
        return [
            self::ROLE_USER,
            self::ROLE_ADMIN,
            self::ROLE_SUPERADMIN,
            self::ROLE_ANALYTICS,
        ];
    }

    /**
     * Check if user is an admin
     */
    public function isAdmin(): bool
    {
        return in_array($this->role, [self::ROLE_ADMIN, self::ROLE_SUPERADMIN], true);
    }

    /**
     * Check if user is a superadmin
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPERADMIN;
    }

    /**
     * Analytics-only: queries and results, account settings — nothing else.
     */
    public function isAnalyticsOnly(): bool
    {
        return $this->role === self::ROLE_ANALYTICS;
    }

    /**
     * Full dashboard (library, playground, vocab, platform tools, etc.).
     */
    public function hasFullAccess(): bool
    {
        return ! $this->isAnalyticsOnly();
    }

    /**
     * Roles this user may assign when managing other users.
     *
     * @return list<string>
     */
    public function assignableRoles(): array
    {
        $roles = [
            self::ROLE_USER,
            self::ROLE_ADMIN,
            self::ROLE_ANALYTICS,
        ];

        if ($this->isSuperAdmin()) {
            $roles[] = self::ROLE_SUPERADMIN;
        }

        return $roles;
    }

    public static function roleLabel(string $role): string
    {
        return match ($role) {
            self::ROLE_ADMIN => 'Admin',
            self::ROLE_SUPERADMIN => 'Superadmin',
            self::ROLE_ANALYTICS => 'Analytics only',
            default => 'User',
        };
    }
}
