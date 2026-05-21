<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Banar;
use Illuminate\Http\Request;

class BanarController extends Controller
{
    /**
     * Display a listing of active banars for the authenticated user's type.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $userType = $user->usertype ?? 'client'; // default to client if not authenticated or not set
        
        $today = now()->toDateString();
        
        $banars = Banar::where('status', 'active')
            ->where('usertype', $userType)
            ->where(function ($query) use ($today) {
                // Return banar if:
                // both dates are null
                // or start_date is before/equal today and end_date is null
                // or start_date is null and end_date is after/equal today
                // or today is between start_date and end_date
                $query->where(function ($q) use ($today) {
                    $q->whereNull('start_date')->whereNull('end_date');
                })->orWhere(function ($q) use ($today) {
                    $q->where('start_date', '<=', $today)->whereNull('end_date');
                })->orWhere(function ($q) use ($today) {
                    $q->whereNull('start_date')->where('end_date', '>=', $today);
                })->orWhere(function ($q) use ($today) {
                    $q->where('start_date', '<=', $today)->where('end_date', '>=', $today);
                });
            })
            ->latest()
            ->get();

        return response()->json([
            'status' => true,
            'data' => $banars
        ]);
    }
}
