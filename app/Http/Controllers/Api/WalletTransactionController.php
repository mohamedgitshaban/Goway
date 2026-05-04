<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;

class WalletTransactionController extends Controller
{
    public function driverTransactions(Request $request)
    {
        $user = $request->user();

        if (! $user || $user->usertype !== 'driver') {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
        }

        $data = $this->buildQuery($request)
            ->where('user_id', $user->id)
            ->where('user_type', 'driver')
            ->paginate((int) $request->input('limit', 15))
            ->appends($request->query());

        return response()->json(['status' => true, 'transactions' => $data]);
    }

    public function clientTransactions(Request $request)
    {
        $user = $request->user();

        if (! $user || $user->usertype !== 'client') {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
        }

        $data = $this->buildQuery($request)
            ->where('user_id', $user->id)
            ->where('user_type', 'client')
            ->paginate((int) $request->input('limit', 15))
            ->appends($request->query());

        return response()->json(['status' => true, 'transactions' => $data]);
    }

    public function adminTransactions(Request $request)
    {
        $user = $request->user();

        if (! $user || $user->usertype !== 'admin') {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
        }

        $data = $this->buildQuery($request)
            ->with('user:id,name,phone,usertype')
            ->paginate((int) $request->input('limit', 20))
            ->appends($request->query());

        return response()->json(['status' => true, 'transactions' => $data]);
    }

    private function buildQuery(Request $request)
    {
        $search = $request->input('search');
        $type = $request->input('type');
        $userType = $request->input('user_type');
        $from = $request->input('from');
        $to = $request->input('to');
        $sortBy = $request->input('sort_by', 'id');
        $sortDir = strtolower($request->input('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        $allowedSorts = ['id', 'amount', 'created_at', 'balance_before', 'balance_after'];
        if (! in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'id';
        }

        $query = WalletTransaction::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                if (is_numeric($search)) {
                    $q->orWhere('id', (int) $search)
                        ->orWhere('wallet_id', (int) $search)
                        ->orWhere('user_id', (int) $search);
                }

                $q->orWhere('source', 'LIKE', "%{$search}%");
            });
        }

        if ($type && in_array($type, ['mint', 'burn'], true)) {
            $query->where('type', $type);
        }

        if ($userType && in_array($userType, ['driver', 'client', 'admin'], true)) {
            $query->where('user_type', $userType);
        }

        if ($from) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to) {
            $query->whereDate('created_at', '<=', $to);
        }

        return $query->orderBy($sortBy, $sortDir);
    }
}
