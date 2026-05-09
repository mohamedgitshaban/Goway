<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WithdrawRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'amount',
        'status',
        'payment_method',
        'account_name',
        'account_number',
        'bank_name',
        'iban',
        'driver_payout_account_id',
        'admin_id',
        'admin_note',
        'processed_at',
        'payout_reference',
    ];

    /**
     * Sensitive financial fields are encrypted at rest.
     */
    protected $casts = [
        'amount'       => 'float',
        'processed_at' => 'datetime',
        'account_name'   => 'encrypted',
        'account_number' => 'encrypted',
        'bank_name'      => 'encrypted',
        'iban'           => 'encrypted',
    ];

    // ─── Relations ────────────────────────────────────────────────────────────

    public function user()
    {
        return $this->belongsTo(Driver::class, 'user_id');
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function payoutAccount()
    {
        return $this->belongsTo(DriverPayoutAccount::class, 'driver_payout_account_id');
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }
}
