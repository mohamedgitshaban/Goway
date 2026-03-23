<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    use HasFactory;

    
    protected $fillable = ['user_id', 'balance'];

    public function scopeFilter($query, $filters)
{
    return $query
        ->when($filters['wallet_id'] ?? null, fn($q, $v) => $q->where('id', $v))
        ->when($filters['balance'] ?? null, fn($q, $v) => $q->where('balance', $v))
        ->when($filters['user_name'] ?? null, function ($q, $v) {
            $q->whereHas('user', fn($u) => $u->where('name', 'like', "%$v%"));
        });
}
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
