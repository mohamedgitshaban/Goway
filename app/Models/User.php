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
        // driver document paths
        'nid_front',
        'nid_back',
        'license_image',
        'criminal_record',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'password_hash',
        // hide sensitive documents
        'nid_front',
        'nid_back',
        'license_image',
        'personal_image',
        'criminal_record',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
    ];

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

}
