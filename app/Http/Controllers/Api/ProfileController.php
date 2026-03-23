<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function me(Request $request)
    {
        $user = $request->user();

        // Load related data depending on usertype
        $relations = ['wallets'];
        if ($user->isDriver()) {
            $relations[] = 'vehicles';
        }

        $user->load($relations);

        return response()->json($user);
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
