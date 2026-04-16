<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DriverDocumentResource;
use App\Models\Driver;
use App\Models\DriverDocument;
use App\Models\Trip;
use App\Models\Vehicle;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class DriverDocumentController extends Controller
{
    public function index(Request $request)
    {
        $limit   = $request->input('limit', 10);
        $search  = $request->input('search');
        $status  = $request->input('status');
        $sortBy  = $request->input('sort_by', 'id');
        $sortDir = $request->input('sort_dir', 'asc');

        $query = DriverDocument::with(['driver', 'tripType']);

        /* -----------------------------------------
     * SEARCH (driver name, phone, doc id, trip type)
     * ----------------------------------------- */
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('id', $search)
                    ->orWhere('age', $search)
                    ->orWhereHas('driver', function ($u) use ($search) {
                        $u->where('name', 'LIKE', "%{$search}%")
                            ->orWhere('phone', 'LIKE', "%{$search}%");
                    })
                    ->orWhereHas('tripType', function ($t) use ($search) {
                        $t->where('name', 'LIKE', "%{$search}%");
                    });
            });
        }

        /* -----------------------------------------
     * FILTER BY STATUS
     * ----------------------------------------- */
        if ($status) {
            $query->where('status', $status);
        }

        /* -----------------------------------------
     * SORTING
     * ----------------------------------------- */
        if ($sortBy === 'trip_type') {
            $query->join('trip_types', 'driver_documents.trip_type_id', '=', 'trip_types.id')
                ->orderBy('trip_types.name', $sortDir)
                ->select('driver_documents.*');
        } elseif (in_array($sortBy, ['id', 'age', 'status'])) {
            $query->orderBy($sortBy, $sortDir);
        }

        /* -----------------------------------------
     * EXPORT (CSV or XLSX)
     * ----------------------------------------- */
        if ($request->has('export')) {
            $format = $request->input('export', 'xlsx'); // xlsx or csv

            if ($format === 'xlsx') {
                return \Maatwebsite\Excel\Facades\Excel::download(
                    new \App\Exports\DriverDocumentsExport($query),
                    'driver_documents.xlsx'
                );
            }

            // CSV export
            $fileName = 'driver_documents.csv';

            $response = new \Symfony\Component\HttpFoundation\StreamedResponse(function () use ($query) {
                $handle = fopen('php://output', 'w');

                fputcsv($handle, [
                    'ID',
                    'Driver Name',
                    'Phone',
                    'Age',
                    'Trip Type',
                    'Status'
                ]);

                $query->chunk(200, function ($docs) use ($handle) {
                    foreach ($docs as $doc) {
                        fputcsv($handle, [
                            $doc->id,
                            $doc->driver->name,
                            $doc->driver->phone,
                            $doc->age,
                            $doc->tripType?->name,
                            $doc->status,
                        ]);
                    }
                });

                fclose($handle);
            });

            $response->headers->set('Content-Type', 'text/csv');
            $response->headers->set('Content-Disposition', "attachment; filename=\"$fileName\"");

            return $response;
        }

        /* -----------------------------------------
     * NORMAL JSON RESPONSE
     * ----------------------------------------- */
        $data = $query->paginate($limit);

        return DriverDocumentResource::collection($data);
    }

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
        $existingVehicle = Vehicle::where('driver_id', $user->id)->first();

        // Build new document data (upload + delete old)
        $documentData = $this->buildDocumentData($request, $existing, $data['age']);
        $vehicleData = $this->buildVehicleData($request, $existingVehicle);

        // Create or update the single record
        $user->driverDocument()->updateOrCreate(
            ['user_id' => $user->id],
            $documentData
        );

        // Create or update vehicle data
        Vehicle::updateOrCreate(
            ['driver_id' => $user->id],
            array_merge($vehicleData, [
                'trip_type_id' => $request->trip_type_id,
                'vehicle_brand_id' => $request->vehicle_brand_id,
                'vehicle_model_id' => $request->vehicle_model_id,
            ])
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
        $existingDocument = optional(auth()->user())->driverDocument;
        $existingVehicle = Vehicle::where('driver_id', optional(auth()->user())->id)->first();

        $validator = Validator::make($request->all(), [
            'age' => 'required|integer|min:10|max:80',
            'birth_date' => 'required|date',
            'nid_front' => $this->fileOrPathRule($request, 'nid_front'),
            'nid_back'  => $this->fileOrPathRule($request, 'nid_back'),
            'birth_front'  => $this->fileOrPathRule($request, 'birth_front'),
            'parent_nid_front' => $this->fileOrPathRule($request, 'parent_nid_front'),
            'parent_nid_back'  => $this->fileOrPathRule($request, 'parent_nid_back'),
            'license_image' => $this->fileOrPathRule($request, 'license_image'),
            'criminal_record' => $this->fileOrPathRule($request, 'criminal_record'),
            'trip_type_id' => 'required|exists:trip_types,id',
            'vehicle_brand_id' => 'required|integer|exists:vehicle_brands,id',
            'vehicle_model_id' => 'required|integer|exists:vehicle_models,id',
            'color' => 'required|string',
            'year' => 'required|integer|min:1900|max:' . date('Y'),
            'plate_number' => 'required|string',
            'vehicle_license_image' => $this->fileOrPathRule($request, 'vehicle_license_image'),
            'car_front_image' => $this->fileOrPathRule($request, 'car_front_image'),
            'car_back_image' => $this->fileOrPathRule($request, 'car_back_image'),
            'car_left_image' => $this->fileOrPathRule($request, 'car_left_image'),
            'car_right_image' => $this->fileOrPathRule($request, 'car_right_image'),
        ]);

        $validator->after(function ($validator) use ($request, $existingDocument, $existingVehicle) {

            $age = (int) $request->age;

            // If age >= 18 → driver NID required
            if ($age >= 18) {
                if (! $this->hasUploadedFileOrPath($request, 'nid_front', $existingDocument?->nid_front)) {
                    $validator->errors()->add('nid_front', 'Driver NID front is required for age 18 or above.');
                }
                if (! $this->hasUploadedFileOrPath($request, 'nid_back', $existingDocument?->nid_back)) {
                    $validator->errors()->add('nid_back', 'Driver NID back is required for age 18 or above.');
                }
            }

            // If age < 18 → parent NID required
            if ($age < 18) {
                if (! $this->hasUploadedFileOrPath($request, 'birth_front', $existingDocument?->birth_front)) {
                    $validator->errors()->add('birth_front', 'Birth certificate front is required for drivers under 18.');
                }
                if (! $this->hasUploadedFileOrPath($request, 'parent_nid_front', $existingDocument?->parent_nid_front)) {
                    $validator->errors()->add('parent_nid_front', 'Parent NID front is required for drivers under 18.');
                }
                if (! $this->hasUploadedFileOrPath($request, 'parent_nid_back', $existingDocument?->parent_nid_back)) {
                    $validator->errors()->add('parent_nid_back', 'Parent NID back is required for drivers under 18.');
                }
            }

            if (! $this->hasUploadedFileOrPath($request, 'criminal_record', $existingDocument?->criminal_record)) {
                $validator->errors()->add('criminal_record', 'Criminal record document is required.');
            }

            if (
                Trip::find($request->trip_type_id)?->need_licence &&
                ! $this->hasUploadedFileOrPath($request, 'vehicle_license_image', $existingVehicle?->vehicle_license_image)
            ) {
                $validator->errors()->add('vehicle_license_image', 'Vehicle license image is required.');
            }

            if (! $this->hasUploadedFileOrPath($request, 'car_front_image', $existingVehicle?->car_front_image)) {
                $validator->errors()->add('car_front_image', 'Car front image is required.');
            }

            if (! $this->hasUploadedFileOrPath($request, 'car_back_image', $existingVehicle?->car_back_image)) {
                $validator->errors()->add('car_back_image', 'Car back image is required.');
            }

            if (! $this->hasUploadedFileOrPath($request, 'car_left_image', $existingVehicle?->car_left_image)) {
                $validator->errors()->add('car_left_image', 'Car left image is required.');
            }

            if (! $this->hasUploadedFileOrPath($request, 'car_right_image', $existingVehicle?->car_right_image)) {
                $validator->errors()->add('car_right_image', 'Car right image is required.');
            }
        });

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        return $validator->validated();
    }

    private function fileOrPathRule(Request $request, string $field): string
    {
        return $request->hasFile($field)
            ? 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120'
            : 'nullable|string|max:2048';
    }

    private function hasUploadedFileOrPath(Request $request, string $field, $existingValue = null): bool
    {
        if ($request->hasFile($field)) {
            return true;
        }

        $value = $request->input($field);
        if (is_string($value) && trim($value) !== '') {
            return true;
        }

        return is_string($existingValue) && trim($existingValue) !== '';
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
            // ensure trip_type_id is stored with the document
            'trip_type_id' => $request->trip_type_id,
            'birth_date' => $request->birth_date,
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

    private function buildVehicleData(Request $request, $existing)
    {
        $fields = [
            'vehicle_license_image',
            'car_front_image',
            'car_back_image',
            'car_left_image',
            'car_right_image',
        ];

        $data = [
            'color' => $request->color,
            'year' => $request->year,
            'plate_number' => $request->plate_number,
            'status' => 'pending',
            'isactive' => true,
        ];

        foreach ($fields as $field) {
            if ($request->hasFile($field)) {
                if ($existing && $existing->$field) {
                    $this->deleteOldFile($existing->$field);
                }
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

        $doc->driver->update(['status' => 'active']);
        Wallet::firstOrCreate(
            ['user_id' => $doc->driver->id],
            ['balance' => 0]
        );
        return response()->json(['message' => 'Documents accepted']);
    }

    public function reject(Request $request, $id)
    {
        $doc = DriverDocument::where('id', $id)->first();

        if (! $doc) {
            return response()->json(['message' => 'Document not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'reason' => 'required|string',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $doc->update([
            'status' => 'rejected',
            'reject_reason' => $request->reason,
        ]);
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
