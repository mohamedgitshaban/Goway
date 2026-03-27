<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\Request;

class UserCouponController extends Controller
{
    public function allCoupons(Request $request)
    {
        $user = $request->user();
        $userId = $user->id;

        // Load all coupons with usage info for this user
        $coupons = Coupon::with(['couponUsers' => function ($q) use ($userId) {
            $q->where('user_id', $userId);
        }])->get();

        $now = now();

        $available = [];
        $used = [];
        $expired = [];

        foreach ($coupons as $coupon) {

            $usage = $coupon->couponUsers->first();
            $timesUsed = $usage ? $usage->times_used : 0;
            $limit = $coupon->per_user_limit ?? 1;

            $remaining = max($limit - $timesUsed, 0);
            $isExpired = $coupon->ends_at && $coupon->ends_at < $now;

            // Build full coupon data
            $couponData = $coupon->toArray();

            // 1) AVAILABLE
            if (!$isExpired && $remaining > 0) {
                $couponData['remaining_uses'] = $remaining;
                $available[] = $couponData;
                continue;
            }

            // 2) USED
            if ($timesUsed > 0) {
                $couponData['times_used'] = $timesUsed;
                $used[] = $couponData;
                continue;
            }

            // 3) EXPIRED & NEVER USED
            if ($isExpired && $timesUsed == 0) {
                $expired[] = $couponData;
            }
        }

        return response()->json([
            'available' => $available,
            'used'      => $used,
            'expired'   => $expired,
        ]);
    }
}
