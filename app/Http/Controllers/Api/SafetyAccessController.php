<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SafetyAccessController extends Controller
{
    /**
     * GET /safety-access
     * Return the authenticated user's current safety access settings.
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'status' => true,
            'safety_location_access' => $user->safety_location_access,
            'safety_voice_access'    => $user->safety_voice_access,
        ]);
    }

    /**
     * PUT /safety-access
     * Allow the authenticated user to enable/disable their safety features.
     *
     * Body (all optional):
     *   safety_location_access: boolean
     *   safety_voice_access: boolean
     */
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'safety_location_access' => 'sometimes|in:true,false,1,0',
            'safety_voice_access'    => 'sometimes|in:true,false,1,0',
        ]);

        $user->update($data);

        return response()->json([
            'status'  => true,
            'message' => 'Safety access updated successfully',
            'safety_location_access' => (bool) $user->safety_location_access,
            'safety_voice_access'    => (bool) $user->safety_voice_access,
        ]);
    }
}
