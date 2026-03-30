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
        'is_online'
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
}
