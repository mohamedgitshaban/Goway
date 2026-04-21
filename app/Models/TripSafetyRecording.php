<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TripSafetyRecording extends Model
{
    protected $fillable = [
        'trip_id',
        'user_id',
        'file_path',
        'mime_type',
        'duration_seconds',
        'size_bytes',
        'started_at',
        'ended_at',
    ];

    protected $casts = [
        'duration_seconds' => 'integer',
        'size_bytes' => 'integer',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function chunks()
    {
        return $this->hasMany(TripSafetyRecordingChunk::class, 'trip_safety_recording_id')
            ->orderBy('chunk_index');
    }

    public function scopeActive($query)
    {
        return $query->whereNull('ended_at');
    }
}
