<?php

namespace App\Http\Controllers\Api;

use App\Models\Coupon;
use App\Http\Resources\CouponResource;

class CouponController extends BaseDiscountController
{
    public function __construct()
    {
        $this->model = Coupon::class;
        $this->resource = CouponResource::class;

        $this->searchFields = ['code', 'trip_type'];
    }

    protected function rules($id = null)
    {
        $isUpdate = $id !== null;

        return [
            'code'               => ($isUpdate ? 'sometimes|required' : 'required') . '|string|max:50|unique:coupons,code,' . $id,
            'discount_type'      => ($isUpdate ? 'sometimes|required' : 'required') . '|in:percentage,fixed',
            'discount_value'     => ($isUpdate ? 'sometimes|required' : 'required') . '|numeric|min:0',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'usage_limit'        => 'nullable|integer|min:1',
            'per_user_limit'     => 'nullable|integer|min:1',
            'starts_at'          => 'nullable|date',
            'ends_at'            => 'nullable|date|after_or_equal:starts_at',
            'is_active'          => 'boolean',
            'trip_type_id'       => ($isUpdate ? 'sometimes|required' : 'required') .'|exists:trip_types,id',
        ];
    }
    
}
