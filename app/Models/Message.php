<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = [
        'conversation_id',
        'sender_id',
        'body',
        'attachment',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    // ─── Relations ───────────────────────────────────────────────

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    // ─── Scopes ──────────────────────────────────────────────────

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    // ─── Helpers ─────────────────────────────────────────────────

    public function markAsRead(): void
    {
        if (!$this->read_at) {
            $this->update(['read_at' => now()]);
        }
    }
}
