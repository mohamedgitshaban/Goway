<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\ProfileResource;

class ProfileController extends Controller
{
    public function me(Request $request)
    {
        $user = $request->user();

        $relations = ['wallet'];
        if ($user->isDriver()) {
            $relations[] = 'vehicles';
        }

        $user->load($relations);

        return new ProfileResource($user);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => 'nullable|string|max:191',
            'name_en' => 'nullable|string|max:191',
            'name_ar' => 'nullable|string|max:191',
            'phone' => 'nullable|string|max:30',
        ]);

        $user->update($data);

        // reload relations
        $user->load(['wallets', 'vehicles']);

        return response()->json($user);
    }
}
