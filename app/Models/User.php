<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use SoftDeletes;
    use HasApiTokens, HasFactory, Notifiable;

    // Role/user types
    public const ROLE_ADMIN = 'admin';
    public const ROLE_DRIVER = 'driver';
    public const ROLE_CLIENT = 'client';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'name',
        'email',
        'phone',
        'password',
        'usertype',
        'status',
        'personal_image',
        'is_online',
        'is_idle',
        'is_phone_verified',
        'fcm_token',
        'safety_location_access',
        'safety_voice_access',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_idle' => 'boolean',
        'safety_location_access' => 'boolean',
        'safety_voice_access' => 'boolean',
    ];
    protected static function booted()
    {

        static::created(function ($user) {
            $user->wallet()->create(['balance' => 0]);
        });
    }
    public function isAdmin(): bool
    {
        return $this->usertype === self::ROLE_ADMIN;
    }
    public function isDriver(): bool
    {
        return $this->usertype === self::ROLE_DRIVER;
    }

    public function isClient(): bool
    {
        return $this->usertype === self::ROLE_CLIENT;
    }
    public function wallet()
    {
        return $this->hasOne(Wallet::class , 'user_id');
    }

    public function getWalletBalanceAttribute()
    {
        return $this->wallet ? $this->wallet->balance : 0;
    }

    public function trustedContacts()
    {
        return $this->hasMany(TrustedContact::class, 'user_id');
    }

    /**
     * Admin permissions relationship (only for users with usertype === admin)
     */
    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'admin_permissions', 'admin_id', 'permission_id')
            ->withPivot('can_edit')
            ->withTimestamps();
    }

    /**
     * Role relation (nullable)
     */
    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    /**
     * Check if admin has a permission by name.
     */
    public function hasPermission(string $name): bool
    {
        if (! $this->isAdmin()) {
            return false;
        }

        // eager-loaded check
        if ($this->relationLoaded('permissions')) {
            if ($this->permissions->contains('name', $name)) {
                return true;
            }
            // check role permissions if role relation is loaded
            if ($this->relationLoaded('role') && $this->role && $this->role->relationLoaded('permissions')) {
                return $this->role->permissions->contains('name', $name);
            }
            return false;
        }
        // check direct admin permissions first
        if ($this->permissions()->where('name', $name)->exists()) {
            return true;
        }

        // check role permissions
        if ($this->role()->exists()) {
            return $this->role->permissions()->where('name', $name)->exists();
        }

        return false;
    }

    /**
     * Sync permissions by ids
     */
    public function syncPermissions(array $permissionIds)
    {
        return $this->permissions()->sync($permissionIds);
    }
}
