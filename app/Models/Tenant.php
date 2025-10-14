<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

class Tenant extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'display_name',
        'api_key',
        'plan_type',
        'is_active',
        'settings',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'api_key',
    ];

    /**
     * Get the api_key attribute (decrypt it)
     */
    public function getApiKeyAttribute($value): string
    {
        return $value ? Crypt::decryptString($value) : '';
    }

    /**
     * Set the api_key attribute (encrypt it)
     */
    public function setApiKeyAttribute($value): void
    {
        $this->attributes['api_key'] = $value ? Crypt::encryptString($value) : null;
    }

    /**
     * Get the users for this tenant
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Scope to get only active tenants
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Check if tenant is on a specific plan
     */
    public function isOnPlan(string $plan): bool
    {
        return strtolower($this->plan_type) === strtolower($plan);
    }

    /**
     * Check if tenant is on Pro or higher plan
     */
    public function isProOrHigher(): bool
    {
        return in_array(strtolower($this->plan_type), ['pro', 'enterprise']);
    }
}
