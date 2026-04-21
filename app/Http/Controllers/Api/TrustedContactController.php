<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TrustedContact;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TrustedContactController extends Controller
{
    private const MAX_CONTACTS = 3;

    /**
     * GET /trusted-contacts
     * List the authenticated user's trusted contacts.
     */
    public function index(Request $request): JsonResponse
    {
        $contacts = $request->user()->trustedContacts;

        return response()->json([
            'status'   => true,
            'contacts' => $contacts,
        ]);
    }

    /**
     * POST /trusted-contacts
     * Add a new trusted contact (max 3).
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->trustedContacts()->count() >= self::MAX_CONTACTS) {
            return response()->json([
                'status'  => false,
                'message' => 'You can only have up to ' . self::MAX_CONTACTS . ' trusted contacts.',
            ], 422);
        }

        $data = $request->validate([
            'name'  => 'required|string|max:255',
            'phone' => 'required|string|max:30',
        ]);

        $contact = $user->trustedContacts()->create($data);

        return response()->json([
            'status'  => true,
            'message' => 'Trusted contact added successfully',
            'contact' => $contact,
        ], 201);
    }

    /**
     * PUT /trusted-contacts/{id}
     * Update a trusted contact.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $contact = $user->trustedContacts()->find($id);

        if (! $contact) {
            return response()->json(['status' => false, 'message' => 'Trusted contact not found'], 404);
        }

        $data = $request->validate([
            'name'  => 'sometimes|required|string|max:255',
            'phone' => 'sometimes|required|string|max:30',
        ]);

        $contact->update($data);

        return response()->json([
            'status'  => true,
            'message' => 'Trusted contact updated successfully',
            'contact' => $contact,
        ]);
    }

    /**
     * DELETE /trusted-contacts/{id}
     * Delete a trusted contact.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $contact = $user->trustedContacts()->find($id);

        if (! $contact) {
            return response()->json(['status' => false, 'message' => 'Trusted contact not found'], 404);
        }

        $contact->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Trusted contact deleted successfully',
        ]);
    }
}
