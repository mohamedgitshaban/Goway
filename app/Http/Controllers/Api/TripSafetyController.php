<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use App\Models\TripSafetyLocation;
use App\Models\TripSafetyRecording;
use App\Models\TripSafetyRecordingChunk;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TripSafetyController extends Controller
{
    public function storeLocation(Request $request, Trip $trip): JsonResponse
    {
        $user = $request->user();

        if (! $this->canAccessTrip($trip, $user->id)) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
        }

        if (! $user->safety_location_access) {
            return response()->json(['status' => false, 'message' => 'Safety location access is not enabled for your account'], 403);
        }

        if (! in_array($trip->status, ['driver_arrived', 'in_progress'])) {
            return response()->json(['status' => false, 'message' => 'Trip is not active for live safety tracking'], 400);
        }

        $data = $request->validate([
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'recorded_at' => 'nullable|date',
            'accuracy' => 'nullable|numeric|min:0',
            'speed' => 'nullable|numeric|min:0',
            'heading' => 'nullable|numeric|min:0|max:360',
        ]);

        $location = TripSafetyLocation::create([
            'trip_id' => $trip->id,
            'user_id' => $user->id,
            'lat' => $data['lat'],
            'lng' => $data['lng'],
            'accuracy' => $data['accuracy'] ?? null,
            'speed' => $data['speed'] ?? null,
            'heading' => $data['heading'] ?? null,
            'recorded_at' => isset($data['recorded_at']) ? $data['recorded_at'] : now(),
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Safety location stored',
            'location_id' => $location->id,
        ], 201);
    }

    public function startVoiceSession(Request $request, Trip $trip): JsonResponse
    {
        $user = $request->user();

        if (! $this->canAccessTrip($trip, $user->id)) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
        }

        if (! $user->safety_voice_access) {
            return response()->json(['status' => false, 'message' => 'Safety voice access is not enabled for your account'], 403);
        }

        if (! in_array($trip->status, ['driver_arrived', 'in_progress'])) {
            return response()->json(['status' => false, 'message' => 'Trip is not active for voice safety recording'], 400);
        }

        $data = $request->validate([
            'started_at' => 'nullable|date',
        ]);

        $record = TripSafetyRecording::active()
            ->where('trip_id', $trip->id)
            ->where('user_id', $user->id)
            ->first();

        if (! $record) {
            $record = TripSafetyRecording::create([
                'trip_id' => $trip->id,
                'user_id' => $user->id,
                'file_path' => '',
                'started_at' => $data['started_at'] ?? now(),
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'Voice safety session started',
            'recording_id' => $record->id,
        ], 201);
    }

    public function uploadVoiceChunk(Request $request, Trip $trip): JsonResponse
    {
        $user = $request->user();

        if (! $this->canAccessTrip($trip, $user->id)) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
        }

        if (! $user->safety_voice_access) {
            return response()->json(['status' => false, 'message' => 'Safety voice access is not enabled for your account'], 403);
        }

        if (! in_array($trip->status, ['driver_arrived', 'in_progress'])) {
            return response()->json(['status' => false, 'message' => 'Trip is not active for chunk recording'], 400);
        }

        $data = $request->validate([
            'recording_id' => 'nullable|integer|exists:trip_safety_recordings,id',
            'chunk' => 'required|file|mimes:mp3,wav,m4a,aac,ogg,webm|max:10240',
            'chunk_index' => 'required|integer|min:1',
            'recorded_second' => 'nullable|integer|min:0',
        ]);

        $record = null;
        if (! empty($data['recording_id'])) {
            $record = TripSafetyRecording::active()
                ->where('id', $data['recording_id'])
                ->where('trip_id', $trip->id)
                ->where('user_id', $user->id)
                ->first();
        }

        if (! $record) {
            $record = TripSafetyRecording::active()
                ->where('trip_id', $trip->id)
                ->where('user_id', $user->id)
                ->first();
        }

        if (! $record) {
            $record = TripSafetyRecording::create([
                'trip_id' => $trip->id,
                'user_id' => $user->id,
                'file_path' => '',
                'started_at' => now(),
            ]);
        }

        $file = $request->file('chunk');
        $extension = $file->getClientOriginalExtension() ?: 'webm';
        $fileName = 'chunk_'.$data['chunk_index'].'_'.now()->timestamp.'.'.$extension;
        $path = $file->storeAs('trip-safety/voice/chunks/trip_'.$trip->id.'/recording_'.$record->id, $fileName, 'public');

        $chunk = TripSafetyRecordingChunk::updateOrCreate(
            [
                'trip_safety_recording_id' => $record->id,
                'chunk_index' => $data['chunk_index'],
            ],
            [
                'recorded_second' => $data['recorded_second'] ?? null,
                'file_path' => $path,
                'mime_type' => $file->getClientMimeType(),
                'size_bytes' => $file->getSize(),
            ]
        );

        if ($record->file_path === '') {
            $record->update(['file_path' => $path]);
        }

        return response()->json([
            'status' => true,
            'message' => 'Voice chunk stored',
            'recording_id' => $record->id,
            'chunk_id' => $chunk->id,
            'chunk_index' => $chunk->chunk_index,
        ], 201);
    }

    public function finishVoiceSession(Request $request, Trip $trip, TripSafetyRecording $recording): JsonResponse
    {
        $user = $request->user();

        if (! $this->canAccessTrip($trip, $user->id)) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
        }

        if (! $user->safety_voice_access) {
            return response()->json(['status' => false, 'message' => 'Safety voice access is not enabled for your account'], 403);
        }

        if ($recording->trip_id !== $trip->id || $recording->user_id !== $user->id) {
            return response()->json(['status' => false, 'message' => 'Recording does not belong to this trip/user'], 403);
        }

        $data = $request->validate([
            'ended_at' => 'nullable|date',
            'duration_seconds' => 'nullable|integer|min:1',
        ]);

        $totalSize = (int) $recording->chunks()->sum('size_bytes');

        $recording->update([
            'ended_at' => $data['ended_at'] ?? now(),
            'duration_seconds' => $data['duration_seconds'] ?? null,
            'size_bytes' => $totalSize,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Voice safety session finished',
            'recording_id' => $recording->id,
            'chunks_count' => $recording->chunks()->count(),
            'total_size_bytes' => $totalSize,
        ]);
    }

    // Legacy single-file upload endpoint kept for compatibility.
    public function uploadVoice(Request $request, Trip $trip): JsonResponse
    {
        $user = $request->user();

        if (! $this->canAccessTrip($trip, $user->id)) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
        }

        if (! $user->safety_voice_access) {
            return response()->json(['status' => false, 'message' => 'Safety voice access is not enabled for your account'], 403);
        }

        if (! in_array($trip->status, ['driver_arrived', 'in_progress', 'completed'])) {
            return response()->json(['status' => false, 'message' => 'Trip is not valid for safety recording upload'], 400);
        }

        $data = $request->validate([
            'record' => 'required|file|mimes:mp3,wav,m4a,aac,ogg,webm|max:51200',
            'started_at' => 'nullable|date',
            'ended_at' => 'nullable|date|after_or_equal:started_at',
            'duration_seconds' => 'nullable|integer|min:1',
        ]);

        $path = $request->file('record')->store('trip-safety/voice', 'public');

        $record = TripSafetyRecording::create([
            'trip_id' => $trip->id,
            'user_id' => $user->id,
            'file_path' => $path,
            'mime_type' => $request->file('record')->getClientMimeType(),
            'duration_seconds' => $data['duration_seconds'] ?? null,
            'size_bytes' => $request->file('record')->getSize(),
            'started_at' => $data['started_at'] ?? null,
            'ended_at' => $data['ended_at'] ?? null,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Trip safety voice saved',
            'recording_id' => $record->id,
            'file_path' => $record->file_path,
        ], 201);
    }

    private function canAccessTrip(Trip $trip, int $userId): bool
    {
        return $trip->client_id === $userId || $trip->driver_id === $userId;
    }
}
