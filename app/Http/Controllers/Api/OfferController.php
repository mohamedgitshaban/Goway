<?php

namespace App\Http\Controllers\Api;

use App\Models\Offer;
use App\Models\User;
use App\Http\Resources\OfferResource;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class OfferController extends BaseDiscountController
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->model = Offer::class;
        $this->resource = OfferResource::class;
        $this->searchFields = ['title_ar' , 'title_en', 'description_ar', 'description_en', 'trip_type'];
        $this->notificationService = $notificationService;
    }

    public function store(Request $request)
    {
        $response = parent::store($request);

        // Notify target users about the new offer
        $offer = $response->resource ?? null;
        if ($offer && $offer->is_active) {
            $userType = $offer->user_type; // 'driver' or 'client'
            $users = User::where('usertype', $userType)
                ->where('status', 'active')
                ->whereNotNull('fcm_token')
                ->get();

            $this->notificationService->notifyNewOffer($offer, $users);
        }

        return $response;
    }

    protected function rules($id = null)
    {
        $isUpdate = $id !== null;
        $imageRules = request()->hasFile('image')
            ? 'required|file|image|max:5120'
            : 'required|string|max:2048';

        return [
            'title_ar'           => ($isUpdate ? 'sometimes|required' : 'required') . '|string|max:255',
            'title_en'           => ($isUpdate ? 'sometimes|required' : 'required') . '|string|max:255',
            'description_ar'     => 'nullable|string',
            'image'              => $imageRules,
            'description_en'     => 'nullable|string',
            'discount_type'      => ($isUpdate ? 'sometimes|required' : 'required') . '|in:percentage,fixed',
            'discount_value'     => ($isUpdate ? 'sometimes|required' : 'required') . '|numeric|min:0',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'starts_at'          => ($isUpdate ? 'sometimes|required' : 'required') . '|date',
            'ends_at'            => ($isUpdate ? 'sometimes|required' : 'required') . '|date|after_or_equal:starts_at',
            'is_active'          => 'in:true,false,1,0',
            'user_type'          => ($isUpdate ? 'sometimes|required' : 'required') . '|in:driver,client',
            'trip_type_id'       => ($isUpdate ? 'sometimes|required' : 'required') .'|exists:trip_types,id',
        ];
    }
}
