<?php

namespace App\Http\Controllers\Api;

use App\Models\Offer;
use App\Http\Resources\OfferResource;

class OfferController extends BaseDiscountController
{
    public function __construct()
    {
        $this->model = Offer::class;
        $this->resource = OfferResource::class;

        $this->searchFields = ['title_ar' , 'title_en', 'description_ar', 'description_en', 'trip_type'];
    }

    protected function rules($id = null)
    {
        $isUpdate = $id !== null;

        return [
            'title_ar'           => ($isUpdate ? 'sometimes|required' : 'required') . '|string|max:255',
            'title_en'           => ($isUpdate ? 'sometimes|required' : 'required') . '|string|max:255',
            'description_ar'     => 'nullable|string',
            'description_en'     => 'nullable|string',
            'discount_type'      => ($isUpdate ? 'sometimes|required' : 'required') . '|in:percentage,fixed',
            'discount_value'     => ($isUpdate ? 'sometimes|required' : 'required') . '|numeric|min:0',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'starts_at'          => ($isUpdate ? 'sometimes|required' : 'required') . '|date',
            'ends_at'            => ($isUpdate ? 'sometimes|required' : 'required') . '|date|after_or_equal:starts_at',
            'is_active'          => 'boolean',
            'trip_type_id'       => ($isUpdate ? 'sometimes|required' : 'required') .'|exists:trip_types,id',
        ];
    }
}
