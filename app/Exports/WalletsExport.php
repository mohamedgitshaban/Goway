<?php
namespace App\Exports;

use App\Models\Wallet;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class WalletsExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return Wallet::with('user')->get()->map(function ($wallet) {
            return [
                'wallet_id' => $wallet->id,
                'user_name' => $wallet->user->name,
                'user_type' => $wallet->user->usertype,
                'balance'   => $wallet->balance,
            ];
        });
    }

    public function headings(): array
    {
        return ['Wallet ID', 'User Name', 'User Type', 'Balance'];
    }
}
