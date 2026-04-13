<?php

namespace App\Http\Controllers\Api;
use Illuminate\Http\Request;

use App\Exports\WalletsExport;
use App\Http\Controllers\Controller;
use App\Http\Resources\WalletResource;
use App\Models\Wallet;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WalletController extends Controller
{
    public function index(Request $request)
    {
        $limit   = $request->input('limit', 10);
        $search  = $request->input('search');
        $sortBy  = $request->input('sort_by', 'id');
        $sortDir = $request->input('sort_dir', 'desc');

        $query = Wallet::with('user');

        // Search (wallet id, user name, balance)
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('id', $search)
                    ->orWhere('balance', $search)
                    ->orWhereHas('user', function ($u) use ($search) {
                        $u->where('name', 'LIKE', "%{$search}%");
                    });
            });
        }

        // Sorting
        if ($sortBy === 'user_name') {
            $query->join('users', 'wallets.user_id', '=', 'users.id')
                ->orderBy('users.name', $sortDir)
                ->select('wallets.*');
        } elseif (in_array($sortBy, ['id', 'balance'])) {
            $query->orderBy($sortBy, $sortDir);
        }

        // EXPORT MODE
        if ($request->has('export')) {
            $format = $request->input('export', 'xlsx'); // xlsx or csv

            if ($format === 'xlsx') {
                return Excel::download(new WalletsExport($query), 'wallets.xlsx');
            }

            // CSV export
            $fileName = 'wallets.csv';

            $response = new StreamedResponse(function () use ($query) {
                $handle = fopen('php://output', 'w');

                fputcsv($handle, ['Wallet ID', 'User Name', 'User Type', 'Balance']);

                $query->chunk(200, function ($wallets) use ($handle) {
                    foreach ($wallets as $wallet) {
                        fputcsv($handle, [
                            $wallet->id,
                            $wallet->user->name,
                            $wallet->user->type,
                            $wallet->balance,
                        ]);
                    }
                });

                fclose($handle);
            });

            $response->headers->set('Content-Type', 'text/csv');
            $response->headers->set('Content-Disposition', "attachment; filename=\"$fileName\"");

            return $response;
        }

        // NORMAL JSON RESPONSE
        $data = $query->paginate($limit);

        return WalletResource::collection($data);
    }

    public function show($id)
    {
        
        $wallet = Wallet::with('user')->find($id);
        if (!$wallet) {
            return response()->json(['message' => 'Wallet not found'], 404);
        }

        return new WalletResource($wallet);
    }
}
