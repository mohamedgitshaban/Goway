<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    protected $fillable = [
        'type',
        'trip_id',
        'user_id',
        'admin_id',
        'status',
        'closed_at',
    ];

    protected $casts = [
        'closed_at' => 'datetime',
    ];

    // ─── Types ───────────────────────────────────────────────────
    public const TYPE_SUPPORT      = 'support';
    public const TYPE_TRIP_SUPPORT = 'trip_support';
    public const TYPE_TRIP_CHAT    = 'trip_chat';

    // ─── Relations ───────────────────────────────────────────────

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class)->orderBy('created_at');
    }

    public function latestMessage()
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    // ─── Scopes ──────────────────────────────────────────────────

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeSupport($query)
    {
        return $query->whereIn('type', [self::TYPE_SUPPORT, self::TYPE_TRIP_SUPPORT]);
    }

    public function scopeTripChat($query)
    {
        return $query->where('type', self::TYPE_TRIP_CHAT);
    }

    // ─── Helpers ─────────────────────────────────────────────────

    public function isSupport(): bool
    {
        return in_array($this->type, [self::TYPE_SUPPORT, self::TYPE_TRIP_SUPPORT]);
    }

    public function isTripChat(): bool
    {
        return $this->type === self::TYPE_TRIP_CHAT;
    }

    public function close(): void
    {
        $this->update([
            'status'    => 'closed',
            'closed_at' => now(),
        ]);
    }
}
