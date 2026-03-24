<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\DriverDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class DriverDocumentController extends Controller
{
    /**
     * Driver uploads all documents in one request
     */
    public function uploadDocuments(Request $request)
    {
        $user = auth()->user();

        if (! $user || ! $user->isDriver()) {
            return response()->json(['message' => 'Driver not found'], 404);
        }

        // Validate request
        $validator = $this->validateRequest($request);
        if ($validator instanceof \Illuminate\Http\JsonResponse) {
            return $validator; // return validation errors
        }

        $data = $validator;

        // Get existing document record
        $existing = $user->driverDocument;

        // Build new document data (upload + delete old)
        $documentData = $this->buildDocumentData($request, $existing, $data['age']);

        // Create or update the single record
        $user->driverDocument()->updateOrCreate(
            ['user_id' => $user->id],
            $documentData
        );
        $user->load('driverDocument');
        // Update user status
        $user->status = 'inreview';
        $user->save();

        return response()->json([
            'message' => 'Documents submitted successfully and are under review',
            'document' => $user->driverDocument,
        ]);
    }

    /* ---------------------------------------------------------
     *  VALIDATION
     * --------------------------------------------------------- */
    private function validateRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'age' => 'required|integer|min:10|max:80',

            'nid_front' => 'nullable|mimes:jpg,jpeg,png,pdf',
            'nid_back'  => 'nullable|mimes:jpg,jpeg,png,pdf',
            'birth_front'  => 'nullable|mimes:jpg,jpeg,png,pdf',

            'parent_nid_front' => 'nullable|mimes:jpg,jpeg,png,pdf',
            'parent_nid_back'  => 'nullable|mimes:jpg,jpeg,png,pdf',

            'license_image' => 'nullable|mimes:jpg,jpeg,png,pdf',
            'criminal_record' => 'required|mimes:jpg,jpeg,png,pdf',
            'trip_type_id' => 'required|exists:trip_types,id',
        ]);

        $validator->after(function ($validator) use ($request) {

            $age = (int) $request->age;

            // If age >= 18 → driver NID required
            if ($age >= 18) {
                if (!$request->hasFile('nid_front')) {
                    $validator->errors()->add('nid_front', 'Driver NID front is required for age 18 or above.');
                }
                if (!$request->hasFile('nid_back')) {
                    $validator->errors()->add('nid_back', 'Driver NID back is required for age 18 or above.');
                }
            }

            // If age < 18 → parent NID required
            if ($age < 18) {
                if (!$request->hasFile('birth_front')) {
                    $validator->errors()->add('birth_front', 'Birth certificate front is required for drivers under 18.');
                }
                if (!$request->hasFile('parent_nid_front')) {
                    $validator->errors()->add('parent_nid_front', 'Parent NID front is required for drivers under 18.');
                }
                if (!$request->hasFile('parent_nid_back')) {
                    $validator->errors()->add('parent_nid_back', 'Parent NID back is required for drivers under 18.');
                }
            }
        });

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        return $validator->validated();
    }


    /* ---------------------------------------------------------
     *  FILE UPLOAD + DELETE HELPERS
     * --------------------------------------------------------- */
    private function uploadFile($file)
    {
        $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $file->storeAs('driver_documents', $filename, 'public');
        return asset('storage/driver_documents/' . $filename);
    }

    private function deleteOldFile($path)
    {
        if (!$path) return;

        $relative = str_replace(asset('storage/') . '/', '', $path);
        Storage::disk('public')->delete($relative);
    }

    /* ---------------------------------------------------------
     *  BUILD DOCUMENT DATA (UPLOAD + DELETE OLD)
     * --------------------------------------------------------- */
    private function buildDocumentData(Request $request, $existing, $age)
    {
        $fields = [
            'nid_front',
            'nid_back',
            'parent_nid_front',
            'parent_nid_back',
            'license_image',
            'criminal_record',
        ];

        $data = [
            'age' => $age,
            'status' => 'inreview',
        ];

        foreach ($fields as $field) {
            if ($request->hasFile($field)) {

                // Delete old file if exists
                if ($existing && $existing->$field) {
                    $this->deleteOldFile($existing->$field);
                }

                // Upload new file
                $data[$field] = $this->uploadFile($request->file($field));
            }
        }

        return $data;
    }

    /* ---------------------------------------------------------
     *  ADMIN ACTIONS
     * --------------------------------------------------------- */
    public function accept($id)
    {
        $doc = DriverDocument::where('id', $id)->first();

        if (! $doc) {
            return response()->json(['message' => 'Document not found'], 404);
        }

        $doc->update([
            'status' => 'accepted',
            'reject_reason' => null,
        ]);

        $doc->user->update(['status' => 'active']);

        return response()->json(['message' => 'Documents accepted']);
    }

    public function reject(Request $request, $id)
    {
        $doc = DriverDocument::where('id', $id)->first();

        if (! $doc) {
            return response()->json(['message' => 'Document not found'], 404);
        }

        $data = $request->validate([
            'reason' => 'required|string',
        ]);

        $doc->update([
            'status' => 'rejected',
            'reject_reason' => $data['reason'],
        ]);

        $doc->user->update(['status' => 'rejected_document']);

        return response()->json(['message' => 'Documents rejected']);
    }

    /* ---------------------------------------------------------
     *  SHOW DOCUMENT
     * --------------------------------------------------------- */
    public function show($id)
    {
        $doc = DriverDocument::where('id', $id)->first();

        if (! $doc) {
            return response()->json(['message' => 'Document not found'], 404);
        }

        return response()->json($doc);
    }
}
