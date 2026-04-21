<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TripSafetyRecordingChunk extends Model
{
    protected $fillable = [
        'trip_safety_recording_id',
        'chunk_index',
        'recorded_second',
        'file_path',
        'mime_type',
        'size_bytes',
    ];

    protected $casts = [
        'chunk_index' => 'integer',
        'recorded_second' => 'integer',
        'size_bytes' => 'integer',
    ];

    public function recording()
    {
        return $this->belongsTo(TripSafetyRecording::class, 'trip_safety_recording_id');
    }
}
