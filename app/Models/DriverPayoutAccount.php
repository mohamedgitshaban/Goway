<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DriverPayoutAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'payment_method',
        'account_name',
        'account_number',
        'bank_name',
        'iban',
    ];

    /**
     * Sensitive fields are transparently encrypted at rest using
     * Laravel's built-in Crypt facade via the 'encrypted' cast.
     */
    protected $casts = [
        'account_name'   => 'encrypted',
        'account_number' => 'encrypted',
        'bank_name'      => 'encrypted',
        'iban'           => 'encrypted',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function withdrawRequests()
    {
        return $this->hasMany(WithdrawRequest::class);
    }
}
